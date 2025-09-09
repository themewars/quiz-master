<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class HeroDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $setting = Setting::first();
        
        if ($setting) {
            $setting->update([
                'hero_sub_title' => 'Welcome to ExamGenerator AI',
                'hero_title' => 'Create Amazing Exams with AI',
                'hero_description' => 'Generate professional exams, quizzes, and assessments using advanced AI technology. Perfect for educators, trainers, and organizations.',
            ]);
            
            $this->command->info('Hero section data updated successfully!');
        } else {
            Setting::create([
                'app_name' => 'ExamGenerator AI',
                'email' => 'admin@gmail.com',
                'contact' => '',
                'prefix_code' => 'IN',
                'hero_sub_title' => 'Welcome to ExamGenerator AI',
                'hero_title' => 'Create Amazing Exams with AI',
                'hero_description' => 'Generate professional exams, quizzes, and assessments using advanced AI technology. Perfect for educators, trainers, and organizations.',
                'default_language' => 'en',
                'currency_before_amount' => 1,
                'send_mail_verification' => 1,
                'enable_captcha' => 0,
                'enabled_captcha_in_login' => 0,
                'enabled_captcha_in_register' => 0,
                'enabled_captcha_in_exam' => 0,
            ]);
            
            $this->command->info('Settings record created with hero data!');
        }
    }
}
