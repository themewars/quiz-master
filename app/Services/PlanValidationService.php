<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
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

        // Pick latest ACTIVE subscription; avoid strict string status checks (DB may store ints)
        $this->subscription = $this->user
            ? $this->user->subscriptions()
                ->where('status', SubscriptionStatus::ACTIVE->value)
                ->orderByDesc('id')
                ->first()
            : null;

        // Ignore expired subscriptions
        if ($this->subscription && method_exists($this->subscription, 'isExpired') && $this->subscription->isExpired()) {
            $this->subscription = null;
        }

        $this->plan = $this->subscription?->plan;

        // Fallback to default plan if no valid subscription is found
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

        // Derive actual created exams within current billing-cycle window (based on subscription frequency)
        $usedExams = 0;
        if ($this->user) {
            [$windowStart, $windowEnd] = $this->getCurrentBillingWindow();

            $usedExams = Quiz::where('user_id', $this->user->id)
                ->whereBetween('created_at', [$windowStart, $windowEnd])
                ->count();
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
     * Compute current billing window [start, end] based on subscription frequency.
     */
    private function getCurrentBillingWindow(): array
    {
        $now = Carbon::now();

        // Fallback to calendar month if no subscription
        if (!$this->subscription) {
            return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];
        }

        $startsAt = $this->subscription->starts_at ? Carbon::parse($this->subscription->starts_at) : $now->copy()->startOfMonth();
        $endsAt = $this->subscription->ends_at ? Carbon::parse($this->subscription->ends_at) : null;

        // Determine frequency: 7 days (weekly), 1 month (monthly), 1 year (yearly). Defaults to monthly.
        $frequency = (int) ($this->subscription->plan_frequency ?? 0);
        // 0/unknown => monthly, 1 => weekly, 2 => yearly (based on PlanFrequency enum ordering in codebase)
        $cycleStart = $startsAt->copy();

        // Move cycleStart forward in steps until it is the start of the current cycle containing now
        if ($frequency === 1) { // weekly
            while ($cycleStart->addWeek()->lte($now)) {}
            $cycleStart = $cycleStart->subWeek();
            $cycleEnd = $cycleStart->copy()->addWeek()->subSecond();
        } elseif ($frequency === 2) { // yearly
            while ($cycleStart->addYear()->lte($now)) {}
            $cycleStart = $cycleStart->subYear();
            $cycleEnd = $cycleStart->copy()->addYear()->subSecond();
        } else { // monthly (default)
            while ($cycleStart->addMonth()->lte($now)) {}
            $cycleStart = $cycleStart->subMonth();
            $cycleEnd = $cycleStart->copy()->addMonth()->subSecond();
        }

        // Respect hard subscription end date if present
        if ($endsAt && $cycleEnd->gt($endsAt)) {
            $cycleEnd = $endsAt->copy();
        }

        return [$cycleStart, $cycleEnd];
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
