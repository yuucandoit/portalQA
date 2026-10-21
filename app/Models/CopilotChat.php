<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CopilotChat extends Model
{
    use HasFactory;

    protected $table = 'copilot_chats';

    protected $fillable = [
        'user_id',
        'project_id',
        'report_id',
        'title',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Projects::class, 'project_id');
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(TestReport::class, 'report_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CopilotMessage::class, 'chat_id')->orderBy('id', 'asc');
    }

    public function latestMessage(): HasMany
    {
        return $this->hasMany(CopilotMessage::class, 'chat_id')->latest()->limit(1);
    }
}
