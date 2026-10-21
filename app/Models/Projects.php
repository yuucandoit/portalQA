<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Projects extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'projects';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_code',
        'name',
        'version',
        'description',
    ];

    /**
     * Get formatted project name with Buckleup code: [Kode]_[Nama] or [Nama]
     */
    public function getFormattedNameAttribute(): string
    {
        return $this->project_code ? "{$this->project_code}_{$this->name}" : $this->name;
    }

    /**
     * Get all test cases for the project.
     */
    public function testCases(): HasMany
    {
        return $this->hasMany(TestCase::class, 'project_id');
    }

    /**
     * Get all bugs logged for the project.
     */
    public function bugs(): HasMany
    {
        return $this->hasMany(Bug::class, 'project_id');
    }

    /**
     * Get active test cases (not deleted).
     */
    public function activeTestCases(): HasMany
    {
        return $this->hasMany(TestCase::class, 'project_id')->where('status', '!=', 'ARCHIVED');
    }

    /**
     * Get test cases statistics.
     */
    public function getStatsAttribute(): array
    {
        $total = $this->testCases()->count();
        $pass = $this->testCases()->whereIn('status', ['Pass', 'PASS'])->count();
        $failed = $this->testCases()->whereIn('status', ['Fail', 'FAILED'])->count();
        $blocked = $this->testCases()->whereIn('status', ['Blocked', 'BLOCKED'])->count();
        $notRun = $this->testCases()->whereIn('status', ['Not Run', 'NOT_RUN'])->count();
        $na = $this->testCases()->whereIn('status', ['N/A', 'NA'])->count();
        $passRate = $total > 0 ? round(($pass / $total) * 100) : 0;

        return [
            'total' => $total,
            'pass' => $pass,
            'failed' => $failed,
            'blocked' => $blocked,
            'not_run' => $notRun,
            'na' => $na,
            'pass_rate' => $passRate,
        ];
    }

    /**
     * Scope a query to search projects.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where('name', 'LIKE', "%{$search}%")
                     ->orWhere('project_code', 'LIKE', "%{$search}%")
                     ->orWhere('version', 'LIKE', "%{$search}%")
                     ->orWhere('description', 'LIKE', "%{$search}%");
    }
}

// Alias for compatibility
if (!class_exists('App\Models\Project')) {
    class_alias(Projects::class, 'App\Models\Project');
}