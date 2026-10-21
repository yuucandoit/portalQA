<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('test_cases', function (Blueprint $table) {
            $table->boolean('is_ai_generated')->default(false)->after('evidence_url');
            $table->string('review_status', 50)->default('reviewed')->after('is_ai_generated'); // 'needs_review' or 'reviewed'
        });

        Schema::table('test_steps', function (Blueprint $table) {
            $table->text('actual_result')->nullable()->after('expected_result');
            $table->string('status', 50)->nullable()->default('Not Executed')->after('actual_result');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('test_cases', function (Blueprint $table) {
            $table->dropColumn(['is_ai_generated', 'review_status']);
        });

        Schema::table('test_steps', function (Blueprint $table) {
            $table->dropColumn(['actual_result', 'status']);
        });
    }
};
