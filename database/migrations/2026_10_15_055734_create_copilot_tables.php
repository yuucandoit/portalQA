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
        Schema::create('copilot_chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('report_id')->nullable()->constrained('test_reports')->nullOnDelete();
            $table->string('title')->default('Percakapan Baru');
            $table->timestamps();
        });

        Schema::create('copilot_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained('copilot_chats')->cascadeOnDelete();
            $table->string('role', 20)->default('user'); // user | assistant
            $table->longText('content');
            $table->json('draft_payload')->nullable();
            $table->string('action_type', 50)->nullable();
            $table->string('provider_used', 50)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('copilot_messages');
        Schema::dropIfExists('copilot_chats');
    }
};
