<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bug extends Model
{
    use HasFactory;

    /**
     * Severities per QA-TPL-001
     */
    public const SEVERITIES = [
        'Critical' => 'Critical (Sistem crash, data rusak, membahayakan HW/user)',
        'Major'    => 'Major (Fitur utama tidak sesuai, ada workaround sulit)',
        'Minor'    => 'Minor (Fitur sekunder bermasalah / workaround mudah)',
        'Trivial'  => 'Trivial (Typo, alignment, kosmetik)',
    ];

    /**
     * Priorities per QA-TPL-001
     */
    public const PRIORITIES = [
        'P1 - Urgent' => 'P1 - Urgent (Blokir testing/release, segera)',
        'P2 - High'   => 'P2 - High (Harus selesai sebelum release)',
        'P3 - Medium' => 'P3 - Medium (Jika waktu cukup / next release)',
        'P4 - Low'    => 'P4 - Low (Boleh ditunda)',
    ];

    /**
     * Lifecycle Statuses per QA-TPL-001
     */
    public const STATUSES = [
        'Open',
        'In Progress',
        'Fixed',
        'Ready to Retest',
        'Closed',
        'Reopen',
        'Rejected',
        'Deferred',
    ];

    /**
     * Testing Environments
     */
    public const ENVIRONMENTS = [
        'DEV',
        'SIT',
        'UAT',
        'Staging',
        'Production',
        'Lab (HW)',
        'Lapangan (HW)',
    ];

    /**
     * Bug Types
     */
    public const BUG_TYPES = [
        'Functional',
        'UI/UX',
        'Validation',
        'API',
        'Data',
        'Performance',
        'Security',
        'Compatibility',
        'Hardware',
        'Firmware',
        'Integration',
    ];

    protected $fillable = [
        'project_id',
        'report_id',
        'test_case_id',
        'user_id',
        'bug_id',
        'found_date',
        'module_feature',
        'bug_type',
        'title',
        'steps_to_reproduce',
        'expected_result',
        'actual_result',
        'severity',
        'priority',
        'environment',
        'device_environment',
        'evidence_url',
        'related_test_case_id',
        'reported_by',
        'assigned_to_dev',
        'status',
        'fixed_date',
        'dev_notes',
        'retest_date',
        'retested_by',
        'qa_retest_notes',
        'build_version_found',
        'build_version_fix',
    ];

    protected $casts = [
        'found_date'  => 'date',
        'fixed_date'  => 'date',
        'retest_date' => 'date',
    ];

    protected $appends = [
        'aging_days',
        'is_active',
    ];

    /**
     * Project relationship
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Projects::class, 'project_id');
    }

    /**
     * Test Report relationship
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(TestReport::class, 'report_id');
    }

    /**
     * Test Case relationship
     */
    public function testCase(): BelongsTo
    {
        return $this->belongsTo(TestCase::class, 'test_case_id');
    }

    /**
     * Reporter User relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Calculate Aging in Days
     * QA-TPL-001: Aging dihitung dari found_date sampai fixed_date (jika fix),
     * atau sampai hari ini jika masih terbuka/reopen.
     */
    public function getAgingDaysAttribute(): int
    {
        if (!$this->found_date) {
            return 0;
        }

        $endDate = in_array($this->status, ['Closed', 'Fixed']) && $this->fixed_date
            ? Carbon::parse($this->fixed_date)
            : Carbon::today();

        $startDate = Carbon::parse($this->found_date);

        return max(0, $startDate->diffInDays($endDate));
    }

    /**
     * Active bug check
     */
    public function getIsActiveAttribute(): bool
    {
        return in_array($this->status, ['Open', 'In Progress', 'Fixed', 'Ready to Retest', 'Reopen']);
    }

    /**
     * Scope: Active bugs
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['Open', 'In Progress', 'Fixed', 'Ready to Retest', 'Reopen']);
    }

    /**
     * Scope: Closed bugs
     */
    public function scopeClosed($query)
    {
        return $query->where('status', 'Closed');
    }

    /**
     * Generate next Bug ID (Format: BUG-001, BUG-002, ...)
     */
    public static function generateNextBugId($prefix = 'BUG'): string
    {
        $lastBug = self::where('bug_id', 'LIKE', "{$prefix}-%")
            ->orderBy('id', 'desc')
            ->first();

        if (!$lastBug) {
            return "{$prefix}-001";
        }

        if (preg_match('/' . preg_quote($prefix, '/') . '-(\d+)/i', $lastBug->bug_id, $matches)) {
            $nextNum = ((int)$matches[1]) + 1;
            return sprintf('%s-%03d', $prefix, $nextNum);
        }

        return "{$prefix}-00" . (self::count() + 1);
    }
}
