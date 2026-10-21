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
            $table->text('openai_api_key')->nullable()->after('omniroute_model');
            $table->string('openai_base_url')->nullable()->default('https://api.openai.com/v1')->after('openai_api_key');
            $table->string('openai_model')->nullable()->default('gpt-4o-mini')->after('openai_base_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'openai_api_key',
                'openai_base_url',
                'openai_model',
            ]);
        });
    }
};
