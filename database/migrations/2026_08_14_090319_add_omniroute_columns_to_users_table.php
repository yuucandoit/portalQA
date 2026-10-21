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
            $table->text('omniroute_api_key')->nullable();
            $table->string('omniroute_base_url')->nullable()->default('http://202.155.143.157:20128/v1');
            $table->string('omniroute_model')->nullable()->default('auto/best-coding');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'omniroute_api_key',
                'omniroute_base_url',
                'omniroute_model',
            ]);
        });
    }
};
