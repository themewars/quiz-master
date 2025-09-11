<?php

namespace App\Http\Middleware;

use App\Services\PlanValidationService;
use Closure;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlanFeature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        // Bind validation to the currently authenticated user explicitly to avoid context issues
        $planCheck = (new PlanValidationService(auth()->user()))->canUseFeature($feature);

        if (!($planCheck['allowed'] ?? false)) {
            Notification::make()
                ->danger()
                ->title('Feature Not Available')
                ->body($planCheck['message'] ?? 'This feature is not available in your current plan')
                ->send();
            
            return redirect()->back();
        }

        return $next($request);
    }
}
