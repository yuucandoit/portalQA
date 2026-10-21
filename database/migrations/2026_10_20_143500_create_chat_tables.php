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
        // 1. Chat Channels (Public, Private, Direct, Broadcast)
        Schema::create('chat_channels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->default('public_channel'); // public_channel, private_group, direct, broadcast
            $table->string('topic')->nullable();
            $table->text('description')->nullable();
            $table->string('avatar')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
        });

        // 2. Channel Memberships
        Schema::create('chat_channel_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('chat_channels')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('member'); // owner, admin, member
            $table->boolean('is_muted')->default(false);
            $table->unsignedBigInteger('last_read_message_id')->nullable();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();

            $table->unique(['channel_id', 'user_id']);
        });

        // 3. Messages (with Threading, Quoting, Attachments & Pins)
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('chat_channels')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('parent_id')->nullable()->index(); // Thread parent message
            $table->unsignedBigInteger('reply_to_id')->nullable()->index(); // Quoted message in main flow
            $table->text('message')->nullable();
            $table->string('message_type')->default('text'); // text, image, video, audio, document, sticker, gif, system
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('file_mime')->nullable();
            $table->integer('file_duration')->nullable(); // For audio voice notes / videos
            $table->boolean('is_pinned')->default(false)->index();
            $table->timestamp('pinned_at')->nullable();
            $table->foreignId('pinned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_edited')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // 4. Emoji Reactions
        Schema::create('chat_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('chat_messages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('emoji');
            $table->timestamps();

            $table->unique(['message_id', 'user_id', 'emoji']);
        });

        // 5. Message Delivery & Read Receipts
        Schema::create('chat_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('chat_messages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('delivered'); // delivered, read
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['message_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_receipts');
        Schema::dropIfExists('chat_reactions');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_channel_members');
        Schema::dropIfExists('chat_channels');
    }
};

