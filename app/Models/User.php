<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'gemini_api_key',
        'openrouter_api_key',
        'openrouter_model',
        'ollama_base_url',
        'ollama_model',
        'omniroute_api_key',
        'omniroute_base_url',
        'omniroute_model',
        'openai_api_key',
        'openai_base_url',
        'openai_model',
        'default_ai_provider',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get total unread chat messages for this user across all accessible channels
     */
    public function unreadChatCount(): int
    {
        $channelIds = ChatChannel::where(function ($q) {
            $q->where('type', 'public_channel')
              ->orWhere('type', 'broadcast')
              ->orWhereHas('members', fn($mq) => $mq->where('user_id', $this->id));
        })
        ->where('is_archived', false)
        ->pluck('id');

        if ($channelIds->isEmpty()) {
            return 0;
        }

        $memberships = ChatChannelMember::whereIn('channel_id', $channelIds)
            ->where('user_id', $this->id)
            ->pluck('last_read_message_id', 'channel_id');

        $totalUnread = 0;
        foreach ($channelIds as $chId) {
            $lastReadId = (int)($memberships[$chId] ?? 0);
            $totalUnread += ChatMessage::where('channel_id', $chId)
                ->where('user_id', '!=', $this->id)
                ->where('is_deleted', false)
                ->where('id', '>', $lastReadId)
                ->count();
        }

        return $totalUnread;
    }

    /**
     * Get configured Gemini API Key (User key or env fallback)
     */
    public function getResolvedGeminiKey(): string
    {
        return $this->gemini_api_key ?: config('ai.gemini.api_key', '');
    }

    /**
     * Get configured OpenRouter API Key (User key or env fallback)
     */
    public function getResolvedOpenRouterKey(): string
    {
        return $this->openrouter_api_key ?: config('ai.openrouter.api_key', '');
    }

    /**
     * Get configured OpenRouter Model (User setting or env fallback)
     */
    public function getResolvedOpenRouterModel(): string
    {
        return $this->openrouter_model ?: config('ai.openrouter.model', 'meta-llama/llama-3.1-8b-instruct:free');
    }

    /**
     * Get configured Ollama Base URL (User setting or env fallback)
     */
    public function getResolvedOllamaBaseUrl(): string
    {
        return $this->ollama_base_url ?: config('ai.ollama.base_url', 'http://localhost:11434');
    }

    /**
     * Get configured Ollama Model (User setting or env fallback)
     */
    public function getResolvedOllamaModel(): string
    {
        return $this->ollama_model ?: config('ai.ollama.model', 'llama3.2');
    }

    /**
     * Get configured OmniRoute API Key (User key or env fallback)
     */
    public function getResolvedOmnirouteKey(): string
    {
        return $this->omniroute_api_key ?: config('ai.omniroute.api_key', '');
    }

    /**
     * Get configured OmniRoute Base URL (User setting or env fallback)
     */
    public function getResolvedOmnirouteBaseUrl(): string
    {
        return $this->omniroute_base_url ?: config('ai.omniroute.base_url', 'http://202.155.143.157:20128/v1');
    }

    /**
     * Get configured OmniRoute Model (User setting or env fallback)
     */
    public function getResolvedOmnirouteModel(): string
    {
        return $this->omniroute_model ?: config('ai.omniroute.model', 'auto');
    }

    /**
     * Get configured OpenAI API Key (User key or env fallback)
     */
    public function getResolvedOpenAiKey(): string
    {
        return $this->openai_api_key ?: config('ai.openai.api_key', '');
    }

    /**
     * Get configured OpenAI Base URL (User setting or env fallback)
     */
    public function getResolvedOpenAiBaseUrl(): string
    {
        return $this->openai_base_url ?: config('ai.openai.base_url', 'https://api.openai.com/v1');
    }

    /**
     * Get configured OpenAI Model (User setting or env fallback)
     */
    public function getResolvedOpenAiModel(): string
    {
        return $this->openai_model ?: config('ai.openai.model', 'gpt-4o-mini');
    }

    /**
     * Get user preferred AI provider (User setting or env fallback)
     */
    public function getResolvedDefaultAiProvider(): string
    {
        return $this->default_ai_provider ?: config('ai.default_provider', 'gemini');
    }
}

