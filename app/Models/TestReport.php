<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestReport extends Model
{
    use HasFactory;

    protected $table = 'test_reports';

    protected $fillable = [
        'project_id',
        'created_by',
        'report_name',
        'sprint',
        'cycle',
        'test_type',
        'report_date',
        'description',
    ];

    protected $casts = [
        'report_date' => 'date',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    /**
     * The project this report belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Projects::class, 'project_id');
    }

    /**
     * The user who created this report.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * All test cases belonging to this report.
     */
    public function testCases(): HasMany
    {
        return $this->hasMany(TestCase::class, 'report_id')->orderBy('id');
    }

    /**
     * All bugs linked to this test report.
     */
    public function bugs(): HasMany
    {
        return $this->hasMany(Bug::class, 'report_id');
    }

    /**
     * Total number of test cases in this report.
     */
    public function getTestCaseCountAttribute(): int
    {
        return $this->testCases()->count();
    }

    /**
     * Number of PASS test cases.
     */
    public function getPassCountAttribute(): int
    {
        return $this->testCases()->whereIn('status', ['Pass', 'PASS'])->count();
    }

    /**
     * Number of FAILED test cases.
     */
    public function getFailedCountAttribute(): int
    {
        return $this->testCases()->whereIn('status', ['Fail', 'FAILED'])->count();
    }

    /**
     * Number of BLOCKED test cases.
     */
    public function getBlockedCountAttribute(): int
    {
        return $this->testCases()->whereIn('status', ['Blocked', 'BLOCKED'])->count();
    }

    /**
     * Number of NOT RUN test cases.
     */
    public function getNotRunCountAttribute(): int
    {
        return $this->testCases()->whereIn('status', ['Not Run', 'NOT_RUN', 'Not Executed', 'NOT_EXECUTED'])->count();
    }

    /**
     * Number of N/A test cases.
     */
    public function getNaCountAttribute(): int
    {
        return $this->testCases()->whereIn('status', ['N/A', 'NA'])->count();
    }

    /**
     * Pass rate percentage.
     */
    public function getPassRateAttribute(): int
    {
        $total = $this->test_case_count;
        if ($total === 0) return 0;
        return round(($this->pass_count / $total) * 100);
    }

    /**
     * Overall report status based on test case results.
     */
    public function getOverallStatusAttribute(): string
    {
        $cases = $this->testCases()->pluck('status');
        if ($cases->isEmpty()) return 'Not Run';
        if ($cases->contains(fn($s) => in_array($s, ['Fail', 'FAILED'])))    return 'Fail';
        if ($cases->contains(fn($s) => in_array($s, ['Blocked', 'BLOCKED']))) return 'Blocked';
        if ($cases->every(fn($s) => in_array($s, ['Pass', 'PASS'])))         return 'Pass';
        return 'In Progress';
    }

    /**
     * Human readable test type label.
     */
    public function getTestTypeLabelAttribute(): string
    {
        return match($this->test_type) {
            'functional'  => 'Functional Test',
            'integration' => 'Integration Test',
            'regression'  => 'Regression Test',
            'performance' => 'Performance Test',
            'reliability' => 'Reliability Test',
            'stress'      => 'Stress Test',
            'negative'    => 'Negative Test',
            'validation'  => 'Validation Test',
            'hardware'    => 'Hardware Test',
            default       => ucfirst($this->test_type),
        };
    }

    /**
     * Scope: search by report name, sprint, or project name.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('report_name', 'LIKE', "%{$search}%")
              ->orWhere('sprint', 'LIKE', "%{$search}%")
              ->orWhereHas('project', fn($pq) => $pq->where('name', 'LIKE', "%{$search}%")->orWhere('project_code', 'LIKE', "%{$search}%"));
        });
    }

    /**
     * Compute Quality Gate & Completeness Check metrics aligned with QA-TPL-001 (Ringkasan_TC).
     */
    public function getQualityGateMetrics(): array
    {
        $cases = $this->testCases;
        $totalCases = $cases->count();

        $passCount    = $cases->filter(fn($c) => in_array($c->status, ['Pass', 'PASS']))->count();
        $failCount    = $cases->filter(fn($c) => in_array($c->status, ['Fail', 'FAIL', 'FAILED']))->count();
        $blockedCount = $cases->filter(fn($c) => in_array($c->status, ['Blocked', 'BLOCKED']))->count();
        $notRunCount  = $cases->filter(fn($c) => in_array($c->status, ['Not Run', 'NOT_RUN', 'Not Executed', 'NOT_EXECUTED']))->count();
        $naCount      = $cases->filter(fn($c) => in_array($c->status, ['N/A', 'NA']))->count();

        $executedCount = $passCount + $failCount;
        $passRate = $executedCount > 0 ? round(($passCount / $executedCount) * 100, 1) : 0;
        $progress = $totalCases > 0 ? round((($totalCases - $notRunCount) / $totalCases) * 100, 1) : 0;

        // Completeness checks
        $failWithoutBug = $cases->filter(fn($c) => in_array($c->status, ['Fail', 'FAIL', 'FAILED']) && empty(trim((string)$c->bug_ticket)))->count();
        $executedWithoutEvidence = $cases->filter(fn($c) => in_array($c->status, ['Pass', 'PASS', 'Fail', 'FAIL', 'FAILED']) && empty(trim((string)$c->evidence_url)))->count();

        // Hardware Rule 7 check: Pass without valid spec
        $hwPassWithoutSpec = 0;
        if ($this->test_type === 'hardware') {
            $hwPassWithoutSpec = $cases->filter(function ($c) {
                if (!in_array($c->status, ['Pass', 'PASS'])) return false;
                $dyn = is_array($c->dynamic_fields) ? $c->dynamic_fields : (json_decode($c->dynamic_fields, true) ?? []);
                $spec = trim((string)($dyn['passLimitSpec'] ?? ''));
                return empty($spec) || strtoupper($spec) === 'TBD';
            })->count();
        }

        // Active critical bugs for this report or project
        $activeCriticalBugs = Bug::where(function ($q) {
            $q->where('report_id', $this->id)
              ->orWhere('project_id', $this->project_id);
        })
        ->whereIn('severity', ['Critical', 'Major'])
        ->whereNotIn('status', ['Closed', 'Resolved'])
        ->count();

        // Quality Gate Verdict
        $reasons = [];
        if ($failWithoutBug > 0) {
            $reasons[] = "{$failWithoutBug} test case Fail belum memiliki Bug ID (Aturan 3 QA-TPL-001).";
        }
        if ($hwPassWithoutSpec > 0) {
            $reasons[] = "{$hwPassWithoutSpec} test case Hardware berstatus Pass tanpa Batas Lulus / Spec valid (Aturan 7).";
        }
        if ($activeCriticalBugs > 0) {
            $reasons[] = "Terdapat {$activeCriticalBugs} defect Critical/Major yang masih aktif.";
        }
        if ($executedCount > 0 && $passRate < 80) {
            $reasons[] = "Pass Rate ({$passRate}%) di bawah batas minimal 80%.";
        }

        if (!empty($reasons)) {
            $verdict = 'NO GO';
            $verdictClass = 'badge-danger';
            $verdictColor = '#ef4444';
        } elseif ($passRate < 95 || $progress < 100 || $executedWithoutEvidence > 0 || $blockedCount > 0) {
            $verdict = 'CONDITIONAL';
            $verdictClass = 'badge-warning';
            $verdictColor = '#f59e0b';
            if ($passRate < 95) $reasons[] = "Pass Rate ({$passRate}%) belum mencapai target 95%.";
            if ($progress < 100) $reasons[] = "Progress eksekusi ({$progress}%) belum 100% (masih ada {$notRunCount} Not Run).";
            if ($executedWithoutEvidence > 0) $reasons[] = "{$executedWithoutEvidence} test case belum melampirkan Evidence (Aturan 4).";
            if ($blockedCount > 0) $reasons[] = "Terdapat {$blockedCount} test case Blocked.";
        } else {
            $verdict = 'GO';
            $verdictClass = 'badge-success';
            $verdictColor = '#10b981';
            $reasons[] = "Seluruh kriteria Quality Gate terpenuhi (100% Run, Pass Rate ≥ 95%, 0 Bug Blocker).";
        }

        return [
            'verdict'                  => $verdict,
            'verdict_class'            => $verdictClass,
            'verdict_color'            => $verdictColor,
            'reasons'                  => $reasons,
            'total_cases'              => $totalCases,
            'pass_count'               => $passCount,
            'fail_count'               => $failCount,
            'blocked_count'            => $blockedCount,
            'not_run_count'            => $notRunCount,
            'na_count'                 => $naCount,
            'executed_count'           => $executedCount,
            'pass_rate'                => $passRate,
            'progress'                 => $progress,
            'fail_without_bug'         => $failWithoutBug,
            'executed_without_evidence'=> $executedWithoutEvidence,
            'hw_pass_without_spec'     => $hwPassWithoutSpec,
            'active_critical_bugs'     => $activeCriticalBugs,
        ];
    }
}
