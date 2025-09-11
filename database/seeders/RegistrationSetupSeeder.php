<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Currency;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RegistrationSetupSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('🔧 Setting up registration system...');
        
        // Create roles if they don't exist
        $this->createRoles();
        
        // Create default currency if it doesn't exist
        $this->createDefaultCurrency();
        
        // Create default plan if it doesn't exist
        $this->createDefaultPlan();
        
        $this->command->info('✅ Registration system setup completed!');
    }

    private function createRoles()
    {
        $this->command->info('👥 Creating user roles...');
        
        $roles = [
            ['name' => User::USER_ROLE, 'guard_name' => 'web'],
            ['name' => User::ADMIN_ROLE, 'guard_name' => 'web'],
        ];
        
        foreach ($roles as $roleData) {
            Role::firstOrCreate(
                ['name' => $roleData['name']],
                $roleData
            );
        }
        
        $this->command->info('✅ Roles created/verified');
    }

    private function createDefaultCurrency()
    {
        $this->command->info('💰 Creating default currency...');
        
        $currency = Currency::firstOrCreate(
            ['code' => 'USD'],
            [
                'name' => 'US Dollar',
                'symbol' => '$',
                'code' => 'USD',
                'status' => true,
            ]
        );
        
        $this->command->info("✅ Currency created/verified: {$currency->name}");
        
        return $currency;
    }

    private function createDefaultPlan()
    {
        $this->command->info('📋 Creating default plan...');
        
        // Check if default plan already exists
        $existingDefaultPlan = Plan::where('assign_default', true)->first();
        
        if ($existingDefaultPlan) {
            $this->command->info("✅ Default plan already exists: {$existingDefaultPlan->name}");
            return;
        }
        
        // Get or create USD currency
        $currency = Currency::where('code', 'USD')->first();
        if (!$currency) {
            $currency = $this->createDefaultCurrency();
        }
        
        // Create default free plan
        $plan = Plan::create([
            'name' => 'Free Plan',
            'description' => 'Basic free plan for new users with limited features',
            'frequency' => 1, // Monthly
            'no_of_exam' => 5,
            'price' => 0.00,
            'trial_days' => 7,
            'assign_default' => true,
            'status' => true,
            'currency_id' => $currency->id,
            'exams_per_month' => 5,
            'max_questions_per_exam' => 20,
            'max_questions_per_month' => 100,
            'pdf_export_enabled' => false,
            'word_export_enabled' => false,
            'website_quiz_enabled' => false,
            'pdf_to_exam_enabled' => false,
            'ppt_quiz_enabled' => false,
            'answer_key_enabled' => true,
            'white_label_enabled' => false,
            'watermark_enabled' => true,
            'priority_support_enabled' => false,
            'multi_teacher_enabled' => false,
            'allowed_question_types' => ['mcq'],
            'badge_text' => 'Free',
        ]);
        
        $this->command->info("✅ Default plan created: {$plan->name}");
    }
}
