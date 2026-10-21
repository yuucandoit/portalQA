<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Projects: Tambah project_code (Kode Buckleup, misal: NEWM2, ICCS)
        Schema::table('projects', function (Blueprint $table) {
            $table->string('project_code', 50)->nullable()->after('id');
        });

        // 2. Test Reports: Tambah sprint dan cycle
        Schema::table('test_reports', function (Blueprint $table) {
            $table->string('sprint', 50)->nullable()->after('report_name');
            $table->string('cycle', 50)->nullable()->default('Siklus 1')->after('sprint');
        });

        // 3. Test Cases: Tambah test_case_id, buckleup_ticket, bug_ticket
        Schema::table('test_cases', function (Blueprint $table) {
            $table->string('test_case_id', 100)->nullable()->after('title');
            $table->string('buckleup_ticket', 100)->nullable()->after('notes_programmer');
            $table->string('bug_ticket', 100)->nullable()->after('buckleup_ticket');
        });

        // 4. Normalisasi data status lama ke standar QA-STD-001 (Pass, Fail, Blocked, Not Run, N/A)
        DB::table('test_cases')->where('status', 'PASS')->update(['status' => 'Pass']);
        DB::table('test_cases')->where('status', 'FAILED')->update(['status' => 'Fail']);
        DB::table('test_cases')->where('status', 'BLOCKED')->update(['status' => 'Blocked']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('project_code');
        });

        Schema::table('test_reports', function (Blueprint $table) {
            $table->dropColumn(['sprint', 'cycle']);
        });

        Schema::table('test_cases', function (Blueprint $table) {
            $table->dropColumn(['test_case_id', 'buckleup_ticket', 'bug_ticket']);
        });

        DB::table('test_cases')->where('status', 'Pass')->update(['status' => 'PASS']);
        DB::table('test_cases')->where('status', 'Fail')->update(['status' => 'FAILED']);
        DB::table('test_cases')->where('status', 'Blocked')->update(['status' => 'BLOCKED']);
    }
};
