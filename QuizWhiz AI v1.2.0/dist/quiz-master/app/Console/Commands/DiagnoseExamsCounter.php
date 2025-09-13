<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\Quiz;
use App\Models\Plan;
use App\Services\PlanValidationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DiagnoseExamsCounter extends Command
{
    protected $signature = 'diagnose:exams-counter {--user-id= : Diagnose specific user ID}';
    protected $description = 'Diagnose exams remaining counter issues';

    public function handle()
    {
        $this->info('🔍 Diagnosing Exams Counter Issues...');
        $this->newLine();
        
        // Check database schema
        $this->checkDatabaseSchema();
        $this->newLine();
        
        // Check specific user or all users
        $userId = $this->option('user-id');
        if ($userId) {
            $this->diagnoseUser($userId);
        } else {
            $this->diagnoseAllUsers();
        }
        
        return 0;
    }
    
    private function checkDatabaseSchema()
    {
        $this->info('📊 Checking Database Schema...');
        
        $requiredColumns = [
            'exams_generated_this_month',
            'questions_generated_this_month', 
            'usage_reset_date'
        ];
        
        $missingColumns = [];
        foreach ($requiredColumns as $column) {
            if (!Schema::hasColumn('subscriptions', $column)) {
                $missingColumns[] = $column;
            }
        }
        
        if (empty($missingColumns)) {
            $this->info('✅ All required columns exist in subscriptions table');
        } else {
            $this->error('❌ Missing columns in subscriptions table:');
            foreach ($missingColumns as $column) {
                $this->line("   - {$column}");
            }
            $this->newLine();
            $this->warn('🔧 Solution: Run the migration:');
            $this->line('   php artisan migrate');
            $this->newLine();
        }
    }
    
    private function diagnoseUser($userId)
    {
        $this->info("🔍 Diagnosing User ID: {$userId}");
        $this->newLine();
        
        $subscription = Subscription::where('user_id', $userId)
            ->where('status', 1) // ACTIVE
            ->with('plan')
            ->first();
            
        if (!$subscription) {
            $this->error("❌ No active subscription found for user {$userId}");
            return;
        }
        
        $this->showUserDetails($subscription);
    }
    
    private function diagnoseAllUsers()
    {
        $this->info('🔍 Diagnosing All Active Users...');
        $this->newLine();
        
        $subscriptions = Subscription::where('status', 1) // ACTIVE
            ->with(['user', 'plan'])
            ->get();
            
        if ($subscriptions->isEmpty()) {
            $this->warn('⚠️  No active subscriptions found');
            return;
        }
        
        $this->info("📊 Found {$subscriptions->count()} active subscriptions");
        $this->newLine();
        
        $issues = [];
        foreach ($subscriptions as $subscription) {
            $userIssues = $this->analyzeSubscription($subscription);
            if (!empty($userIssues)) {
                $issues[$subscription->user_id] = $userIssues;
            }
        }
        
        if (empty($issues)) {
            $this->info('✅ No issues found with any subscriptions');
        } else {
            $this->error('❌ Issues found:');
            foreach ($issues as $userId => $userIssues) {
                $this->line("User {$userId}:");
                foreach ($userIssues as $issue) {
                    $this->line("  - {$issue}");
                }
            }
        }
    }
    
    private function showUserDetails($subscription)
    {
        $this->info("👤 User Details:");
        $this->line("   User ID: {$subscription->user_id}");
        $this->line("   Plan: {$subscription->plan->name}");
        $this->line("   Subscription ID: {$subscription->id}");
        $this->line("   Status: {$subscription->status}");
        $this->newLine();
        
        // Check actual quiz count
        $actualQuizCount = Quiz::where('user_id', $subscription->user_id)->count();
        $this->info("📊 Usage Data:");
        $this->line("   Actual Quiz Count: {$actualQuizCount}");
        
        // Check if columns exist
        if (Schema::hasColumn('subscriptions', 'exams_generated_this_month')) {
            $counterValue = $subscription->exams_generated_this_month ?? 'NULL';
            $this->line("   Counter Value: {$counterValue}");
            
            if ($counterValue !== $actualQuizCount) {
                $this->error("   ❌ MISMATCH: Counter ({$counterValue}) != Actual ({$actualQuizCount})");
            } else {
                $this->info("   ✅ Counter matches actual count");
            }
        } else {
            $this->warn("   ⚠️  Counter column doesn't exist");
        }
        
        $this->newLine();
        
        // Test PlanValidationService
        $this->info("🧪 Testing PlanValidationService:");
        try {
            $planService = app(PlanValidationService::class);
            $result = $planService->canCreateExam();
            
            $this->line("   Allowed: " . ($result['allowed'] ? 'Yes' : 'No'));
            $this->line("   Message: {$result['message']}");
            $this->line("   Limit: {$result['limit']}");
            $this->line("   Used: {$result['used']}");
            $this->line("   Remaining: {$result['remaining']}");
            
        } catch (\Exception $e) {
            $this->error("   ❌ Error: {$e->getMessage()}");
        }
    }
    
    private function analyzeSubscription($subscription)
    {
        $issues = [];
        
        // Check if columns exist
        if (!Schema::hasColumn('subscriptions', 'exams_generated_this_month')) {
            $issues[] = "Missing usage tracking columns";
            return $issues;
        }
        
        // Check actual vs counter
        $actualQuizCount = Quiz::where('user_id', $subscription->user_id)->count();
        $counterValue = $subscription->exams_generated_this_month ?? 0;
        
        if ($counterValue !== $actualQuizCount) {
            $issues[] = "Counter mismatch: {$counterValue} vs {$actualQuizCount} actual quizzes";
        }
        
        // Check plan limits
        if ($subscription->plan) {
            $plan = $subscription->plan;
            $examsPerMonth = $plan->exams_per_month ?? $plan->no_of_exam ?? 0;
            
            if ($examsPerMonth > 0 && $counterValue > $examsPerMonth) {
                $issues[] = "Counter exceeds plan limit: {$counterValue} > {$examsPerMonth}";
            }
        }
        
        return $issues;
    }
}
