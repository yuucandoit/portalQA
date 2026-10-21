<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ChatChannel extends Model
{
    use HasFactory;

    protected $table = 'chat_channels';

    protected $fillable = [
        'name',
        'slug',
        'type', // public_channel, private_group, direct, broadcast
        'topic',
        'description',
        'avatar',
        'created_by',
        'is_archived',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ChatChannelMember::class, 'channel_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_channel_members', 'channel_id', 'user_id')
            ->withPivot(['role', 'is_muted', 'last_read_message_id', 'last_read_at'])
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'channel_id')->whereNull('parent_id');
    }

    public function allMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'channel_id');
    }

    public function pinnedMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'channel_id')
            ->where('is_pinned', true)
            ->where('is_deleted', false)
            ->orderBy('pinned_at', 'desc');
    }

    public function latestMessage()
    {
        return $this->hasOne(ChatMessage::class, 'channel_id')
            ->whereNull('parent_id')
            ->where('is_deleted', false)
            ->latestOfMany();
    }

    /**
     * Get display title for the channel for a specific user
     */
    public function getDisplayTitleForUser(?User $user): string
    {
        if ($this->type === 'direct' && $user) {
            $other = $this->users()->where('users.id', '!=', $user->id)->first();
            return $other ? $other->name : $this->name;
        }
        return $this->name;
    }

    /**
     * Get display avatar for a specific user
     */
    public function getDisplayAvatarForUser(?User $user): ?string
    {
        if ($this->type === 'direct' && $user) {
            $other = $this->users()->where('users.id', '!=', $user->id)->first();
            return $other ? ($other->avatar ?? null) : $this->avatar;
        }
        return $this->avatar;
    }
}

