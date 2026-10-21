<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatMessage extends Model
{
    use HasFactory;

    protected $table = 'chat_messages';

    protected $fillable = [
        'channel_id',
        'user_id',
        'parent_id',
        'reply_to_id',
        'message',
        'message_type', // text, image, video, audio, document, sticker, gif, system
        'file_path',
        'file_name',
        'file_size',
        'file_mime',
        'file_duration',
        'is_pinned',
        'pinned_at',
        'pinned_by',
        'is_edited',
        'edited_at',
        'is_deleted',
        'metadata',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'pinned_at' => 'datetime',
        'is_edited' => 'boolean',
        'edited_at' => 'datetime',
        'is_deleted' => 'boolean',
        'metadata' => 'array',
        'file_duration' => 'integer',
        'file_size' => 'integer',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(ChatChannel::class, 'channel_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'parent_id');
    }

    public function threadReplies(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'parent_id')->orderBy('created_at', 'asc');
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'reply_to_id');
    }

    public function pinnedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pinned_by');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(ChatReaction::class, 'message_id');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(ChatReceipt::class, 'message_id');
    }

    /**
     * Group reactions by emoji with list of reacting users
     */
    public function getGroupedReactionsAttribute(): array
    {
        $grouped = [];
        foreach ($this->reactions as $reaction) {
            $e = $reaction->emoji;
            if (!isset($grouped[$e])) {
                $grouped[$e] = [
                    'emoji' => $e,
                    'count' => 0,
                    'users' => [],
                    'user_ids' => []
                ];
            }
            $grouped[$e]['count']++;
            $grouped[$e]['users'][] = $reaction->user->name ?? 'User';
            $grouped[$e]['user_ids'][] = $reaction->user_id;
        }
        return array_values($grouped);
    }
}

