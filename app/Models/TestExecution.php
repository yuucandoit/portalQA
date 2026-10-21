<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestExecution extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'test_case_id',
        'executed_by',
        'status',
        'test_result',
        'actual_result',
        'notes_qa',
        'notes_programmer',
        'evidence_url',
        'date_tested',
        'execution_number',
        'environment_info',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_tested' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the test case that owns the execution.
     */
    public function testCase(): BelongsTo
    {
        return $this->belongsTo(TestCase::class);
    }

    /**
     * Get the user who executed the test.
     */
    public function executedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter by test result.
     */
    public function scopeWithResult($query, string $result)
    {
        return $query->where('test_result', $result);
    }

    /**
     * Scope a query to get latest executions.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('execution_number', 'desc');
    }

    /**
     * Get status badge class.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'PASS' => 'badge-success',
            'FAILED' => 'badge-danger',
            'BLOCKED' => 'badge-warning',
            'SKIPPED' => 'badge-secondary',
            default => 'badge-secondary',
        };
    }

    /**
     * Get test result badge class.
     */
    public function getTestResultBadgeClassAttribute(): string
    {
        return match($this->test_result) {
            'PASSED' => 'badge-success',
            'FAILED' => 'badge-danger',
            'BLOCKED' => 'badge-warning',
            'NOT EXECUTED' => 'badge-secondary',
            default => 'badge-secondary',
        };
    }

    /**
     * Check if execution is successful.
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'PASS' || $this->test_result === 'PASSED';
    }

    /**
     * Check if execution is failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'FAILED' || $this->test_result === 'FAILED';
    }

    /**
     * Create a new execution (auto-increment execution number).
     */
    public static function createExecution(int $testCaseId, array $data): self
    {
        $lastExecution = self::where('test_case_id', $testCaseId)
                             ->orderBy('execution_number', 'desc')
                             ->first();
        
        $executionNumber = $lastExecution ? $lastExecution->execution_number + 1 : 1;
        
        $data['execution_number'] = $executionNumber;
        $data['test_case_id'] = $testCaseId;
        
        return self::create($data);
    }
}