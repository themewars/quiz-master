<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\PlanValidationService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class DiagnoseRegistration extends Command
{
    protected $signature = 'registration:diagnose {user_id? : Specific user ID to diagnose}';
    protected $description = 'Diagnose registration and dashboard loading issues';

    public function handle()
    {
        $userId = $this->argument('user_id');
        
        if ($userId) {
            $this->diagnoseUser($userId);
        } else {
            $this->diagnoseRecentUsers();
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
        $this->info("Email: {$user->email}");
        $this->info("Status: " . ($user->status ? 'Active' : 'Inactive'));
        $this->info("Email Verified: " . ($user->email_verified_at ? 'Yes' : 'No'));
        $this->info("Created: {$user->created_at}");

        // Check roles
        $roles = $user->roles;
        $this->info("Roles: " . $roles->pluck('name')->join(', '));

        // Check subscriptions
        $subscriptions = $user->subscriptions;
        $this->info("Total Subscriptions: " . $subscriptions->count());
        
        $activeSubscription = $subscriptions->where('status', 'active')->first();
        if ($activeSubscription) {
            $this->info("Active Subscription: ID {$activeSubscription->id}");
            $this->info("Plan: {$activeSubscription->plan->name}");
            $this->info("Status: {$activeSubscription->status}");
        } else {
            $this->warn("⚠️  No active subscription found");
        }

        // Check default plan
        $defaultPlan = Plan::where('assign_default', true)->where('status', true)->first();
        if ($defaultPlan) {
            $this->info("Default Plan Available: {$defaultPlan->name}");
        } else {
            $this->error("❌ No default plan found - this will cause registration issues!");
        }

        // Test PlanValidationService
        try {
            $planValidationService = new PlanValidationService($user);
            $result = $planValidationService->canCreateExam();
            
            $this->info("PlanValidationService Test:");
            $this->info("  Allowed: " . ($result['allowed'] ? 'Yes' : 'No'));
            $this->info("  Message: {$result['message']}");
            $this->info("  Remaining: {$result['remaining']}");
        } catch (\Exception $e) {
            $this->error("❌ PlanValidationService Error: " . $e->getMessage());
        }

        // Check if user can access dashboard
        $this->info("\n=== Dashboard Access Test ===");
        try {
            // Simulate dashboard widget loading
            $subscription = Subscription::where('user_id', $userId)
                ->where('status', 'active')
                ->first();
                
            if ($subscription) {
                $this->info("✅ User has active subscription - dashboard should load");
            } else {
                $this->warn("⚠️  No active subscription - dashboard may show errors");
            }
        } catch (\Exception $e) {
            $this->error("❌ Dashboard access error: " . $e->getMessage());
        }
    }

    private function diagnoseRecentUsers()
    {
        $this->info("=== Diagnosing Recent Users (Last 10) ===");
        
        $users = User::orderByDesc('created_at')->limit(10)->get();
        
        foreach ($users as $user) {
            $this->line("User {$user->id}: {$user->name} ({$user->email})");
            
            $activeSubscription = $user->subscriptions()->where('status', 'active')->first();
            if ($activeSubscription) {
                $this->info("  ✅ Has active subscription");
            } else {
                $this->warn("  ⚠️  No active subscription");
            }
        }

        // Check system health
        $this->info("\n=== System Health Check ===");
        
        $defaultPlan = Plan::where('assign_default', true)->where('status', true)->first();
        if ($defaultPlan) {
            $this->info("✅ Default plan exists: {$defaultPlan->name}");
        } else {
            $this->error("❌ No default plan found!");
        }

        $totalUsers = User::count();
        $usersWithSubscriptions = User::whereHas('subscriptions')->count();
        $usersWithoutSubscriptions = $totalUsers - $usersWithSubscriptions;
        
        $this->info("Total Users: {$totalUsers}");
        $this->info("Users with Subscriptions: {$usersWithSubscriptions}");
        $this->info("Users without Subscriptions: {$usersWithoutSubscriptions}");
        
        if ($usersWithoutSubscriptions > 0) {
            $this->warn("⚠️  {$usersWithoutSubscriptions} users don't have subscriptions");
        }
    }
}
