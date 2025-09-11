<?php

namespace App\Filament\User\Widgets;

use App\Enums\SubscriptionStatus;
use App\Models\Quiz;
use App\Models\Subscription;
use App\Models\UserQuiz;
use App\Services\PlanValidationService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class UserDashboardCardCount extends BaseWidget
{
    protected static string $view = 'filament.user.widgets.user-dashboard-widgets';


    protected function getViewData(): array
    {
        try {
            $userId = getLoggedInUserId();
            if (!$userId) {
                return $this->getDefaultData();
            }

            $subscription = Subscription::where('user_id', $userId)
                ->where('status', SubscriptionStatus::ACTIVE->value)
                ->first();

            $totalQuizzes = Quiz::where('user_id', $userId)->count();
            $activeQuizzes = Quiz::where('user_id', $userId)->where('status', 1)->count();

            $participants = UserQuiz::whereHas('quiz', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })->count();

            $completedCount = UserQuiz::whereHas('quiz', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })->whereNotNull('completed_at')->count();

            $completedPer = $participants > 0 ? round(($completedCount / $participants) * 100) : 0;

            // Plan usage summary - Use PlanValidationService for accurate calculation
            $examsRemaining = 0;
            try {
                $planValidationService = app(PlanValidationService::class);
                $planCheck = $planValidationService->canCreateExam();
                
                if (isset($planCheck['remaining'])) {
                    if ($planCheck['remaining'] === -1) {
                        $examsRemaining = __('messages.common.unlimited');
                    } else {
                        $examsRemaining = max(0, $planCheck['remaining']); // Ensure non-negative
                    }
                } else {
                    $examsRemaining = 0;
                }
            } catch (\Exception $e) {
                \Log::error('Error calculating exams remaining: ' . $e->getMessage(), [
                    'user_id' => $userId,
                    'trace' => $e->getTraceAsString()
                ]);
                $examsRemaining = 0;
            }

            return [
                'subscription' => $subscription,
                'totalQuizzes' => $totalQuizzes,
                'activeQuizzes' => $activeQuizzes,
                'participants' => $participants,
                'completedPer' => $completedPer,
                'examsRemaining' => $examsRemaining,
            ];
        } catch (\Exception $e) {
            \Log::error('Error in UserDashboardCardCount::getViewData: ' . $e->getMessage(), [
                'user_id' => getLoggedInUserId(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->getDefaultData();
        }
    }

    private function getDefaultData(): array
    {
        return [
            'subscription' => null,
            'totalQuizzes' => 0,
            'activeQuizzes' => 0,
            'participants' => 0,
            'completedPer' => 0,
            'examsRemaining' => 0,
        ];
    }
}
