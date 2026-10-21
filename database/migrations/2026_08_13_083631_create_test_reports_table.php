<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');

            $table->string('report_name');
            $table->enum('test_type', [
                'functional',
                'integration',
                'regression',
                'performance',
                'reliability',
                'stress',
                'negative',
            ]);
            $table->date('report_date');
            $table->text('description')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['project_id', 'test_type']);
            $table->index('report_date');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_reports');
    }
};
