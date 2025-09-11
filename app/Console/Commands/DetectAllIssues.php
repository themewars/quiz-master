<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Plan;
use App\Models\Currency;
use App\Models\Subscription;
use Spatie\Permission\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;

class DetectAllIssues extends Command
{
    protected $signature = 'issues:detect';
    protected $description = 'Detect all potential issues in the application';

    public function handle()
    {
        $this->info('🔍 Starting comprehensive issue detection...');
        $this->info('==========================================');
        
        $issues = [];
        
        // Check database structure
        $issues = array_merge($issues, $this->checkDatabaseStructure());
        
        // Check models and relationships
        $issues = array_merge($issues, $this->checkModels());
        
        // Check configuration
        $issues = array_merge($issues, $this->checkConfiguration());
        
        // Check file permissions
        $issues = array_merge($issues, $this->checkFilePermissions());
        
        // Check dependencies
        $issues = array_merge($issues, $this->checkDependencies());
        
        // Check routes
        $issues = array_merge($issues, $this->checkRoutes());
        
        // Summary
        $this->displaySummary($issues);
        
        return 0;
    }

    private function checkDatabaseStructure()
    {
        $this->info('📊 Checking database structure...');
        $issues = [];
        
        // Check users table
        $requiredUserFields = ['balance', 'used_balance', 'status', 'email_verified_at'];
        foreach ($requiredUserFields as $field) {
            if (!Schema::hasColumn('users', $field)) {
                $issues[] = [
                    'type' => 'database',
                    'severity' => 'high',
                    'message' => "Missing field '{$field}' in users table",
                    'solution' => 'Run: php artisan migrate'
                ];
            }
        }
        
        // Check if tables exist
        $requiredTables = ['users', 'plans', 'subscriptions', 'currencies', 'roles', 'permissions'];
        foreach ($requiredTables as $table) {
            if (!Schema::hasTable($table)) {
                $issues[] = [
                    'type' => 'database',
                    'severity' => 'critical',
                    'message' => "Missing table '{$table}'",
                    'solution' => 'Run: php artisan migrate'
                ];
            }
        }
        
        return $issues;
    }

    private function checkModels()
    {
        $this->info('🏗️ Checking models and relationships...');
        $issues = [];
        
        try {
            // Check if default plan exists
            $defaultPlan = Plan::where('assign_default', true)->where('status', true)->first();
            if (!$defaultPlan) {
                $issues[] = [
                    'type' => 'model',
                    'severity' => 'high',
                    'message' => 'No active default plan found',
                    'solution' => 'Run: php artisan db:seed --class=RegistrationSetupSeeder'
                ];
            }
            
            // Check if currency exists
            $currency = Currency::first();
            if (!$currency) {
                $issues[] = [
                    'type' => 'model',
                    'severity' => 'high',
                    'message' => 'No currency found in database',
                    'solution' => 'Run: php artisan db:seed --class=RegistrationSetupSeeder'
                ];
            }
            
            // Check roles
            $userRole = Role::where('name', User::USER_ROLE)->first();
            $adminRole = Role::where('name', User::ADMIN_ROLE)->first();
            
            if (!$userRole) {
                $issues[] = [
                    'type' => 'model',
                    'severity' => 'critical',
                    'message' => 'User role not found',
                    'solution' => 'Run: php artisan db:seed --class=RoleSeeder'
                ];
            }
            
            if (!$adminRole) {
                $issues[] = [
                    'type' => 'model',
                    'severity' => 'critical',
                    'message' => 'Admin role not found',
                    'solution' => 'Run: php artisan db:seed --class=RoleSeeder'
                ];
            }
            
        } catch (\Exception $e) {
            $issues[] = [
                'type' => 'model',
                'severity' => 'critical',
                'message' => 'Database connection error: ' . $e->getMessage(),
                'solution' => 'Check database configuration in .env file'
            ];
        }
        
        return $issues;
    }

