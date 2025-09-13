<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PlanValidationService;
use Illuminate\Console\Command;

class DebugPlanUsage extends Command
{
    protected $signature = 'plan:debug {userId?}';

    protected $description = 'Print plan usage and remaining exams for a user';

    public function handle(): int
    {
        $userId = $this->argument('userId');

        if ($userId) {
            $user = User::find($userId);
        } else {
            $sub = Subscription::where('status', SubscriptionStatus::ACTIVE->value)->orderByDesc('id')->first();
            $user = $sub?->user;
        }

        if (!$user) {
            $this->error('No suitable user found.');
            return self::FAILURE;
        }

        $service = new PlanValidationService($user);
        $check = $service->canCreateExam();

        $this->info('User: ' . $user->id . ' - ' . ($user->email ?? 'n/a'));
        $this->line('Allowed: ' . (($check['allowed'] ?? false) ? 'yes' : 'no'));
        $this->line('Message: ' . ($check['message'] ?? ''));
        $this->line('Limit: ' . ($check['limit'] ?? 'n/a'));
        $this->line('Used: ' . ($check['used'] ?? 'n/a'));
        $this->line('Remaining: ' . ($check['remaining'] ?? 'n/a'));

        return self::SUCCESS;
    }
}


