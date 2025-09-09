<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing settings record with hero data
        DB::table('settings')
            ->where('id', 1)
            ->update([
                'hero_sub_title' => 'Welcome to ExamGenerator AI',
                'hero_title' => 'Create Amazing Exams with AI',
                'hero_description' => 'Generate professional exams, quizzes, and assessments using advanced AI technology. Perfect for educators, trainers, and organizations.',
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove hero data
        DB::table('settings')
            ->where('id', 1)
            ->update([
                'hero_sub_title' => null,
                'hero_title' => null,
                'hero_description' => null,
                'updated_at' => now(),
            ]);
    }
};
