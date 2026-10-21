<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CopilotMessage extends Model
{
    use HasFactory;

    protected $table = 'copilot_messages';

    protected $fillable = [
        'chat_id',
        'role',
        'content',
        'draft_payload',
        'action_type',
        'provider_used',
    ];

    protected $casts = [
        'draft_payload' => 'array',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    public function chat(): BelongsTo
    {
        return $this->belongsTo(CopilotChat::class, 'chat_id');
    }
}
