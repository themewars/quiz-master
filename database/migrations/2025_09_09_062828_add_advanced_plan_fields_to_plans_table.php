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
        Schema::table('plans', function (Blueprint $table) {
            // Exam limits
            $table->integer('exams_per_month')->default(3)->after('no_of_exam')->comment('Number of exams allowed per month (-1 = unlimited)');
            $table->integer('max_questions_per_exam')->default(10)->after('exams_per_month')->comment('Maximum questions per exam');
            $table->integer('max_questions_per_month')->nullable()->after('max_questions_per_exam')->comment('Total questions per month limit (optional)');
            
            // Feature toggles
            $table->boolean('pdf_export_enabled')->default(false)->after('max_questions_per_month');
            $table->boolean('word_export_enabled')->default(false)->after('pdf_export_enabled');
            $table->boolean('youtube_quiz_enabled')->default(false)->after('word_export_enabled');
            $table->boolean('ppt_quiz_enabled')->default(false)->after('youtube_quiz_enabled');
            $table->boolean('answer_key_enabled')->default(false)->after('ppt_quiz_enabled');
            $table->boolean('white_label_enabled')->default(false)->after('answer_key_enabled');
            $table->boolean('watermark_enabled')->default(true)->after('white_label_enabled');
            $table->boolean('priority_support_enabled')->default(false)->after('watermark_enabled');
            $table->boolean('multi_teacher_enabled')->default(false)->after('priority_support_enabled');
            
            // Question type restrictions
            $table->json('allowed_question_types')->nullable()->after('multi_teacher_enabled')->comment('JSON array of allowed question types');
            
            // Plan metadata
            $table->string('badge_text')->nullable()->after('allowed_question_types')->comment('Plan badge text (e.g., "Recommended")');
            $table->string('payment_gateway_plan_id')->nullable()->after('badge_text')->comment('Razorpay/Stripe plan ID');
            
            // Remove old no_of_exam field (keep for backward compatibility for now)
            // $table->dropColumn('no_of_exam');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn([
                'exams_per_month',
                'max_questions_per_exam',
                'max_questions_per_month',
                'pdf_export_enabled',
                'word_export_enabled',
                'youtube_quiz_enabled',
                'ppt_quiz_enabled',
                'answer_key_enabled',
                'white_label_enabled',
                'watermark_enabled',
                'priority_support_enabled',
                'multi_teacher_enabled',
                'allowed_question_types',
                'badge_text',
                'payment_gateway_plan_id',
            ]);
        });
    }
};