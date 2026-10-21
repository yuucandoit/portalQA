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
        Schema::create('test_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_case_id')->constrained()->onDelete('cascade');
            $table->foreignId('executed_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Status dan hasil eksekusi
            $table->enum('status', ['PASS', 'FAILED', 'BLOCKED', 'SKIPPED'])->default('PASS');
            $table->enum('test_result', ['PASSED', 'FAILED', 'BLOCKED', 'NOT EXECUTED'])->default('PASSED');
            $table->text('actual_result')->nullable();
            $table->text('notes_qa')->nullable();
            $table->text('notes_programmer')->nullable();
            $table->string('evidence_url')->nullable();
            $table->timestamp('date_tested')->nullable();
            
            // Tracking eksekusi
            $table->integer('execution_number')->default(1);
            $table->text('environment_info')->nullable();
            
            $table->timestamps();
            
            // Index untuk performa
            $table->index(['test_case_id', 'execution_number']);
            $table->index('date_tested');
            $table->index('status');
            $table->index('executed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_executions');
    }
};
