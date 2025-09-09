<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Enums\SubscriptionStatus;
use Illuminate\Support\Facades\Auth;

class PlanValidationService
{
    protected $subscription;
    protected $plan;

    public function __construct()
    {
        $this->subscription = $this->getActiveSubscription();
        $this->plan = $this->subscription?->plan;
    }

    /**
     * Get active subscription for current user
     */
    protected function getActiveSubscription(): ?Subscription
    {
        return Subscription::with('plan')
            ->where('user_id', Auth::id())
            ->where('status', SubscriptionStatus::ACTIVE->value)
            ->first();
    }

    /**
     * Check if user can create exam with given question count
     */
    public function canCreateExam(int $questionCount = 0): array
    {
        if (!$this->subscription || !$this->plan) {
            return [
                'allowed' => false,
                'reason' => 'no_active_subscription',
                'message' => 'No active subscription found. Please subscribe to a plan.',
                'upgrade_required' => true
            ];
        }

        // Check if subscription is expired
        if ($this->subscription->isExpired()) {
            return [
                'allowed' => false,
                'reason' => 'subscription_expired',
                'message' => 'Your subscription has expired. Please renew or upgrade.',
                'upgrade_required' => true
            ];
        }

        // Check monthly exam limit
        if (!$this->canCreateMoreExams()) {
            return [
                'allowed' => false,
                'reason' => 'exam_limit_exceeded',
                'message' => "You've reached your monthly exam limit ({$this->plan->exams_per_month}). Upgrade to create more exams.",
                'upgrade_required' => true,
                'current_usage' => $this->subscription->exams_generated_this_month,
                'limit' => $this->plan->exams_per_month
            ];
        }

        // Check question count limit
        if ($questionCount > 0 && !$this->canCreateQuestions($questionCount)) {
            return [
                'allowed' => false,
                'reason' => 'question_limit_exceeded',
                'message' => "This exam exceeds your plan's question limit ({$this->plan->max_questions_per_exam} questions per exam).",
                'upgrade_required' => true,
                'requested_questions' => $questionCount,
                'limit' => $this->plan->max_questions_per_exam
            ];
        }

        // Check monthly question limit
        if ($questionCount > 0 && !$this->canCreateQuestionsThisMonth($questionCount)) {
            return [
                'allowed' => false,
                'reason' => 'monthly_question_limit_exceeded',
                'message' => "This exam would exceed your monthly question limit ({$this->plan->max_questions_per_month} questions per month).",
                'upgrade_required' => true,
                'current_usage' => $this->subscription->questions_generated_this_month,
                'requested_questions' => $questionCount,
                'limit' => $this->plan->max_questions_per_month
            ];
        }

        return [
            'allowed' => true,
            'reason' => 'success',
            'message' => 'Exam creation allowed',
            'upgrade_required' => false
        ];
    }

    /**
     * Check if user can create more exams this month
     */
    public function canCreateMoreExams(): bool
    {
        if (!$this->plan) return false;

        // Unlimited exams
        if ($this->plan->hasUnlimitedExams()) {
            return true;
        }

        // Check monthly limit
        return $this->subscription->exams_generated_this_month < $this->plan->exams_per_month;
    }

    /**
     * Check if user can create questions for an exam
     */
    public function canCreateQuestions(int $questionCount): bool
    {
        if (!$this->plan) return false;

        // Unlimited questions per exam
        if ($this->plan->hasUnlimitedQuestionsPerExam()) {
            return true;
        }

        return $questionCount <= $this->plan->max_questions_per_exam;
    }

    /**
     * Check if user can create questions this month
     */
    public function canCreateQuestionsThisMonth(int $questionCount): bool
    {
        if (!$this->plan) return false;

        // No monthly question limit
        if (!$this->plan->max_questions_per_month) {
            return true;
        }

        // Unlimited questions per month
        if ($this->plan->hasUnlimitedQuestionsPerMonth()) {
            return true;
        }

        return ($this->subscription->questions_generated_this_month + $questionCount) <= $this->plan->max_questions_per_month;
    }

    /**
     * Check if user can use specific feature
     */
    public function canUseFeature(string $feature): bool
    {
        if (!$this->plan) return false;

        return $this->plan->allowsFeature($feature);
    }

    /**
     * Check if user can use specific question type
     */
    public function canUseQuestionType(string $type): bool
    {
        if (!$this->plan) return false;

        return $this->plan->allowsQuestionType($type);
    }

    /**
     * Get current usage statistics
     */
    public function getUsageStats(): array
    {
        if (!$this->subscription || !$this->plan) {
            return [
                'exams_used' => 0,
                'exams_limit' => 0,
                'questions_used' => 0,
                'questions_limit' => 0,
                'exams_remaining' => 0,
                'questions_remaining' => 0,
                'reset_date' => null
            ];
        }

        $examsUsed = $this->subscription->exams_generated_this_month;
        $questionsUsed = $this->subscription->questions_generated_this_month;
        
        $examsLimit = $this->plan->hasUnlimitedExams() ? -1 : $this->plan->exams_per_month;
        $questionsLimit = $this->plan->hasUnlimitedQuestionsPerMonth() ? -1 : ($this->plan->max_questions_per_month ?? -1);

        return [
            'exams_used' => $examsUsed,
            'exams_limit' => $examsLimit,
            'questions_used' => $questionsUsed,
            'questions_limit' => $questionsLimit,
            'exams_remaining' => $examsLimit === -1 ? -1 : max(0, $examsLimit - $examsUsed),
            'questions_remaining' => $questionsLimit === -1 ? -1 : max(0, $questionsLimit - $questionsUsed),
            'reset_date' => $this->subscription->usage_reset_date
        ];
    }

    /**
     * Record exam creation
     */
    public function recordExamCreation(int $questionCount = 0): void
    {
        if (!$this->subscription) return;

        $this->subscription->increment('exams_generated_this_month');
        
        if ($questionCount > 0) {
            $this->subscription->increment('questions_generated_this_month', $questionCount);
        }
    }

    /**
     * Reset monthly usage (called by cron job)
     */
    public function resetMonthlyUsage(): void
    {
        if (!$this->subscription) return;

        $this->subscription->update([
            'exams_generated_this_month' => 0,
            'questions_generated_this_month' => 0,
            'usage_reset_date' => now()->addMonth()->startOfMonth()
        ]);
    }
}
