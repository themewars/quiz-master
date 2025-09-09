<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Setting;

echo "Updating hero section data...\n";

$setting = Setting::first();

if ($setting) {
    $setting->update([
        'hero_sub_title' => 'Welcome to ExamGenerator AI',
        'hero_title' => 'Create Amazing Exams with AI',
        'hero_description' => 'Generate professional exams, quizzes, and assessments using advanced AI technology. Perfect for educators, trainers, and organizations.',
    ]);
    
    echo "Hero section data updated successfully!\n";
    echo "Hero Title: " . $setting->hero_title . "\n";
    echo "Hero Sub Title: " . $setting->hero_sub_title . "\n";
    echo "Hero Description: " . $setting->hero_description . "\n";
} else {
    echo "No settings record found!\n";
}
