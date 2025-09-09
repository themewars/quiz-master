<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Currency;
use Illuminate\Database\Seeder;

class DefaultPlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currency = Currency::first();
        $currencyId = $currency ? $currency->id : 1;

        // Free Plan
        Plan::create([
            'name' => 'Free Plan',
            'description' => 'Perfect for getting started with basic exam generation',
            'frequency' => 1, // Monthly
            'no_of_exam' => 3, // Legacy field
            'price' => 0,
            'trial_days' => null,
            'assign_default' => true,
            'status' => true,
            'currency_id' => $currencyId,
            // New advanced fields
            'exams_per_month' => 3,
            'max_questions_per_exam' => 10,
            'max_questions_per_month' => 30,
            'pdf_export_enabled' => false,
            'word_export_enabled' => false,
            'youtube_quiz_enabled' => false,
            'ppt_quiz_enabled' => false,
            'answer_key_enabled' => false,
            'white_label_enabled' => false,
            'watermark_enabled' => true,
            'priority_support_enabled' => false,
            'multi_teacher_enabled' => false,
            'allowed_question_types' => ['mcq'],
            'badge_text' => null,
            'payment_gateway_plan_id' => null,
        ]);

        // Basic Plan
        Plan::create([
            'name' => 'Basic Plan',
            'description' => 'Great for regular educators with moderate needs',
            'frequency' => 1, // Monthly
            'no_of_exam' => 50, // Legacy field
            'price' => 499,
            'trial_days' => 7,
            'assign_default' => false,
            'status' => true,
            'currency_id' => $currencyId,
            // New advanced fields
            'exams_per_month' => 50,
            'max_questions_per_exam' => 30,
            'max_questions_per_month' => 1500,
            'pdf_export_enabled' => true,
            'word_export_enabled' => false,
            'youtube_quiz_enabled' => false,
            'ppt_quiz_enabled' => false,
            'answer_key_enabled' => false,
            'white_label_enabled' => false,
            'watermark_enabled' => false,
            'priority_support_enabled' => false,
            'multi_teacher_enabled' => false,
            'allowed_question_types' => ['mcq', 'short_answer'],
            'badge_text' => null,
            'payment_gateway_plan_id' => null,
        ]);

        // Pro Plan
        Plan::create([
            'name' => 'Pro Plan',
            'description' => 'Advanced features for professional educators and institutions',
            'frequency' => 1, // Monthly
            'no_of_exam' => -1, // Legacy field (unlimited)
            'price' => 999,
            'trial_days' => 14,
            'assign_default' => false,
            'status' => true,
            'currency_id' => $currencyId,
            // New advanced fields
            'exams_per_month' => -1, // Unlimited
            'max_questions_per_exam' => 50,
            'max_questions_per_month' => -1, // Unlimited
            'pdf_export_enabled' => true,
            'word_export_enabled' => true,
            'youtube_quiz_enabled' => true,
            'ppt_quiz_enabled' => true,
            'answer_key_enabled' => true,
            'white_label_enabled' => false,
            'watermark_enabled' => false,
            'priority_support_enabled' => true,
            'multi_teacher_enabled' => false,
            'allowed_question_types' => ['mcq', 'short_answer', 'long_answer', 'true_false'],
            'badge_text' => 'Recommended',
            'payment_gateway_plan_id' => null,
        ]);

        // Enterprise Plan
        Plan::create([
            'name' => 'Enterprise Plan',
            'description' => 'Complete solution for large organizations and institutions',
            'frequency' => 1, // Monthly
            'no_of_exam' => -1, // Legacy field (unlimited)
            'price' => 4999,
            'trial_days' => 30,
            'assign_default' => false,
            'status' => true,
            'currency_id' => $currencyId,
            // New advanced fields
            'exams_per_month' => -1, // Unlimited
            'max_questions_per_exam' => 100,
            'max_questions_per_month' => -1, // Unlimited
            'pdf_export_enabled' => true,
            'word_export_enabled' => true,
            'youtube_quiz_enabled' => true,
            'ppt_quiz_enabled' => true,
            'answer_key_enabled' => true,
            'white_label_enabled' => true,
            'watermark_enabled' => false,
            'priority_support_enabled' => true,
            'multi_teacher_enabled' => true,
            'allowed_question_types' => ['mcq', 'short_answer', 'long_answer', 'true_false', 'fill_blank'],
            'badge_text' => 'Most Popular',
            'payment_gateway_plan_id' => null,
        ]);
    }
}
