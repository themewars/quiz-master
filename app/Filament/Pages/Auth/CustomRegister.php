<?php

namespace App\Filament\Pages\Auth;

use App\Models\Plan;
use App\Models\User;
use App\Models\Subscription;
use Filament\Pages\Auth\Register;
use Filament\Events\Auth\Registered;
use Filament\Notifications\Notification;
use App\Actions\Subscription\CreateSubscription;
use App\Http\Responses\CustomRegistrationResponse;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;
use AbanoubNassem\FilamentGRecaptchaField\Forms\Components\GRecaptcha;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;

class CustomRegister extends Register
{
    /**
     * @var view-string
     */
    protected static string $view = 'filament.auth.register';

    /**
     * @return array<int | string, string | Form>
     */
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getNameFormComponent()
                            ->label(__('messages.common.name') . ':')
                            ->placeholder(__('messages.common.name')),
                        $this->getEmailFormComponent()
                            ->label(__('messages.user.email_address') . ':')
                            ->placeholder(__('messages.user.email_address')),
                        $this->getPasswordFormComponent()
                            ->label(__('messages.user.password') . ':')
                            ->placeholder(__('messages.user.password'))
                            ->extraAttributes(['class' => 'password-field']),
                        $this->getPasswordConfirmationFormComponent()
                            ->label(__('messages.user.confirm_password') . ':')
                            ->placeholder(__('messages.user.confirm_password'))
                            ->extraAttributes(['class' => 'password-field']),
                        GRecaptcha::make('captcha')->visible(function () {
                            return enableCaptcha() && checkCaptcha('enabled_captcha_in_register');
                        }),

                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getRegisterFormAction()
                ->extraAttributes(['class' => 'w-full flex items-center justify-center space-x-3 form-submit mt-5'])
                ->label(__('messages.home.sign_up')),
        ];
    }

    public function register(): ?RegistrationResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $user = $this->wrapInDatabaseTransaction(function () {

            $data = $this->form->getState();

            $data = $this->mutateFormDataBeforeRegister($data);

            $user = $this->handleRegistration($data);

            $user->assignRole(User::USER_ROLE);

            $this->form->model($user)->saveRelationships();

            return $user;
        });

        // Create subscription for new user
        try {
            $plan = Plan::where('assign_default', true)->where('status', true)->first();
            if ($plan) {
                $data['plan'] = $plan->load('currency')->toArray();
                $data['user_id'] = $user->id;
                $data['payment_type'] = Subscription::TYPE_FREE;
                if ($plan->trial_days != null && $plan->trial_days > 0) {
                    $data['trial_days'] = $plan->trial_days;
                }
                CreateSubscription::run($data);
                
                \Log::info('Subscription created successfully for new user', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'plan_id' => $plan->id,
                    'plan_name' => $plan->name
                ]);
            } else {
                \Log::error('No active default plan found during user registration', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'available_plans' => Plan::where('status', true)->count()
                ]);
                
                // Show user-friendly error message
                Notification::make()
                    ->warning()
                    ->title(__('Registration completed with limited access'))
                    ->body(__('Please contact administrator to assign a plan for full access.'))
                    ->send();
            }
        } catch (\Exception $e) {
            \Log::error('Error creating subscription during registration: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Show user-friendly error message
            Notification::make()
                ->warning()
                ->title(__('Registration completed with limited access'))
                ->body(__('There was an issue setting up your account. Please contact support.'))
                ->send();
        }

        event(new Registered($user));

        // Check if email verification is enabled in settings
        $setting = \App\Models\Setting::first();
        if ($setting && $setting->send_mail_verification) {
            try {
                $user->sendEmailVerificationNotification();
                Notification::make()
                    ->success()
                    ->title(__('messages.home.email_verification_link_sent'))
                    ->send();
            } catch (\Exception $e) {
                \Log::error('Email verification failed: ' . $e->getMessage());
                Notification::make()
                    ->warning()
                    ->title(__('Registration successful'))
                    ->body(__('Email verification could not be sent. Please contact support if needed.'))
                    ->send();
            }
        } else {
            // Auto-verify email if verification is disabled
            $user->update(['email_verified_at' => now()]);
            Notification::make()
                ->success()
                ->title(__('Registration successful'))
                ->body(__('Your account has been created successfully.'))
                ->send();
        }

        return app(CustomRegistrationResponse::class);
    }
}
