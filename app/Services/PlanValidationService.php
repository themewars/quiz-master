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
        try {
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
                $this->plan = Plan::where('assign_default', true)->where('status', true)->first();
                
                // If still no plan found, log error and use a minimal fallback
                if (!$this->plan) {
                    \Log::error('No default plan found in PlanValidationService');
                    $this->plan = null;
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error in PlanValidationService constructor: ' . $e->getMessage());
            $this->user = null;
            $this->subscription = null;
            $this->plan = null;
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

        // Prefer new field `exams_per_month`; fall back to legacy `no_of_exam` if needed
        $examsPerMonth = $this->plan->exams_per_month;
        if ($examsPerMonth === null || $examsPerMonth === 0) {
            $examsPerMonth = $this->plan->no_of_exam ?? 0;
        }

        // Use subscription counter which is updated when exams are created and respects billing cycles
        $usedExams = $this->subscription->exams_generated_this_month ?? 0;

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
        try {
            $now = Carbon::now();

            // Fallback to calendar month if no subscription
            if (!$this->subscription) {
                return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];
            }

            // Validate subscription dates
            $startsAt = $this->subscription->starts_at ? Carbon::parse($this->subscription->starts_at) : $now->copy()->startOfMonth();
            $endsAt = $this->subscription->ends_at ? Carbon::parse($this->subscription->ends_at) : null;

            // Ensure startsAt is not in the future
            if ($startsAt->gt($now)) {
                $startsAt = $now->copy()->startOfMonth();
            }

            // Determine frequency based on PlanFrequency enum values
            $frequency = (int) ($this->subscription->plan_frequency ?? 2); // Default to monthly
            // 1 => weekly, 2 => monthly, 3 => yearly (based on PlanFrequency enum values)
            $cycleStart = $startsAt->copy();

            // Calculate the current billing cycle start and end dates safely
            if ($frequency === 1) { // weekly
                $weeksDiff = $cycleStart->diffInWeeks($now);
                $cycleStart = $cycleStart->addWeeks($weeksDiff);
                $cycleEnd = $cycleStart->copy()->addWeek()->subSecond();
            } elseif ($frequency === 3) { // yearly
                $yearsDiff = $cycleStart->diffInYears($now);
                $cycleStart = $cycleStart->addYears($yearsDiff);
                $cycleEnd = $cycleStart->copy()->addYear()->subSecond();
            } else { // monthly (default) - frequency === 2
                $monthsDiff = $cycleStart->diffInMonths($now);
                $cycleStart = $cycleStart->addMonths($monthsDiff);
                $cycleEnd = $cycleStart->copy()->addMonth()->subSecond();
            }

            // Respect hard subscription end date if present
            if ($endsAt && $cycleEnd->gt($endsAt)) {
                $cycleEnd = $endsAt->copy();
            }

            return [$cycleStart, $cycleEnd];
        } catch (\Exception $e) {
            \Log::error('Error calculating billing window: ' . $e->getMessage());
            // Fallback to current month
            $now = Carbon::now();
            return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];
        }
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
        try {
            if (!$this->subscription) {
                \Log::warning('Attempted to update usage for non-existent subscription');
                return;
            }

            // Validate input parameters
            if ($examsGenerated < 0 || $questionsGenerated < 0) {
                \Log::error('Negative usage values provided: exams=' . $examsGenerated . ', questions=' . $questionsGenerated);
                return;
            }

            // Check if we need to reset usage for new billing cycle
            $this->resetUsageIfNewCycle();

            $currentExams = $this->subscription->exams_generated_this_month ?? 0;
            $currentQuestions = $this->subscription->questions_generated_this_month ?? 0;

            $this->subscription->update([
                'exams_generated_this_month' => $currentExams + $examsGenerated,
                'questions_generated_this_month' => $currentQuestions + $questionsGenerated,
            ]);

            \Log::info('Usage updated successfully', [
                'user_id' => $this->user?->id,
                'subscription_id' => $this->subscription->id,
                'exams_added' => $examsGenerated,
                'questions_added' => $questionsGenerated,
                'total_exams' => $currentExams + $examsGenerated,
                'total_questions' => $currentQuestions + $questionsGenerated,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error updating usage: ' . $e->getMessage(), [
                'user_id' => $this->user?->id,
                'subscription_id' => $this->subscription?->id,
                'exams_generated' => $examsGenerated,
                'questions_generated' => $questionsGenerated,
            ]);
        }
    }

    /**
     * Reset usage if we're in a new billing cycle
     */
    private function resetUsageIfNewCycle(): void
    {
        if (!$this->subscription) {
            return;
        }

        $lastResetDate = $this->subscription->usage_reset_date ? Carbon::parse($this->subscription->usage_reset_date) : null;
        $now = Carbon::now();

        // If no reset date or we're past the reset date, reset usage
        if (!$lastResetDate || $now->gte($lastResetDate)) {
            [$windowStart, $windowEnd] = $this->getCurrentBillingWindow();
            
            $this->subscription->update([
                'exams_generated_this_month' => 0,
                'questions_generated_this_month' => 0,
                'usage_reset_date' => $windowEnd->addSecond(),
            ]);
        }
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
     * Force reset usage counters to match actual quiz count
     */
    public function forceResetUsageCounters(): void
    {
        if (!$this->subscription || !$this->user) {
            return;
        }

        // Count actual quizzes created by user
        $actualQuizCount = Quiz::where('user_id', $this->user->id)->count();
        
        $this->subscription->update([
            'exams_generated_this_month' => $actualQuizCount,
            'questions_generated_this_month' => 0,
            'usage_reset_date' => Carbon::now()->addMonth(),
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

        // Prefer new field `exams_per_month`; fall back to legacy `no_of_exam` if needed
        $examsPerMonth = $this->plan->exams_per_month;
        if ($examsPerMonth === null || $examsPerMonth === 0) {
            $examsPerMonth = $this->plan->no_of_exam ?? 0;
        }

        return [
            'exams' => [
                'used' => $this->subscription->exams_generated_this_month ?? 0,
                'limit' => $examsPerMonth,
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
                'website_quiz' => $this->plan->allowsFeature('website_quiz'),
                'pdf_to_exam' => $this->plan->allowsFeature('pdf_to_exam'),
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
