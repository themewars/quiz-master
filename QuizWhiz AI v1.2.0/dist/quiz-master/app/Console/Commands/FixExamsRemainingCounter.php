<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\Quiz;
use App\Services\PlanValidationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixExamsRemainingCounter extends Command
{
    protected $signature = 'fix:exams-remaining-counter {--user-id= : Fix for specific user ID}';
    protected $description = 'Fix exams remaining counter for all users or specific user';

    public function handle()
    {
        $this->info('🔧 Starting Exams Remaining Counter Fix...');
        
        try {
            DB::beginTransaction();
            
            $userId = $this->option('user-id');
            
            if ($userId) {
                $this->fixUserCounters($userId);
            } else {
                $this->fixAllUsersCounters();
            }
            
            DB::commit();
            $this->info('✅ Exams remaining counter fix completed successfully!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Error fixing counters: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
    
    private function fixUserCounters($userId)
    {
        $this->info("🔍 Fixing counters for user ID: {$userId}");
        
        $subscription = Subscription::where('user_id', $userId)
            ->where('status', 1) // ACTIVE
            ->first();
            
        if (!$subscription) {
            $this->warn("⚠️  No active subscription found for user {$userId}");
            return;
        }
        
        $this->updateUsageCounters($subscription);
        $this->info("✅ Fixed counters for user {$userId}");
    }
    
    private function fixAllUsersCounters()
    {
        $this->info('🔍 Fixing counters for all users...');
        
        $subscriptions = Subscription::where('status', 1) // ACTIVE
            ->with(['user', 'plan'])
            ->get();
            
        $this->info("📊 Found {$subscriptions->count()} active subscriptions");
        
        $progressBar = $this->output->createProgressBar($subscriptions->count());
        $progressBar->start();
        
        foreach ($subscriptions as $subscription) {
            $this->updateUsageCounters($subscription);
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine();
    }
    
    private function updateUsageCounters($subscription)
    {
        try {
            // Count actual quizzes created by user
            $actualQuizCount = Quiz::where('user_id', $subscription->user_id)->count();
            
            // Get current usage from subscription
            $currentUsage = $subscription->exams_generated_this_month ?? 0;
            
            // Update subscription with correct usage
            $subscription->update([
                'exams_generated_this_month' => $actualQuizCount,
                'questions_generated_this_month' => $subscription->questions_generated_this_month ?? 0,
            ]);
            
            // Log the fix
            \Log::info('Fixed usage counters', [
                'user_id' => $subscription->user_id,
                'subscription_id' => $subscription->id,
                'old_usage' => $currentUsage,
                'new_usage' => $actualQuizCount,
                'plan_name' => $subscription->plan->name ?? 'Unknown'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error updating usage counters', [
                'user_id' => $subscription->user_id,
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
