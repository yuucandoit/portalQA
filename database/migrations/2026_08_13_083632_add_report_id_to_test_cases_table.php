<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('test_cases', function (Blueprint $table) {
            // Add report_id after project_id, nullable so existing rows aren't broken
            $table->foreignId('report_id')
                  ->nullable()
                  ->after('project_id')
                  ->constrained('test_reports')
                  ->onDelete('cascade');

            $table->index('report_id');
        });
    }

    public function down(): void
    {
        Schema::table('test_cases', function (Blueprint $table) {
            $table->dropForeign(['report_id']);
            $table->dropIndex(['report_id']);
            $table->dropColumn('report_id');
        });
    }
};
