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
            // Usage limits
            if (!Schema::hasColumn('plans', 'exams_per_month')) {
                $table->integer('exams_per_month')->default(0)->after('status');
            }
            if (!Schema::hasColumn('plans', 'max_questions_per_exam')) {
                $table->integer('max_questions_per_exam')->default(0)->after('exams_per_month');
            }
            if (!Schema::hasColumn('plans', 'max_questions_per_month')) {
                $table->integer('max_questions_per_month')->default(0)->after('max_questions_per_exam');
            }

            // Feature toggles
            if (!Schema::hasColumn('plans', 'pdf_export_enabled')) {
                $table->boolean('pdf_export_enabled')->default(false)->after('max_questions_per_month');
            }
            if (!Schema::hasColumn('plans', 'word_export_enabled')) {
                $table->boolean('word_export_enabled')->default(false)->after('pdf_export_enabled');
            }
            if (!Schema::hasColumn('plans', 'ppt_quiz_enabled')) {
                $table->boolean('ppt_quiz_enabled')->default(false)->after('word_export_enabled');
            }
            if (!Schema::hasColumn('plans', 'answer_key_enabled')) {
                $table->boolean('answer_key_enabled')->default(false)->after('ppt_quiz_enabled');
            }
            if (!Schema::hasColumn('plans', 'white_label_enabled')) {
                $table->boolean('white_label_enabled')->default(false)->after('answer_key_enabled');
            }
            if (!Schema::hasColumn('plans', 'watermark_enabled')) {
                $table->boolean('watermark_enabled')->default(false)->after('white_label_enabled');
            }
            if (!Schema::hasColumn('plans', 'priority_support_enabled')) {
                $table->boolean('priority_support_enabled')->default(false)->after('watermark_enabled');
            }
            if (!Schema::hasColumn('plans', 'multi_teacher_enabled')) {
                $table->boolean('multi_teacher_enabled')->default(false)->after('priority_support_enabled');
            }

            // Options
            if (!Schema::hasColumn('plans', 'allowed_question_types')) {
                $table->json('allowed_question_types')->nullable()->after('multi_teacher_enabled');
            }
            if (!Schema::hasColumn('plans', 'badge_text')) {
                $table->string('badge_text')->nullable()->after('allowed_question_types');
            }
            if (!Schema::hasColumn('plans', 'payment_gateway_plan_id')) {
                $table->string('payment_gateway_plan_id')->nullable()->after('badge_text');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $columns = [
                'exams_per_month',
                'max_questions_per_exam',
                'max_questions_per_month',
                'pdf_export_enabled',
                'word_export_enabled',
                'ppt_quiz_enabled',
                'answer_key_enabled',
                'white_label_enabled',
                'watermark_enabled',
                'priority_support_enabled',
                'multi_teacher_enabled',
                'allowed_question_types',
                'badge_text',
                'payment_gateway_plan_id',
            ];

            foreach ($columns as $col) {
                if (Schema::hasColumn('plans', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
