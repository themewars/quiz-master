<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\Quiz;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ResetUserUsageCounters extends Command
{
    protected $signature = 'usage:reset-counters {--force : Force reset all counters}';
    protected $description = 'Reset usage counters to match actual quiz counts for all users';

    public function handle()
    {
        $this->info('Starting usage counter reset...');
        
<<<<<<< HEAD
=======
        // Check if the required columns exist
        if (!$this->columnsExist()) {
            $this->error('Required columns do not exist. Please run the migration first:');
            $this->line('php artisan migrate');
            return 1;
        }
        
>>>>>>> 6e6a904f14e0a396dda1604dde54cd28910c35a2
        $subscriptions = Subscription::where('status', 'active')->get();
        $resetCount = 0;
        
        foreach ($subscriptions as $subscription) {
            // Count actual quizzes created by user
            $actualQuizCount = Quiz::where('user_id', $subscription->user_id)->count();
            
            // Get current counter value
            $currentCounter = $subscription->exams_generated_this_month ?? 0;
            
            // Only reset if there's a mismatch or force flag is used
            if ($this->option('force') || $currentCounter !== $actualQuizCount) {
                $subscription->update([
                    'exams_generated_this_month' => $actualQuizCount,
                    'questions_generated_this_month' => 0, // Reset questions counter too
                    'usage_reset_date' => Carbon::now()->addMonth(),
                ]);
                
                $resetCount++;
                $this->line("User {$subscription->user_id}: Reset from {$currentCounter} to {$actualQuizCount} exams");
            }
        }
        
        $this->info("Reset completed! {$resetCount} users updated.");
        return 0;
    }
<<<<<<< HEAD
=======
    
    private function columnsExist(): bool
    {
        try {
            $subscription = Subscription::first();
            if ($subscription) {
                // Try to access the column
                $subscription->exams_generated_this_month;
                return true;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
>>>>>>> 6e6a904f14e0a396dda1604dde54cd28910c35a2
}
