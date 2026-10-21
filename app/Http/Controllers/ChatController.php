<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ChatChannel;
use App\Models\ChatChannelMember;
use App\Models\ChatMessage;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    protected ChatService $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    /**
     * Main Chat Room Workspace (Slack/Discord style)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        // 1. Fetch user channels with unread counts
        $channels = ChatChannel::where(function ($q) use ($user) {
            $q->where('type', 'public_channel')
              ->orWhere('type', 'broadcast')
              ->orWhereHas('members', fn($mq) => $mq->where('user_id', $user->id));
        })
        ->where('is_archived', false)
        ->withCount(['members', 'pinnedMessages'])
        ->get();

        // 2. Identify active channel
        $activeChannel = null;
        if ($request->filled('channel')) {
            $slugOrId = $request->channel;
            $activeChannel = $channels->first(fn($c) => $c->slug === $slugOrId || $c->id == $slugOrId);
        }

        if (!$activeChannel) {
            $activeChannel = $channels->firstWhere('slug', 'general') ?: $channels->first();
        }

        // Ensure user is member of active channel if public
        if ($activeChannel) {
            ChatChannelMember::firstOrCreate(
                ['channel_id' => $activeChannel->id, 'user_id' => $user->id],
                ['role' => 'member']
            );

            // Mark active channel as read
            $this->chatService->markAsRead($user, $activeChannel);
        }

        // 3. Load latest messages for active channel
        $messages = collect();
        $pinnedMessages = collect();

        if ($activeChannel) {
            $messages = ChatMessage::where('channel_id', $activeChannel->id)
                ->whereNull('parent_id') // main timeline only
                ->with(['user', 'replyTo.user', 'reactions.user'])
                ->orderBy('created_at', 'asc')
                ->take(100)
                ->get()
                ->map(fn($m) => $this->chatService->formatMessagePayload($m));

            $pinnedMessages = ChatMessage::where('channel_id', $activeChannel->id)
                ->where('is_pinned', true)
                ->where('is_deleted', false)
                ->with(['user'])
                ->orderBy('pinned_at', 'desc')
                ->get()
                ->map(fn($m) => $this->chatService->formatMessagePayload($m));
        }

        // 4. Other team members for direct messages & member invite
        $teamUsers = User::where('id', '!=', $user->id)->orderBy('name')->get();
        $allUsers = User::select('id', 'name', 'email')->orderBy('name')->get();

        // 5. Categorize channels for the sidebar
        $publicChannels = $channels->whereIn('type', ['public_channel', 'broadcast'])->values();
        $privateGroups = $channels->where('type', 'private_group')->values();
        $directMessages = $channels->where('type', 'direct')->map(function ($c) use ($user) {
            $c->display_name = $c->getDisplayTitleForUser($user);
            return $c;
        })->values();

        $wsPort = config('services.chat.ws_port', env('CHAT_WS_PORT', 6001));

        $channelMembers = collect();
        if ($activeChannel) {
            $channelMembers = $activeChannel->members()->with('user')->get()->map(function ($m) {
                return [
                    'id'   => $m->user->id ?? $m->user_id,
                    'name' => $m->user->name ?? 'User',
                    'email'=> $m->user->email ?? '',
                    'role' => $m->role,
                ];
            })->values();
        }

        return view('pages.chat.index', compact(
            'user',
            'channels',
            'publicChannels',
            'privateGroups',
            'directMessages',
            'activeChannel',
            'messages',
            'pinnedMessages',
            'teamUsers',
            'allUsers',
            'channelMembers',
            'wsPort'
        ));
    }

    /**
     * Create a new Channel, Private Group, or Broadcast
     */
    public function createChannel(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name'        => 'required|string|max:80',
            'type'        => 'required|in:public_channel,private_group,broadcast',
            'topic'       => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'member_ids'  => 'nullable|array',
            'member_ids.*'=> 'exists:users,id',
        ]);

        $baseSlug = Str::slug($request->name);
        $slug = $baseSlug ?: 'channel-' . Str::random(5);
        $count = 1;
        while (ChatChannel::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . (++$count);
        }

        $channel = DB::transaction(function () use ($request, $user, $slug) {
            $channel = ChatChannel::create([
                'name'        => strtolower(trim($request->name)),
                'slug'        => $slug,
                'type'        => $request->type,
                'topic'       => $request->topic,
                'description' => $request->description,
                'created_by'  => $user->id,
            ]);

            // Add creator as owner
            ChatChannelMember::create([
                'channel_id' => $channel->id,
                'user_id'    => $user->id,
                'role'       => 'owner',
            ]);

            // Add invited members
            if ($request->filled('member_ids')) {
                foreach ($request->member_ids as $mId) {
                    if ((int)$mId !== (int)$user->id) {
                        ChatChannelMember::firstOrCreate([
                            'channel_id' => $channel->id,
                            'user_id'    => $mId,
                        ], [
                            'role' => 'member',
                        ]);
                    }
                }
            }

            // If public, enroll all users
            if ($request->type === 'public_channel') {
                foreach (User::where('id', '!=', $user->id)->get() as $other) {
                    ChatChannelMember::firstOrCreate([
                        'channel_id' => $channel->id,
                        'user_id'    => $other->id,
                    ], ['role' => 'member']);
                }
            }

            // Create initial system message
            $channelDesc = $request->description ? " - {$request->description}" : '';
            ChatMessage::create([
                'channel_id'   => $channel->id,
                'user_id'      => $user->id,
                'message'      => "{$user->name} membuat channel **#{$channel->name}**{$channelDesc}",
                'message_type' => 'system',
            ]);

            return $channel;
        });

        $this->chatService->broadcast('channel_created', null, [
            'channel' => [
                'id'    => $channel->id,
                'name'  => $channel->name,
                'slug'  => $channel->slug,
                'type'  => $channel->type,
                'topic' => $channel->topic,
            ],
            'creator' => $user->name,
        ]);

        if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'channel' => $channel,
                'redirect'=> route('chat.index', ['channel' => $channel->slug]),
            ]);
        }

        return redirect()->route('chat.index', ['channel' => $channel->slug]);
    }

    /**
     * Start or open Direct Message with a specific user
     */
    public function directMessage(Request $request, int $targetUserId)
    {
        $user = Auth::user();
        $targetUser = User::findOrFail($targetUserId);

        if ($user->id === $targetUser->id) {
            return redirect()->route('chat.index');
        }

        $slug = 'dm-' . min($user->id, $targetUser->id) . '-' . max($user->id, $targetUser->id);

        $channel = ChatChannel::where('slug', $slug)->first();
        if (!$channel) {
            $channel = DB::transaction(function () use ($slug, $user, $targetUser) {
                $ch = ChatChannel::create([
                    'name'        => $targetUser->name,
                    'slug'        => $slug,
                    'type'        => 'direct',
                    'topic'       => 'Pesan langsung dengan ' . $targetUser->name,
                    'description' => 'Obrolan pribadi langsung 1-on-1.',
                    'created_by'  => $user->id,
                ]);

                ChatChannelMember::create(['channel_id' => $ch->id, 'user_id' => $user->id, 'role' => 'member']);
                ChatChannelMember::create(['channel_id' => $ch->id, 'user_id' => $targetUser->id, 'role' => 'member']);

                return $ch;
            });
        }

        if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'slug'    => $channel->slug,
                'redirect'=> route('chat.index', ['channel' => $channel->slug]),
            ]);
        }

        return redirect()->route('chat.index', ['channel' => $channel->slug]);
    }

    /**
     * Send Message (Text, Files, Voice Notes, Gifs, Stickers)
     */
    public function sendMessage(Request $request, int $channelId)
    {
        $user = Auth::user();
        $channel = ChatChannel::findOrFail($channelId);

        // Broadcast channels: only owner or admin can post
        if ($channel->type === 'broadcast') {
            $member = ChatChannelMember::where('channel_id', $channel->id)->where('user_id', $user->id)->first();
            if (!$member || !in_array($member->role, ['owner', 'admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya admin yang dapat mengirim pesan di channel broadcast ini.',
                ], 403);
            }
        }

        $request->validate([
            'message'       => 'nullable|string|max:10000',
            'file'          => 'nullable|file|max:51200', // max 50MB
            'audio_base64'  => 'nullable|string',
            'parent_id'     => 'nullable|exists:chat_messages,id',
            'reply_to_id'   => 'nullable|exists:chat_messages,id',
            'message_type'  => 'nullable|string|in:text,image,video,audio,document,sticker,gif,system',
            'file_duration' => 'nullable|integer',
            'metadata'      => 'nullable|array',
        ]);

        if (!$request->filled('message') && !$request->hasFile('file') && !$request->filled('audio_base64')) {
            return response()->json(['success' => false, 'message' => 'Pesan atau berkas tidak boleh kosong.'], 422);
        }

        $msg = $this->chatService->sendMessage($user, $channel, $request->all());

        return response()->json([
            'success' => true,
            'message' => $this->chatService->formatMessagePayload($msg),
        ]);
    }

    /**
     * Get Thread Replies for a parent message
     */
    public function getThread(Request $request, int $channelId, int $messageId)
    {
        $parent = ChatMessage::where('channel_id', $channelId)
            ->where('id', $messageId)
            ->with(['user', 'reactions.user'])
            ->firstOrFail();

        $replies = ChatMessage::where('channel_id', $channelId)
            ->where('parent_id', $messageId)
            ->with(['user', 'replyTo.user', 'reactions.user'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($m) => $this->chatService->formatMessagePayload($m));

        return response()->json([
            'success' => true,
            'parent'  => $this->chatService->formatMessagePayload($parent),
            'replies' => $replies,
        ]);
    }

    /**
     * Edit Message
     */
    public function editMessage(Request $request, int $messageId)
    {
        $user = Auth::user();
        $message = ChatMessage::findOrFail($messageId);

        $request->validate([
            'message' => 'required|string|max:10000',
        ]);

        $this->chatService->editMessage($user, $message, $request->message);

        return response()->json([
            'success' => true,
            'message' => $this->chatService->formatMessagePayload($message->fresh()),
        ]);
    }

    /**
     * Delete Message (Recall for everyone)
     */
    public function deleteMessage(Request $request, int $messageId)
    {
        $user = Auth::user();
        $message = ChatMessage::findOrFail($messageId);

        $this->chatService->deleteMessage($user, $message, true);

        return response()->json([
            'success' => true,
            'messageId' => $messageId,
        ]);
    }

    /**
     * Toggle Pin Message
     */
    public function togglePin(Request $request, int $messageId)
    {
        $user = Auth::user();
        $message = ChatMessage::findOrFail($messageId);

        $isPinned = $this->chatService->togglePin($user, $message);

        return response()->json([
            'success'  => true,
            'isPinned' => $isPinned,
            'messageId'=> $messageId,
        ]);
    }

    /**
     * Toggle Emoji Reaction
     */
    public function toggleReaction(Request $request, int $messageId)
    {
        $user = Auth::user();
        $message = ChatMessage::findOrFail($messageId);

        $request->validate([
            'emoji' => 'required|string|max:20',
        ]);

        $grouped = $this->chatService->toggleReaction($user, $message, $request->emoji);

        return response()->json([
            'success'   => true,
            'reactions' => $grouped,
            'messageId' => $messageId,
        ]);
    }

    /**
     * Mark channel as read
     */
    public function markAsRead(Request $request, int $channelId)
    {
        $user = Auth::user();
        $channel = ChatChannel::findOrFail($channelId);

        $this->chatService->markAsRead($user, $channel);

        return response()->json(['success' => true]);
    }

    /**
     * Search Messages in Channel or Globally
     */
    public function search(Request $request, ?int $channelId = null)
    {
        $query = ChatMessage::query()->where('is_deleted', false);

        if ($channelId) {
            $query->where('channel_id', $channelId);
        }

        // Keyword filter
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('message', 'LIKE', "%{$q}%")
                    ->orWhere('file_name', 'LIKE', "%{$q}%");
            });
        }

        // Type filter: media, documents, links, pinned
        if ($request->filled('filter')) {
            switch ($request->filter) {
                case 'media':
                    $query->whereIn('message_type', ['image', 'video']);
                    break;
                case 'documents':
                    $query->where('message_type', 'document');
                    break;
                case 'links':
                    $query->where(function ($l) {
                        $l->where('message', 'LIKE', '%http://%')
                          ->orWhere('message', 'LIKE', '%https://%');
                    });
                    break;
                case 'pinned':
                    $query->where('is_pinned', true);
                    break;
            }
        }

        $results = $query->with(['user', 'channel'])
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get()
            ->map(fn($m) => $this->chatService->formatMessagePayload($m));

        return response()->json([
            'success' => true,
            'data'    => $results,
        ]);
    }
}

