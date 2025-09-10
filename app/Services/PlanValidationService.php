<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Quiz;
use Carbon\Carbon;

class PlanValidationService
{
    protected $user;
    protected $subscription;
    protected $plan;

    public function __construct(User $user = null)
    {
        $this->user = $user ?? auth()->user();

        // Prefer an active subscription
        $this->subscription = $this->user
            ? $this->user->subscriptions()
                ->whereIn('status', ['active', 'trial', 'trialing'])
                ->orderByDesc('id')
                ->first()
            : null;

        $this->plan = $this->subscription?->plan;

        // Fallback to default plan if no active subscription is found
        if (!$this->plan) {
            $this->plan = Plan::where('assign_default', true)->first();
        }
    }

    /**
     * Check if user can create an exam
     */
    public function canCreateExam(): array
    {
        if (!$this->plan) {
            return [
                'allowed' => false,
                'message' => 'No active subscription found',
                'limit' => 0,
                'used' => 0,
                'remaining' => 0
            ];
        }

        // Check if plan has unlimited exams
        if ($this->plan->hasUnlimitedExams()) {
            return [
                'allowed' => true,
                'message' => 'Unlimited exams allowed',
                'limit' => -1,
                'used' => $this->subscription->exams_generated_this_month ?? 0,
                'remaining' => -1
            ];
        }

        $examsPerMonth = $this->plan->exams_per_month ?? 0;
        $usedExams = $this->subscription->exams_generated_this_month ?? 0;

        // Fallback: derive actual created exams this month to avoid stale counters
        if ($this->user) {
            $now = Carbon::now();
            $actualExams = Quiz::where('user_id', $this->user->id)
                ->whereYear('created_at', $now->year)
                ->whereMonth('created_at', $now->month)
                ->count();
            $usedExams = max((int) $usedExams, (int) $actualExams);
        }

        // If limit is 0 or below, treat as no monthly cap (backward compatible)
        if ($examsPerMonth <= 0) {
            return [
                'allowed' => true,
                'message' => 'No monthly exam cap',
                'limit' => 0,
                'used' => $usedExams,
                'remaining' => -1,
            ];
        }

        if ($usedExams >= $examsPerMonth) {
            return [
                'allowed' => false,
                'message' => "Monthly exam limit reached ({$examsPerMonth} exams)",
                'limit' => $examsPerMonth,
                'used' => $usedExams,
                'remaining' => 0
            ];
        }

        return [
            'allowed' => true,
            'message' => "Exams remaining: " . ($examsPerMonth - $usedExams),
            'limit' => $examsPerMonth,
            'used' => $usedExams,
            'remaining' => $examsPerMonth - $usedExams
        ];
    }

    /**
     * Check if user can generate questions for an exam
     */
    public function canGenerateQuestions(int $questionCount = 1): array
    {
        if (!$this->plan) {
            return [
                'allowed' => false,
                'message' => 'No active subscription found',
                'limit' => 0,
                'used' => 0,
                'remaining' => 0
            ];
        }

        // Check per-exam question limit
        $maxQuestionsPerExam = $this->plan->max_questions_per_exam ?? 0;
        if ($maxQuestionsPerExam > 0 && $questionCount > $maxQuestionsPerExam) {
            return [
                'allowed' => false,
                'message' => "Question limit per exam exceeded ({$maxQuestionsPerExam} questions max)",
                'limit' => $maxQuestionsPerExam,
                'used' => $questionCount,
                'remaining' => 0
            ];
        }

        // Check monthly question limit
        if ($this->plan->hasUnlimitedQuestionsPerMonth()) {
            return [
                'allowed' => true,
                'message' => 'Unlimited questions allowed',
                'limit' => -1,
                'used' => $this->subscription->questions_generated_this_month ?? 0,
                'remaining' => -1
            ];
        }

        $questionsPerMonth = $this->plan->max_questions_per_month ?? 0;
        $usedQuestions = $this->subscription->questions_generated_this_month ?? 0;

        // If limit is 0 or below, treat as no monthly cap (backward compatible)
        if ($questionsPerMonth <= 0) {
            return [
                'allowed' => true,
                'message' => 'No monthly question cap',
                'limit' => 0,
                'used' => $usedQuestions,
                'remaining' => -1,
            ];
        }

        if (($usedQuestions + $questionCount) > $questionsPerMonth) {
            return [
                'allowed' => false,
                'message' => "Monthly question limit would be exceeded ({$questionsPerMonth} questions max)",
                'limit' => $questionsPerMonth,
                'used' => $usedQuestions,
                'remaining' => max(0, $questionsPerMonth - $usedQuestions)
            ];
        }

        return [
            'allowed' => true,
            'message' => "Questions remaining: " . ($questionsPerMonth - $usedQuestions),
            'limit' => $questionsPerMonth,
            'used' => $usedQuestions,
            'remaining' => $questionsPerMonth - $usedQuestions
        ];
    }

