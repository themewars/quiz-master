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
        // Get default currency
        $currency = Currency::first();
        if (!$currency) {
            $this->command->error('No currency found. Please run currency seeder first.');
            return;
        }

        // Clear existing plans
        Plan::truncate();

        // Create Free Plan
        Plan::create([
            'name' => 'Free Plan',
            'description' => 'Perfect for getting started with basic exam creation',
            'frequency' => 1, // Monthly
            'no_of_exam' => 3,
            'price' => 0,
            'trial_days' => 0,
            'assign_default' => true,
            'status' => true,
            'currency_id' => $currency->id,
            'exams_per_month' => 3,
            'max_questions_per_exam' => 10,
            'max_questions_per_month' => 30,
            'pdf_export_enabled' => false,
            'word_export_enabled' => false,
            'website_quiz_enabled' => false,
            'pdf_to_exam_enabled' => false,
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

        // Create Basic Plan
        Plan::create([
            'name' => 'Basic Plan',
            'description' => 'Great for individual educators and small teams',
            'frequency' => 1, // Monthly
            'no_of_exam' => 20,
            'price' => 9.99,
            'trial_days' => 7,
            'assign_default' => false,
            'status' => true,
            'currency_id' => $currency->id,
            'exams_per_month' => 20,
            'max_questions_per_exam' => 25,
            'max_questions_per_month' => 500,
            'pdf_export_enabled' => true,
            'word_export_enabled' => false,
            'website_quiz_enabled' => true,
            'pdf_to_exam_enabled' => true,
            'ppt_quiz_enabled' => false,
            'answer_key_enabled' => true,
            'white_label_enabled' => false,
            'watermark_enabled' => true,
            'priority_support_enabled' => false,
            'multi_teacher_enabled' => false,
            'allowed_question_types' => ['mcq', 'short_answer', 'true_false'],
            'badge_text' => null,
            'payment_gateway_plan_id' => 'basic_monthly',
        ]);

        // Create Pro Plan
        Plan::create([
            'name' => 'Pro Plan',
            'description' => 'Advanced features for professional educators and institutions',
            'frequency' => 1, // Monthly
            'no_of_exam' => 100,
            'price' => 29.99,
            'trial_days' => 14,
            'assign_default' => false,
            'status' => true,
            'currency_id' => $currency->id,
            'exams_per_month' => 100,
            'max_questions_per_exam' => 50,
            'max_questions_per_month' => 5000,
            'pdf_export_enabled' => true,
            'word_export_enabled' => true,
            'website_quiz_enabled' => true,
            'pdf_to_exam_enabled' => true,
            'ppt_quiz_enabled' => true,
            'answer_key_enabled' => true,
            'white_label_enabled' => true,
            'watermark_enabled' => false,
            'priority_support_enabled' => true,
            'multi_teacher_enabled' => false,
            'allowed_question_types' => ['mcq', 'short_answer', 'long_answer', 'true_false', 'fill_blank'],
            'badge_text' => 'Popular',
            'payment_gateway_plan_id' => 'pro_monthly',
        ]);

        // Create Enterprise Plan
        Plan::create([
            'name' => 'Enterprise Plan',
            'description' => 'Unlimited everything for large organizations and universities',
            'frequency' => 1, // Monthly
            'no_of_exam' => -1, // Unlimited
            'price' => 99.99,
            'trial_days' => 30,
            'assign_default' => false,
            'status' => true,
            'currency_id' => $currency->id,
            'exams_per_month' => -1, // Unlimited
            'max_questions_per_exam' => -1, // Unlimited
            'max_questions_per_month' => -1, // Unlimited
            'pdf_export_enabled' => true,
            'word_export_enabled' => true,
            'website_quiz_enabled' => true,
            'pdf_to_exam_enabled' => true,
            'ppt_quiz_enabled' => true,
            'answer_key_enabled' => true,
            'white_label_enabled' => true,
            'watermark_enabled' => false,
            'priority_support_enabled' => true,
            'multi_teacher_enabled' => true,
            'allowed_question_types' => ['mcq', 'short_answer', 'long_answer', 'true_false', 'fill_blank'],
            'badge_text' => 'Best Value',
            'payment_gateway_plan_id' => 'enterprise_monthly',
        ]);

        $this->command->info('Default plans created successfully!');
        $this->command->info('Free Plan: 3 exams/month, 10 questions/exam');
        $this->command->info('Basic Plan: 20 exams/month, 25 questions/exam, $9.99/month');
        $this->command->info('Pro Plan: 100 exams/month, 50 questions/exam, $29.99/month (Popular)');
        $this->command->info('Enterprise Plan: Unlimited everything, $99.99/month (Best Value)');
    }
}
