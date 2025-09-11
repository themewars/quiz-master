<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixRegistrationIssues extends Command
{
    protected $signature = 'registration:fix';
    protected $description = 'Fix common registration issues and ensure proper setup';

    public function handle()
    {
        $this->info('🔧 Starting registration system diagnostics and fixes...');
        
        // Check database structure
        $this->checkDatabaseStructure();
        
        // Check default plan
        $this->checkDefaultPlan();
        
        // Check roles
        $this->checkRoles();
        
        // Check recent registrations
        $this->checkRecentRegistrations();
        
        $this->info('✅ Registration system diagnostics completed!');
        
        return 0;
    }

    private function checkDatabaseStructure()
    {
        $this->info('📊 Checking database structure...');
        
        $requiredFields = ['balance', 'used_balance', 'status'];
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (!Schema::hasColumn('users', $field)) {
                $missingFields[] = $field;
            }
        }
        
        if (empty($missingFields)) {
            $this->info('✅ All required user fields are present');
        } else {
            $this->error('❌ Missing user fields: ' . implode(', ', $missingFields));
            $this->warn('Run: php artisan migrate to add missing fields');
        }
    }

    private function checkDefaultPlan()
    {
        $this->info('📋 Checking default plan configuration...');
        
        $defaultPlan = Plan::where('assign_default', true)->where('status', true)->first();
        
        if ($defaultPlan) {
            $this->info("✅ Default plan found: {$defaultPlan->name} (ID: {$defaultPlan->id})");
            $this->info("   - Price: {$defaultPlan->price}");
            $this->info("   - Trial days: " . ($defaultPlan->trial_days ?? 'None'));
            $this->info("   - Exams per month: " . ($defaultPlan->exams_per_month ?? 'Unlimited'));
        } else {
            $this->error('❌ No active default plan found!');
            
            // Try to find any plan
            $anyPlan = Plan::where('status', true)->first();
            if ($anyPlan) {
                $this->warn("Found plan '{$anyPlan->name}' but it's not set as default");
                if ($this->confirm('Set this plan as default?')) {
                    $anyPlan->update(['assign_default' => true]);
                    $this->info('✅ Plan set as default');
                }
            } else {
                $this->error('❌ No plans found in database!');
                $this->warn('Create at least one plan in admin panel');
            }
        }
    }

    private function checkRoles()
    {
        $this->info('👥 Checking user roles...');
        
        $userRole = Role::where('name', User::USER_ROLE)->first();
        $adminRole = Role::where('name', User::ADMIN_ROLE)->first();
        
        if ($userRole && $adminRole) {
            $this->info('✅ Both user and admin roles are present');
        } else {
            $this->error('❌ Missing roles:');
            if (!$userRole) $this->error('   - User role missing');
            if (!$adminRole) $this->error('   - Admin role missing');
            $this->warn('Run: php artisan db:seed --class=RoleSeeder');
        }
    }

    private function checkRecentRegistrations()
    {
        $this->info('👤 Checking recent user registrations...');
        
        $recentUsers = User::latest()->take(5)->get();
        
        if ($recentUsers->isEmpty()) {
            $this->warn('No users found in database');
            return;
        }
        
        foreach ($recentUsers as $user) {
            $this->info("User: {$user->name} ({$user->email})");
            $this->info("   - Created: {$user->created_at}");
            $this->info("   - Status: " . ($user->status ? 'Active' : 'Inactive'));
            $this->info("   - Email verified: " . ($user->email_verified_at ? 'Yes' : 'No'));
            
            // Check roles
            $roles = $user->roles->pluck('name')->join(', ');
            $this->info("   - Roles: " . ($roles ?: 'None'));
            
            // Check subscriptions
            $subscriptions = $user->subscriptions;
            $this->info("   - Subscriptions: {$subscriptions->count()}");
            
            if ($subscriptions->count() > 0) {
                $activeSub = $subscriptions->where('status', 'active')->first();
                if ($activeSub) {
                    $this->info("   - Active plan: {$activeSub->plan->name}");
                } else {
                    $this->warn("   - No active subscription");
                }
            } else {
                $this->warn("   - No subscriptions found");
            }
            
            $this->info('');
        }
    }
}