    /**
     * Check if user can use a specific feature
     */
    public function canUseFeature(string $feature): array
    {
        if (!$this->plan) {
            return [
                'allowed' => false,
                'message' => 'No active subscription found'
            ];
        }

        $allowed = $this->plan->allowsFeature($feature);

        return [
            'allowed' => $allowed,
            'message' => $allowed ? 'Feature is available' : 'Feature not available in current plan'
        ];
    }

    /**
     * Check if user can use a specific question type
     */
    public function canUseQuestionType(string $questionType): array
    {
        if (!$this->plan) {
            return [
                'allowed' => false,
                'message' => 'No active subscription found'
            ];
        }

        $allowed = $this->plan->allowsQuestionType($questionType);

        return [
            'allowed' => $allowed,
            'message' => $allowed ? 'Question type is allowed' : 'Question type not allowed in current plan'
        ];
    }

    /**
     * Update usage counters
     */
    public function updateUsage(int $examsGenerated = 0, int $questionsGenerated = 0): void
    {
        if (!$this->subscription) {
            return;
        }

        $this->subscription->update([
            'exams_generated_this_month' => ($this->subscription->exams_generated_this_month ?? 0) + $examsGenerated,
            'questions_generated_this_month' => ($this->subscription->questions_generated_this_month ?? 0) + $questionsGenerated,
        ]);
    }

    /**
     * Reset monthly usage (called by scheduler)
     */
    public function resetMonthlyUsage(): void
    {
        if (!$this->subscription) {
            return;
        }

        $this->subscription->update([
            'exams_generated_this_month' => 0,
            'questions_generated_this_month' => 0,
            'usage_reset_date' => now()->addMonth(),
        ]);
    }

    /**
     * Get current usage summary
     */
    public function getUsageSummary(): array
    {
        if (!$this->plan || !$this->subscription) {
            return [
                'exams' => ['used' => 0, 'limit' => 0, 'unlimited' => false],
                'questions' => ['used' => 0, 'limit' => 0, 'unlimited' => false],
                'features' => []
            ];
        }

        return [
            'exams' => [
                'used' => $this->subscription->exams_generated_this_month ?? 0,
                'limit' => $this->plan->exams_per_month ?? 0,
                'unlimited' => $this->plan->hasUnlimitedExams()
            ],
            'questions' => [
                'used' => $this->subscription->questions_generated_this_month ?? 0,
                'limit' => $this->plan->max_questions_per_month ?? 0,
                'unlimited' => $this->plan->hasUnlimitedQuestionsPerMonth()
            ],
            'features' => [
                'pdf_export' => $this->plan->allowsFeature('pdf_export'),
                'word_export' => $this->plan->allowsFeature('word_export'),
                'youtube_quiz' => $this->plan->allowsFeature('youtube_quiz'),
                'ppt_quiz' => $this->plan->allowsFeature('ppt_quiz'),
                'answer_key' => $this->plan->allowsFeature('answer_key'),
                'white_label' => $this->plan->allowsFeature('white_label'),
                'watermark' => $this->plan->allowsFeature('watermark'),
                'priority_support' => $this->plan->allowsFeature('priority_support'),
                'multi_teacher' => $this->plan->allowsFeature('multi_teacher'),
            ]
        ];
    }
}