    private function checkConfiguration()
    {
        $this->info('⚙️ Checking configuration...');
        $issues = [];
        
        // Check .env file
        if (!File::exists('.env')) {
            $issues[] = [
                'type' => 'config',
                'severity' => 'critical',
                'message' => '.env file not found',
                'solution' => 'Copy .env.example to .env and configure'
            ];
        }
        
        // Check required environment variables
        $requiredEnvVars = [
            'APP_NAME', 'APP_ENV', 'APP_KEY', 'DB_CONNECTION', 
            'DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'
        ];
        
        foreach ($requiredEnvVars as $var) {
            if (!env($var)) {
                $issues[] = [
                    'type' => 'config',
                    'severity' => 'high',
                    'message' => "Environment variable {$var} not set",
                    'solution' => 'Set {$var} in .env file'
                ];
            }
        }
        
        // Check storage link
        if (!File::exists('public/storage')) {
            $issues[] = [
                'type' => 'config',
                'severity' => 'medium',
                'message' => 'Storage link not created',
                'solution' => 'Run: php artisan storage:link'
            ];
        }
        
        return $issues;
    }

    private function checkFilePermissions()
    {
        $this->info('📁 Checking file permissions...');
        $issues = [];
        
        $directories = ['storage', 'bootstrap/cache'];
        foreach ($directories as $dir) {
            if (!is_writable($dir)) {
                $issues[] = [
                    'type' => 'permission',
                    'severity' => 'high',
                    'message' => "Directory {$dir} is not writable",
                    'solution' => "Run: chmod -R 775 {$dir}"
                ];
            }
        }
        
        return $issues;
    }

    private function checkDependencies()
    {
        $this->info('📦 Checking dependencies...');
        $issues = [];
        
        // Check if vendor directory exists
        if (!File::exists('vendor')) {
            $issues[] = [
                'type' => 'dependency',
                'severity' => 'critical',
                'message' => 'Vendor directory not found',
                'solution' => 'Run: composer install'
            ];
        }
        
        // Check if node_modules exists (for frontend)
        if (!File::exists('node_modules')) {
            $issues[] = [
                'type' => 'dependency',
                'severity' => 'medium',
                'message' => 'Node modules not installed',
                'solution' => 'Run: npm install'
            ];
        }
        
        return $issues;
    }

    private function checkRoutes()
    {
        $this->info('🛣️ Checking routes...');
        $issues = [];
        
        try {
            // Check if routes are cached
            if (File::exists('bootstrap/cache/routes-v7.php')) {
                $this->warn('Routes are cached. Run: php artisan route:clear to refresh');
            }
            
            // Check if views are cached
            if (File::exists('storage/framework/views')) {
                $this->warn('Views are cached. Run: php artisan view:clear to refresh');
            }
            
        } catch (\Exception $e) {
            $issues[] = [
                'type' => 'route',
                'severity' => 'medium',
                'message' => 'Route checking error: ' . $e->getMessage(),
                'solution' => 'Run: php artisan route:clear'
            ];
        }
        
        return $issues;
    }

    private function displaySummary($issues)
    {
        $this->info('');
        $this->info('📋 ISSUE SUMMARY');
        $this->info('================');
        
        if (empty($issues)) {
            $this->info('✅ No issues found! Your application is properly configured.');
            return;
        }
        
        $critical = array_filter($issues, fn($issue) => $issue['severity'] === 'critical');
        $high = array_filter($issues, fn($issue) => $issue['severity'] === 'high');
        $medium = array_filter($issues, fn($issue) => $issue['severity'] === 'medium');
        $low = array_filter($issues, fn($issue) => $issue['severity'] === 'low');
        
        $this->error("🔴 Critical Issues: " . count($critical));
        $this->warn("🟡 High Priority Issues: " . count($high));
        $this->info("🔵 Medium Priority Issues: " . count($medium));
        $this->info("🟢 Low Priority Issues: " . count($low));
        
        $this->info('');
        $this->info('🔧 RECOMMENDED ACTIONS:');
        $this->info('=======================');
        
        foreach ($issues as $issue) {
            $severity = match($issue['severity']) {
                'critical' => '🔴',
                'high' => '🟡',
                'medium' => '🔵',
                'low' => '🟢',
                default => '⚪'
            };
            
            $this->line("{$severity} {$issue['message']}");
            $this->line("   Solution: {$issue['solution']}");
            $this->line('');
        }
        
        $this->info('💡 Quick Fix Commands:');
        $this->info('=====================');
        $this->line('php artisan migrate --force');
        $this->line('php artisan db:seed --class=RegistrationSetupSeeder');
        $this->line('php artisan storage:link');
        $this->line('php artisan config:clear');
        $this->line('php artisan cache:clear');
        $this->line('php artisan route:clear');
        $this->line('php artisan view:clear');
    }
}
