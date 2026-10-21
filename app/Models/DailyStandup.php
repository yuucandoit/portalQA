<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyStandup extends Model
{
    use HasFactory;

    protected $table = 'daily_standups';

    protected $fillable = [
        'user_id',
        'date',
        'user_name',
        'completed_tasks',
        'blockers',
        'next_plans',
        'raw_input',
        'formatted_output',
    ];

    protected $casts = [
        'date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
