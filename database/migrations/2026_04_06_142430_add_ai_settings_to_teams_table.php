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
        Schema::table('teams', function (Blueprint $table) {
            $table->text('openai_api_key_encrypted')->nullable()->after('currency');
            $table->string('openai_api_key_last4', 4)->nullable()->after('openai_api_key_encrypted');
            $table->string('ai_provider')->default('openai')->after('openai_api_key_last4');
            $table->string('ai_model')->default('gpt-4.1-mini')->after('ai_provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn([
                'openai_api_key_encrypted',
                'openai_api_key_last4',
                'ai_provider',
                'ai_model',
            ]);
        });
    }
};
