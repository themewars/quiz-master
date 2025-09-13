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
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->integer('exams_generated_this_month')->default(0)->after('ends_at');
            $table->integer('questions_generated_this_month')->default(0)->after('exams_generated_this_month');
            $table->timestamp('usage_reset_date')->nullable()->after('questions_generated_this_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['exams_generated_this_month', 'questions_generated_this_month', 'usage_reset_date']);
        });
    }
};
