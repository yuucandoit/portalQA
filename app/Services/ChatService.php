<?php

namespace App\Services;

use App\Models\User;
use App\Models\ChatChannel;
use App\Models\ChatChannelMember;
use App\Models\ChatMessage;
use App\Models\ChatReaction;
use App\Models\ChatReceipt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatService
{
    /**
     * Broadcast an event to the Node.js WebSocket Server via internal HTTP webhook
     */
    public function broadcast(string $event, ?int $channelId = null, array $data = []): bool
    {
        $wsPort = config('services.chat.ws_port', env('CHAT_WS_PORT', 6001));
        $url = "http://127.0.0.1:{$wsPort}/broadcast";

        $payload = json_encode([
            'event'     => $event,
            'channelId' => $channelId,
            'data'      => $data,
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2); // Fast timeout: 2 seconds
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);

        $res = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            // Silently log in debug, do not crash user request if WS server is restarting
            Log::debug("[ChatService::broadcast] WS Broadcast skipped: {$err}");
            return false;
        }

        return true;
    }

    /**
     * Store and broadcast a new message (text, media, audio, file, sticker, gif)
     */
    public function sendMessage(User $user, ChatChannel $channel, array $data): ChatMessage
    {
        $messageType = $data['message_type'] ?? 'text';
        $filePath = null;
        $fileName = null;
        $fileSize = null;
        $fileMime = null;
        $fileDuration = isset($data['file_duration']) ? intval($data['file_duration']) : null;
        $metadata = $data['metadata'] ?? [];

        // Handle uploaded file (image, video, audio, document)
        if (isset($data['file']) && $data['file'] instanceof \Illuminate\Http\UploadedFile) {
            $file = $data['file'];
            $origName = $file->getClientOriginalName();
            $mime = $file->getClientMimeType() ?: $file->getMimeType();
            $size = $file->getSize();

            // Detect message type if not specified
            if (Str::startsWith($mime, 'image/')) {
                $messageType = 'image';
            } elseif (Str::startsWith($mime, 'video/')) {
                $messageType = 'video';
            } elseif (Str::startsWith($mime, 'audio/')) {
                $messageType = 'audio';
            } else {
                $messageType = 'document';
            }

            $folder = 'chat_files/' . date('Y/m');
            $storedPath = $file->store($folder, 'public');

            $filePath = '/storage/' . $storedPath;
            $fileName = $origName;
            $fileSize = $size;
            $fileMime = $mime;
        }

        // Handle Base64 voice recording audio/webm or audio/wav
        if (!empty($data['audio_base64'])) {
            $audioData = base64_decode(preg_replace('#^data:audio/\w+;base64,#i', '', $data['audio_base64']));
            if ($audioData) {
                $ext = $data['audio_ext'] ?? 'webm';
                $name = 'voice_note_' . date('Ymd_His') . '_' . Str::random(6) . '.' . $ext;
                $path = 'chat_files/' . date('Y/m') . '/' . $name;
                Storage::disk('public')->put($path, $audioData);

                $filePath = '/storage/' . $path;
                $fileName = 'Voice Note (' . ($fileDuration ? $fileDuration . 's' : 'Audio') . ')';
                $fileSize = strlen($audioData);
                $fileMime = 'audio/' . $ext;
                $messageType = 'audio';
            }
        }

        $message = ChatMessage::create([
            'channel_id'    => $channel->id,
            'user_id'       => $user->id,
            'parent_id'     => $data['parent_id'] ?? null,
            'reply_to_id'   => $data['reply_to_id'] ?? null,
            'message'       => $data['message'] ?? null,
            'message_type'  => $messageType,
            'file_path'     => $filePath,
            'file_name'     => $fileName,
            'file_size'     => $fileSize,
            'file_mime'     => $fileMime,
            'file_duration' => $fileDuration,
            'metadata'      => $metadata,
        ]);

        // Mark as read for the sender
        ChatReceipt::updateOrCreate(
            ['message_id' => $message->id, 'user_id' => $user->id],
            ['status' => 'read', 'read_at' => now()]
        );

        // Update sender's last read message
        ChatChannelMember::where('channel_id', $channel->id)
            ->where('user_id', $user->id)
            ->update([
                'last_read_message_id' => $message->id,
                'last_read_at'         => now(),
            ]);

        // Load relations for transmission
        $message->load(['user', 'replyTo.user', 'reactions.user']);

        // Format message payload for WebSocket
        $messagePayload = $this->formatMessagePayload($message);

        // Broadcast to channel members
        $this->broadcast('new_message', $channel->id, [
            'message'     => $messagePayload,
            'channelId'   => $channel->id,
            'channelName' => $channel->name,
            'channelSlug' => $channel->slug,
            'isThread'    => (bool) $message->parent_id,
        ]);

        return $message;
    }

    /**
     * Edit a message
     */
    public function editMessage(User $user, ChatMessage $message, string $newText): bool
    {
        if ($message->user_id !== $user->id) {
            abort(403, 'Anda tidak berhak mengedit pesan ini.');
        }

        if ($message->is_deleted) {
            abort(422, 'Pesan yang sudah dihapus tidak dapat diedit.');
        }

        $message->update([
            'message'   => $newText,
            'is_edited' => true,
            'edited_at' => now(),
        ]);

        $this->broadcast('message_updated', $message->channel_id, [
            'messageId' => $message->id,
            'channelId' => $message->channel_id,
            'message'   => $this->formatMessagePayload($message),
        ]);

        return true;
    }

    /**
     * Delete / Recall a message for everyone
     */
    public function deleteMessage(User $user, ChatMessage $message, bool $recallForEveryone = true): bool
    {
        // Owner or sender can delete
        $member = ChatChannelMember::where('channel_id', $message->channel_id)
            ->where('user_id', $user->id)
            ->first();

        $canDelete = ($message->user_id === $user->id) || ($member && in_array($member->role, ['owner', 'admin']));
        if (!$canDelete) {
            abort(403, 'Anda tidak memiliki hak untuk menghapus pesan ini.');
        }

        if ($recallForEveryone) {
            $message->update([
                'is_deleted' => true,
                'message'    => 'Pesan ini telah ditarik kembali',
                'file_path'  => null,
                'file_name'  => null,
            ]);
        } else {
            $message->delete();
        }

        $this->broadcast('message_deleted', $message->channel_id, [
            'messageId' => $message->id,
            'channelId' => $message->channel_id,
        ]);

        return true;
    }

    /**
     * Toggle Pin status on a message
     */
    public function togglePin(User $user, ChatMessage $message): bool
    {
        $newPinnedState = !$message->is_pinned;

        $message->update([
            'is_pinned' => $newPinnedState,
            'pinned_at' => $newPinnedState ? now() : null,
            'pinned_by' => $newPinnedState ? $user->id : null,
        ]);

        $this->broadcast('message_pinned', $message->channel_id, [
            'messageId' => $message->id,
            'channelId' => $message->channel_id,
            'isPinned'  => $newPinnedState,
            'pinnedBy'  => $user->name,
            'pinnedAt'  => now()->format('H:i'),
        ]);

        return $newPinnedState;
    }

    /**
     * Toggle emoji reaction on a message
     */
    public function toggleReaction(User $user, ChatMessage $message, string $emoji): array
    {
        $existing = ChatReaction::where('message_id', $message->id)
            ->where('user_id', $user->id)
            ->where('emoji', $emoji)
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            ChatReaction::create([
                'message_id' => $message->id,
                'user_id'    => $user->id,
                'emoji'      => $emoji,
            ]);
        }

        $message->load('reactions.user');
        $grouped = $message->grouped_reactions;

        $this->broadcast('reaction_updated', $message->channel_id, [
            'messageId' => $message->id,
            'channelId' => $message->channel_id,
            'reactions' => $grouped,
        ]);

        return $grouped;
    }

    /**
     * Mark all messages in a channel as read by the user
     */
    public function markAsRead(User $user, ChatChannel $channel): void
    {
        $latest = $channel->allMessages()->latest()->first();
        if (!$latest) return;

        ChatChannelMember::where('channel_id', $channel->id)
            ->where('user_id', $user->id)
            ->update([
                'last_read_message_id' => $latest->id,
                'last_read_at'         => now(),
            ]);

        // Bulk update delivery and read receipts for unread messages sent by others
        $unreadMessages = ChatMessage::where('channel_id', $channel->id)
            ->where('user_id', '!=', $user->id)
            ->where('id', '<=', $latest->id)
            ->pluck('id');

        foreach ($unreadMessages as $msgId) {
            ChatReceipt::updateOrCreate(
                ['message_id' => $msgId, 'user_id' => $user->id],
                ['status' => 'read', 'read_at' => now()]
            );
        }

        $this->broadcast('receipt_updated', $channel->id, [
            'channelId' => $channel->id,
            'userId'    => $user->id,
            'userName'  => $user->name,
            'lastMsgId' => $latest->id,
            'status'    => 'read',
        ]);
    }

    /**
     * Format message model into standard front-end JSON array
     */
    public function formatMessagePayload(ChatMessage $m): array
    {
        $sender = $m->user;
        $quoted = null;
        if ($m->replyTo) {
            $quoted = [
                'id'       => $m->replyTo->id,
                'sender'   => $m->replyTo->user->name ?? 'User',
                'message'  => Str::limit($m->replyTo->message, 80),
                'type'     => $m->replyTo->message_type,
            ];
        }

        return [
            'id'            => $m->id,
            'channel_id'    => $m->channel_id,
            'user_id'       => $m->user_id,
            'sender_name'   => $sender->name ?? 'User',
            'sender_avatar' => $sender->avatar ?? null,
            'sender_initial'=> $this->getInitials($sender->name ?? 'QA'),
            'parent_id'     => $m->parent_id,
            'message'       => $m->message,
            'message_type'  => $m->message_type,
            'file_path'     => $m->file_path,
            'file_name'     => $m->file_name,
            'file_size'     => $m->file_size ? $this->formatFileSize($m->file_size) : null,
            'file_mime'     => $m->file_mime,
            'file_duration' => $m->file_duration,
            'is_pinned'     => (bool) $m->is_pinned,
            'pinned_at'     => $m->pinned_at ? $m->pinned_at->format('d M H:i') : null,
            'pinned_by'     => $m->pinnedByUser->name ?? null,
            'is_edited'     => (bool) $m->is_edited,
            'is_deleted'    => (bool) $m->is_deleted,
            'metadata'      => $m->metadata ?? [],
            'created_at'    => $m->created_at->format('H:i'),
            'date_str'      => $m->created_at->format('Y-m-d'),
            'time_full'     => $m->created_at->format('d M Y, H:i'),
            'reply_to'      => $quoted,
            'thread_count'  => $m->threadReplies()->count(),
            'reactions'     => $m->grouped_reactions,
            'receipt_status'=> $this->getMessageReceiptStatus($m),
        ];
    }

    /**
     * Compute message delivery / read status (sent, delivered, read)
     */
    private function getMessageReceiptStatus(ChatMessage $m): string
    {
        $receipts = $m->receipts()->where('user_id', '!=', $m->user_id)->get();
        if ($receipts->isEmpty()) {
            return 'sent'; // Single checkmark
        }

        $allRead = $receipts->every(fn($r) => $r->status === 'read');
        if ($allRead) {
            return 'read'; // Double blue checkmark
        }

        return 'delivered'; // Double gray checkmark
    }

    private function getInitials(string $name): string
    {
        $words = explode(' ', trim($name));
        return count($words) >= 2
            ? strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1))
            : strtoupper(substr($name, 0, 2));
    }

    private function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}

