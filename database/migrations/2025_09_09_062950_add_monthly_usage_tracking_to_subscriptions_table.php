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
            // Monthly usage tracking
            $table->integer('exams_generated_this_month')->default(0)->after('payment_type')->comment('Number of exams generated this month');
            $table->integer('questions_generated_this_month')->default(0)->after('exams_generated_this_month')->comment('Number of questions generated this month');
            $table->date('usage_reset_date')->nullable()->after('questions_generated_this_month')->comment('Date when usage counters should reset');
            
            // Plan feature usage tracking
            $table->json('feature_usage')->nullable()->after('usage_reset_date')->comment('JSON object tracking feature usage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'exams_generated_this_month',
                'questions_generated_this_month',
                'usage_reset_date',
                'feature_usage',
            ]);
        });
    }
};