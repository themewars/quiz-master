<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\Quiz;
use App\Services\PlanValidationService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class DiagnoseExamsRemaining extends Command
{
    protected $signature = 'exams:diagnose {user_id? : Specific user ID to diagnose}';
    protected $description = 'Diagnose exams remaining calculation for users';

    public function handle()
    {
        $userId = $this->argument('user_id');
        
        if ($userId) {
            $this->diagnoseUser($userId);
        } else {
            $this->diagnoseAllUsers();
        }
        
        return 0;
    }

    private function diagnoseUser($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            $this->error("User with ID {$userId} not found.");
            return;
        }

        $this->info("=== Diagnosing User: {$user->name} (ID: {$userId}) ===");
        
        // Get active subscription
        $subscription = Subscription::where('user_id', $userId)
            ->where('status', 'active')
            ->orderByDesc('id')
            ->first();

        if (!$subscription) {
            $this->warn("No active subscription found for user.");
            return;
        }

        $plan = $subscription->plan;
        $this->info("Current Plan: {$plan->name} (ID: {$plan->id})");
        $this->info("Plan Exams Limit: " . ($plan->exams_per_month ?? $plan->no_of_exam ?? 'Not set'));
        $this->info("Plan Unlimited: " . ($plan->hasUnlimitedExams() ? 'Yes' : 'No'));

        // Get usage data
        $usedExams = $subscription->exams_generated_this_month ?? 0;
        $actualQuizCount = Quiz::where('user_id', $userId)->count();
        
        $this->info("Counter Value: {$usedExams}");
        $this->info("Actual Quiz Count: {$actualQuizCount}");
        
        if ($usedExams !== $actualQuizCount) {
            $this->warn("⚠️  MISMATCH: Counter ({$usedExams}) != Actual Quizzes ({$actualQuizCount})");
        } else {
            $this->info("✅ Counter matches actual quiz count");
        }

        // Test PlanValidationService
        $planValidationService = new PlanValidationService($user);
        $result = $planValidationService->canCreateExam();
        
        $this->info("PlanValidationService Result:");
        $this->info("  Allowed: " . ($result['allowed'] ? 'Yes' : 'No'));
        $this->info("  Message: {$result['message']}");
        $this->info("  Limit: {$result['limit']}");
        $this->info("  Used: {$result['used']}");
        $this->info("  Remaining: {$result['remaining']}");

        // Check billing cycle
        $usageResetDate = $subscription->usage_reset_date ? Carbon::parse($subscription->usage_reset_date) : null;
        if ($usageResetDate) {
            $this->info("Usage Reset Date: {$usageResetDate->format('Y-m-d H:i:s')}");
            if ($usageResetDate->isPast()) {
                $this->warn("⚠️  Usage reset date is in the past - counters should be reset");
            }
        } else {
            $this->warn("⚠️  No usage reset date set");
        }
    }

    private function diagnoseAllUsers()
    {
        $this->info("=== Diagnosing All Users with Active Subscriptions ===");
        
        $subscriptions = Subscription::where('status', 'active')
            ->with(['user', 'plan'])
            ->get();

        $totalUsers = $subscriptions->count();
        $mismatchCount = 0;
        $unlimitedCount = 0;
        $zeroLimitCount = 0;

        foreach ($subscriptions as $subscription) {
            $user = $subscription->user;
            $plan = $subscription->plan;
            
            $usedExams = $subscription->exams_generated_this_month ?? 0;
            $actualQuizCount = Quiz::where('user_id', $user->id)->count();
            
            if ($plan->hasUnlimitedExams()) {
                $unlimitedCount++;
            } elseif (($plan->exams_per_month ?? $plan->no_of_exam ?? 0) <= 0) {
                $zeroLimitCount++;
            }
            
            if ($usedExams !== $actualQuizCount) {
                $mismatchCount++;
                $this->warn("User {$user->id} ({$user->name}): Counter ({$usedExams}) != Quizzes ({$actualQuizCount})");
            }
        }

        $this->info("\n=== Summary ===");
        $this->info("Total Active Users: {$totalUsers}");
        $this->info("Users with Counter Mismatch: {$mismatchCount}");
        $this->info("Users with Unlimited Plans: {$unlimitedCount}");
        $this->info("Users with Zero/No Limit: {$zeroLimitCount}");
        
        if ($mismatchCount > 0) {
            $this->warn("\n⚠️  {$mismatchCount} users have counter mismatches. Run 'php artisan usage:reset-counters --force' to fix.");
        } else {
            $this->info("\n✅ All counters are accurate!");
        }
    }
}
