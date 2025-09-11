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

            if ($user) {
                $role = $user->roles()->first();

                if ($role && $role->name === User::ADMIN_ROLE) {
                    return redirect()->route('filament.admin.pages.dashboard');
                }

                if ($role && $role->name === User::USER_ROLE) {
                    return redirect()->route('filament.user.pages.dashboard');
                }
            }

            // If no user or role, redirect to login
            return redirect()->route('filament.auth.auth.login')
                ->with('message', 'Registration successful. Please login to continue.');
                
        } catch (\Exception $e) {
            Log::error('Error in CustomRegistrationResponse: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Fallback redirect
            return redirect()->route('filament.auth.auth.login')
                ->with('error', 'Registration completed but there was an issue. Please try logging in.');
        }
    }
}
