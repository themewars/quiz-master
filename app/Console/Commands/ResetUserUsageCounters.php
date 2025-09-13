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
}
