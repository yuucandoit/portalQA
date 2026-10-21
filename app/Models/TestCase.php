<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo as BelongsToAlias;

class TestCase extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'report_id',
        'project_id',
        'test_type',
        'title',
        'test_case_id',
        'priority',
        'precondition',
        'dynamic_fields',
        'status',
        'actual_result',
        'notes_qa',
        'notes_programmer',
        'buckleup_ticket',
        'bug_ticket',
        'evidence_url',
        'is_ai_generated',
        'review_status',
        'date_tested',
        'tested_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'dynamic_fields' => 'array',
        'is_ai_generated' => 'boolean',
        'date_tested' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the project that owns the test case.
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(TestReport::class, 'report_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Projects::class, 'project_id');
    }

    /**
     * Get the user who tested this case.
     */
    public function testedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tested_by');
    }

    /**
     * Get all test steps for this test case.
     */
    public function steps(): HasMany
    {
        return $this->hasMany(TestStep::class)->orderBy('step_order');
    }

    /**
     * Get all executions for this test case.
     */
    public function executions(): HasMany
    {
        return $this->hasMany(TestExecution::class)->orderBy('execution_number', 'desc');
    }

    /**
     * Get the latest execution for this test case.
     */
    public function latestExecution(): HasOne
    {
        return $this->hasOne(TestExecution::class)->latestOfMany('execution_number');
    }

    /**
     * Get the latest successful execution.
     */
    public function latestPassedExecution(): HasOne
    {
        return $this->hasOne(TestExecution::class)
                    ->where('status', 'PASS')
                    ->latestOfMany('execution_number');
    }

    /**
     * Check if test case has steps.
     */
    public function hasSteps(): bool
    {
        return $this->steps()->exists();
    }

    /**
     * Get dynamic field value by key.
     */
    public function getDynamicField(string $key, $default = null)
    {
        return $this->dynamic_fields[$key] ?? $default;
    }

    /**
     * Set dynamic field value.
     */
    public function setDynamicField(string $key, $value): self
    {
        $fields = $this->dynamic_fields ?? [];
        $fields[$key] = $value;
        $this->dynamic_fields = $fields;
        return $this;
    }

    /**
     * Get all dynamic fields as collection.
     */
    public function getDynamicFieldsAttribute($value)
    {
        return collect(json_decode($value, true) ?? []);
    }

    /**
     * Scope a query to filter by test type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('test_type', $type);
    }

    /**
     * Scope a query to filter by priority.
     */
    public function scopeOfPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to search test cases.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where('title', 'LIKE', "%{$search}%")
                     ->orWhere('precondition', 'LIKE', "%{$search}%")
                     ->orWhere('notes_qa', 'LIKE', "%{$search}%");
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
            default => 'badge-secondary',
        };
    }

    /**
     * Get priority badge class.
     */
    public function getPriorityBadgeClassAttribute(): string
    {
        return match($this->priority) {
            'Critical' => 'badge-danger',
            'High' => 'badge-warning',
            'Medium' => 'badge-info',
            'Low' => 'badge-secondary',
            default => 'badge-secondary',
        };
    }

    /**
     * Get test type label.
     */
    public function getTestTypeLabelAttribute(): string
    {
        return match($this->test_type) {
            'functional' => 'Functional Test',
            'integration' => 'Integration Test',
            'regression' => 'Regression Test',
            'performance' => 'Performance Test',
            'reliability' => 'Reliability Test',
            'stress' => 'Stress Test',
            'negative' => 'Negative Test',
            'validation' => 'Validation Test',
            'hardware' => 'Hardware Test',
            default => ucfirst($this->test_type),
        };
    }

    /**
     * Check if test case has execution history.
     */
    public function hasExecutions(): bool
    {
        return $this->executions()->exists();
    }

    /**
     * Get execution count.
     */
    public function getExecutionCountAttribute(): int
    {
        return $this->executions()->count();
    }

    /**
     * Get related Bug from Bug List (QA-TPL-001)
     */
    public function bug(): HasOne
    {
        return $this->hasOne(Bug::class, 'test_case_id');
    }
}