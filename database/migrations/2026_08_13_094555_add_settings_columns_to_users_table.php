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
        Schema::table('users', function (Blueprint $table) {
            $table->text('gemini_api_key')->nullable();
            $table->text('openrouter_api_key')->nullable();
            $table->string('ollama_base_url')->nullable()->default('http://localhost:11434');
            $table->string('ollama_model')->nullable()->default('llama3.2');
            $table->string('default_ai_provider')->nullable()->default('gemini');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'gemini_api_key',
                'openrouter_api_key',
                'ollama_base_url',
                'ollama_model',
                'default_ai_provider',
            ]);
        });
    }
};
