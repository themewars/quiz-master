<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Role;
use App\Actions\Subscription\CreateSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestRegistrationFlow extends Command
{
    protected $signature = 'registration:test';
    protected $description = 'Test the complete registration flow';

    public function handle()
    {
        $this->info('🧪 Testing registration flow...');
        
        // Create test user data
        $testEmail = 'test_' . Str::random(8) . '@example.com';
        $testData = [
            'name' => 'Test User',
            'email' => $testEmail,
            'password' => Hash::make('password123'),
            'status' => true,
        ];
        
        try {
            // Test user creation
            $this->info('1️⃣ Creating test user...');
            $user = User::create($testData);
            $this->info("✅ User created: {$user->name} ({$user->email})");
            
            // Test role assignment
            $this->info('2️⃣ Assigning user role...');
            $userRole = Role::where('name', User::USER_ROLE)->first();
            if ($userRole) {
                $user->assignRole($userRole);
                $this->info('✅ User role assigned');
            } else {
                $this->error('❌ User role not found');
            }
            
            // Test subscription creation
            $this->info('3️⃣ Creating subscription...');
            $defaultPlan = Plan::where('assign_default', true)->where('status', true)->first();
            
            if ($defaultPlan) {
                $subscriptionData = [
                    'plan' => $defaultPlan->load('currency')->toArray(),
                    'user_id' => $user->id,
                    'payment_type' => Subscription::TYPE_FREE,
                ];
                
                if ($defaultPlan->trial_days && $defaultPlan->trial_days > 0) {
                    $subscriptionData['trial_days'] = $defaultPlan->trial_days;
                }
                
                CreateSubscription::run($subscriptionData);
                $this->info("✅ Subscription created with plan: {$defaultPlan->name}");
            } else {
                $this->error('❌ No default plan found');
            }
            
            // Verify everything
            $this->info('4️⃣ Verifying registration...');
            $user->refresh();
            
            $this->info("User ID: {$user->id}");
            $this->info("Email: {$user->email}");
            $this->info("Status: " . ($user->status ? 'Active' : 'Inactive'));
            $this->info("Balance: {$user->balance}");
            $this->info("Roles: " . $user->roles->pluck('name')->join(', '));
            $this->info("Subscriptions: " . $user->subscriptions->count());
            
            if ($user->subscriptions->count() > 0) {
                $activeSub = $user->subscriptions->where('status', 'active')->first();
                if ($activeSub) {
                    $this->info("Active Plan: {$activeSub->plan->name}");
                }
            }
            
            $this->info('✅ Registration flow test completed successfully!');
            
            // Cleanup
            if ($this->confirm('Delete test user?')) {
                $user->delete();
                $this->info('🗑️ Test user deleted');
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Registration test failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            
            // Cleanup on error
            if (isset($user)) {
                $user->delete();
                $this->info('🗑️ Test user cleaned up');
            }
        }
        
        return 0;
    }
}
