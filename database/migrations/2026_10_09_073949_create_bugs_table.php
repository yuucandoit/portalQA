<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Schema aligned with QA-TPL-001 Master v0.2 Bug List
     */
    public function up(): void
    {
        Schema::create('bugs', function (Blueprint $table) {
            $table->id();
            
            // Relational Keys
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('report_id')->nullable()->constrained('test_reports')->nullOnDelete();
            $table->foreignId('test_case_id')->nullable()->constrained('test_cases')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Core QA-TPL-001 Bug Fields
            $table->string('bug_id', 50)->index(); // e.g. BUG-001
            $table->date('found_date');
            $table->string('module_feature', 255)->nullable(); // Modul / Fitur
            $table->string('bug_type', 50)->default('Functional'); // Functional, UI/UX, Validation, API, etc.
            $table->string('title', 255); // Judul Bug: [Di mana] + [Apa yang salah]
            $table->text('steps_to_reproduce')->nullable(); // Langkah Reproduksi
            $table->text('expected_result')->nullable();
            $table->text('actual_result')->nullable();

            // Classification
            $table->string('severity', 50)->default('Major'); // Critical, Major, Minor, Trivial
            $table->string('priority', 50)->default('P2 - High'); // P1 - Urgent, P2 - High, P3 - Medium, P4 - Low
            $table->string('environment', 50)->default('SIT'); // DEV, SIT, UAT, Staging, Production, Lab (HW), Lapangan (HW)
            $table->string('device_environment', 255)->nullable(); // Device / Browser / Unit ID

            // Tracing & Evidence
            $table->text('evidence_url')->nullable();
            $table->string('related_test_case_id', 100)->nullable()->index(); // e.g. SPC-LOGIN-002, DRN-PWR-001
            $table->string('reported_by', 100)->nullable();

            // Dev & Status Lifecycle
            $table->string('assigned_to_dev', 100)->nullable();
            $table->string('status', 50)->default('Open'); // Open, In Progress, Fixed, Ready to Retest, Closed, Reopen, Rejected, Deferred
            $table->date('fixed_date')->nullable();
            $table->text('dev_notes')->nullable();

            // QA Retest Cycle
            $table->date('retest_date')->nullable();
            $table->string('retested_by', 100)->nullable();
            $table->text('qa_retest_notes')->nullable();

            // Versioning
            $table->string('build_version_found', 100)->nullable();
            $table->string('build_version_fix', 100)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bugs');
    }
};
