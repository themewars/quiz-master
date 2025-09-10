<?php

namespace App\Http\Middleware;

use App\Enums\SubscriptionStatus;
use App\Models\Quiz;
use App\Models\Subscription;
use App\Services\PlanValidationService;
use Closure;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class CheckNoOfQuiz
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        if (Route::currentRouteName() == 'filament.user.resources.quizzes.create') {
            $planCheck = app(PlanValidationService::class)->canCreateExam();

            if (!($planCheck['allowed'] ?? false)) {
                Notification::make()
                    ->danger()
                    ->title($planCheck['message'] ?? __('messages.quiz.reached_maximum_no_of_quiz'))
                    ->send();
                return redirect()->route('filament.user.resources.quizzes.index');
            }
        }


        return $next($request);
    }
}
