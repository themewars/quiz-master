<?php

namespace App\Http\Responses;

use App\Models\User;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse as RegistrationResponseContract;
use Illuminate\Support\Facades\Log;

class CustomRegistrationResponse implements RegistrationResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        try {
            /** @var User $user */
            $user = auth()->user();

            // If user is authenticated, redirect to appropriate dashboard
            if ($user) {
                $role = $user->roles()->first();

                if ($role && $role->name === User::ADMIN_ROLE) {
                    return redirect()->route('filament.admin.pages.dashboard')
                        ->with('success', 'Welcome! Registration successful.');
                }

                if ($role && $role->name === User::USER_ROLE) {
                    return redirect()->route('filament.user.pages.dashboard')
                        ->with('success', 'Welcome! Registration successful.');
                }

                // If user has no role, assign default user role and redirect
                if (!$role) {
                    $user->assignRole(User::USER_ROLE);
                    return redirect()->route('filament.user.pages.dashboard')
                        ->with('success', 'Welcome! Registration successful.');
                }
            }

            // If no authenticated user, try to get user from session
            $userId = session('registered_user_id');
            if ($userId) {
                $user = User::find($userId);
                if ($user) {
                    // Log the user in
                    auth()->login($user);
                    
                    $role = $user->roles()->first();
                    if ($role && $role->name === User::ADMIN_ROLE) {
                        return redirect()->route('filament.admin.pages.dashboard')
                            ->with('success', 'Welcome! Registration successful.');
                    }
                    
                    return redirect()->route('filament.user.pages.dashboard')
                        ->with('success', 'Welcome! Registration successful.');
                }
            }

            // Final fallback: redirect to home page
            return redirect()->route('home')
                ->with('success', 'Registration successful! Please login to continue.');
                
        } catch (\Exception $e) {
            Log::error('Error in CustomRegistrationResponse: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Fallback redirect to home
            return redirect()->route('home')
                ->with('success', 'Registration completed successfully! Please login to continue.');
        }
    }
}
