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
        Schema::create('test_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_case_id')->constrained()->onDelete('cascade');
            $table->integer('step_order')->default(1);
            $table->text('step_description');
            $table->text('expected_result');
            $table->timestamps();
            
            // Index untuk performa
            $table->index(['test_case_id', 'step_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_steps');
    }
};
