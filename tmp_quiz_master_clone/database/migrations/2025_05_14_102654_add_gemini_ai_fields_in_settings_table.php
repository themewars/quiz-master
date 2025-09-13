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
        Schema::table('settings', function (Blueprint $table) {
            $table->integer('ai_type')->default(1);
            $table->text('gemini_api_key')->nullable();
            $table->text('gemini_ai_model')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('ai_type');
            $table->dropColumn('gemini_ai_api_key');
            $table->dropColumn('gemini_ai_model');
        });
    }
};
