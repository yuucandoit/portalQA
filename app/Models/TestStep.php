<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestStep extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'test_case_id',
        'step_order',
        'step_description',
        'expected_result',
        'actual_result',
        'status',
    ];

    /**
     * Get the test case that owns the step.
     */
    public function testCase(): BelongsTo
    {
        return $this->belongsTo(TestCase::class);
    }

    /**
     * Scope a query to order by step order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('step_order');
    }

    /**
     * Reorder steps.
     */
    public static function reorderSteps(int $testCaseId, array $stepIds)
    {
        foreach ($stepIds as $index => $stepId) {
            self::where('id', $stepId)
                ->where('test_case_id', $testCaseId)
                ->update(['step_order' => $index + 1]);
        }
    }

    /**
     * Clone step to another test case.
     */
    public function cloneTo(int $testCaseId): self
    {
        return self::create([
            'test_case_id' => $testCaseId,
            'step_order' => $this->step_order,
            'step_description' => $this->step_description,
            'expected_result' => $this->expected_result,
            'actual_result' => $this->actual_result,
            'status' => $this->status,
        ]);
    }
}