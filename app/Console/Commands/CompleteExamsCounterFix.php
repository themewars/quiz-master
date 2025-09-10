<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\Quiz;
use App\Models\Plan;
use App\Services\PlanValidationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CompleteExamsCounterFix extends Command
{
    protected $signature = 'fix:complete-exams-counter {--force : Force fix all users}';
    protected $description = 'Complete fix for exams remaining counter issues';

    public function handle()
    {
        $this->info('🔧 Starting Complete Exams Counter Fix...');
        $this->newLine();
        
        try {
            DB::beginTransaction();
            
            // Step 1: Check and create missing columns
            $this->checkAndCreateColumns();
            
            // Step 2: Fix all subscription counters
            $this->fixAllCounters();
            
            // Step 3: Verify fixes
            $this->verifyFixes();
            
            DB::commit();
            $this->info('✅ Complete fix applied successfully!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Error applying fix: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
    
    private function checkAndCreateColumns()
    {
        $this->info('📊 Step 1: Checking Database Schema...');
        
        $requiredColumns = [
            'exams_generated_this_month' => 'integer',
            'questions_generated_this_month' => 'integer', 
            'usage_reset_date' => 'timestamp'
        ];
        
        $missingColumns = [];
        foreach ($requiredColumns as $column => $type) {
            if (!Schema::hasColumn('subscriptions', $column)) {
                $missingColumns[$column] = $type;
            }
        }
        
        if (empty($missingColumns)) {
            $this->info('✅ All required columns exist');
        } else {
            $this->warn('⚠️  Creating missing columns...');
            
            foreach ($missingColumns as $column => $type) {
                try {
                    if ($type === 'integer') {
                        DB::statement("ALTER TABLE subscriptions ADD COLUMN {$column} INT DEFAULT 0");
                    } elseif ($type === 'timestamp') {
                        DB::statement("ALTER TABLE subscriptions ADD COLUMN {$column} TIMESTAMP NULL");
                    }
                    $this->line("   ✅ Added column: {$column}");
                } catch (\Exception $e) {
                    $this->error("   ❌ Failed to add column {$column}: {$e->getMessage()}");
                    throw $e;
                }
            }
        }
        
        $this->newLine();
    }
    
    private function fixAllCounters()
    {
        $this->info('🔧 Step 2: Fixing Usage Counters...');
        
        $subscriptions = Subscription::where('status', 1) // ACTIVE
            ->with(['user', 'plan'])
            ->get();
            
        if ($subscriptions->isEmpty()) {
            $this->warn('⚠️  No active subscriptions found');
            return;
        }
        
        $this->info("📊 Processing {$subscriptions->count()} active subscriptions");
        
        $progressBar = $this->output->createProgressBar($subscriptions->count());
        $progressBar->start();
        
        $fixedCount = 0;
        foreach ($subscriptions as $subscription) {
            try {
                $this->fixSubscriptionCounter($subscription);
                $fixedCount++;
            } catch (\Exception $e) {
                \Log::error('Error fixing subscription counter', [
                    'subscription_id' => $subscription->id,
                    'user_id' => $subscription->user_id,
                    'error' => $e->getMessage()
                ]);
            }
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine();
        $this->info("✅ Fixed {$fixedCount} subscriptions");
        $this->newLine();
    }
    
    private function fixSubscriptionCounter($subscription)
    {
        // Count actual quizzes created by user
        $actualQuizCount = Quiz::where('user_id', $subscription->user_id)->count();
        
        // Count actual questions created by user
        $actualQuestionCount = DB::table('questions')
            ->join('quizzes', 'questions.quiz_id', '=', 'quizzes.id')
            ->where('quizzes.user_id', $subscription->user_id)
            ->count();
        
        // Update subscription with correct usage
        $subscription->update([
            'exams_generated_this_month' => $actualQuizCount,
            'questions_generated_this_month' => $actualQuestionCount,
            'usage_reset_date' => now()->addMonth(),
        ]);
        
        // Log the fix
        \Log::info('Fixed subscription counter', [
            'user_id' => $subscription->user_id,
            'subscription_id' => $subscription->id,
            'exams_count' => $actualQuizCount,
            'questions_count' => $actualQuestionCount,
            'plan_name' => $subscription->plan->name ?? 'Unknown'
        ]);
    }
    
    private function verifyFixes()
    {
        $this->info('🔍 Step 3: Verifying Fixes...');
        
        $subscriptions = Subscription::where('status', 1)->get();
        $verifiedCount = 0;
        $issueCount = 0;
        
        foreach ($subscriptions as $subscription) {
            $actualQuizCount = Quiz::where('user_id', $subscription->user_id)->count();
            $counterValue = $subscription->exams_generated_this_month ?? 0;
            
            if ($counterValue === $actualQuizCount) {
                $verifiedCount++;
            } else {
                $issueCount++;
                $this->warn("   ⚠️  User {$subscription->user_id}: Still has mismatch ({$counterValue} vs {$actualQuizCount})");
            }
        }
        
        $this->info("✅ Verified: {$verifiedCount} correct, {$issueCount} still have issues");
        
        if ($issueCount > 0) {
            $this->warn('⚠️  Some issues remain. You may need to run this command again.');
        }
        
        $this->newLine();
    }
}
