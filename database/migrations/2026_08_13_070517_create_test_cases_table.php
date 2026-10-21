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
        Schema::create('test_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            
            // Tipe Test
            $table->enum('test_type', [
                'functional', 
                'integration', 
                'regression', 
                'performance', 
                'reliability', 
                'stress', 
                'negative'
            ]);
            
            $table->string('title');
            $table->enum('priority', ['Low', 'Medium', 'High', 'Critical'])->default('Medium');
            $table->text('precondition')->nullable();
            
            // Dynamic fields untuk field spesifik per tipe test
            $table->json('dynamic_fields')->nullable();
            
            // Field hasil pengujian
            $table->enum('status', ['PASS', 'FAILED', 'BLOCKED'])->default('PASS');
            $table->text('actual_result')->nullable();
            $table->text('notes_qa')->nullable();
            $table->text('notes_programmer')->nullable();
            $table->string('evidence_url')->nullable();
            $table->timestamp('date_tested')->nullable();
            $table->foreignId('tested_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            
            // Index untuk performa
            $table->index(['project_id', 'test_type']);
            $table->index('priority');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_cases');
    }
};
