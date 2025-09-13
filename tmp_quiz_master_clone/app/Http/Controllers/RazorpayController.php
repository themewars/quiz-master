<?php

namespace App\Http\Controllers;

use App\Actions\Subscription\CreateSubscription;
use App\Models\PaymentSetting;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Transaction;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RazorpayController extends AppBaseController
{
    public function purchase(Request $request)
    {
        try {
            $plan = $request->json('plan', $request->input('plan'));
            if (is_string($plan)) {
                $decoded = json_decode($plan, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $plan = $decoded;
                }
            }

            if (!is_array($plan) || empty($plan['id'])) {
                return response()->json(['message' => 'Invalid plan payload.'], 422);
            }

            $razorpayPayment = PaymentSetting::first();
            if (!$razorpayPayment || empty($razorpayPayment->razorpay_key) || empty($razorpayPayment->razorpay_secret)) {
                return response()->json(['message' => 'Razorpay is not configured. Please set key/secret in Payment Settings.'], 422);
            }

            $api = new Api($razorpayPayment->razorpay_key, $razorpayPayment->razorpay_secret);

            $amountPaise = (int) round(($plan['payable_amount'] ?? 0) * 100);
            if ($amountPaise <= 0) {
                return response()->json(['message' => 'Payable amount must be greater than zero.'], 422);
            }

            $currency = isset($plan['currency']['code']) ? strtoupper($plan['currency']['code']) : 'INR';

            $orderData = [
                'receipt' => (string) ($plan['id'] ?? '1'),
                'amount' => $amountPaise,
                'currency' => $currency,
                'notes' => [
                    'plan_id' => $plan['id'],
                    'payable_amount' => $plan['payable_amount'] ?? 0,
                    'payment_mode' => Subscription::TYPE_RAZORPAY,
                ],
            ];

            $razorpayOrder = $api->order->create($orderData);

            $data['order_id'] = $razorpayOrder->id;
            $data['payable_amount'] = $plan['payable_amount'] ?? 0;
            $data['currency'] = $currency;
            $data['payment_mode'] = Subscription::TYPE_RAZORPAY;
            $data['plan_id'] =  $plan['id'];

            return $this->sendResponse($data, 'Razorpay order created successfully.');
        } catch (\Throwable $e) {
            Log::error('Razorpay purchase error: '.$e->getMessage(), [
                'exception' => $e,
            ]);
            return response()->json(['message' => 'Payment initialization failed.'], 500);
        }
    }

    public function success(Request $request)
    {
        $razorpayPayment = getPaymentSetting();
        $api = new Api($razorpayPayment->razorpay_key, $razorpayPayment->razorpay_secret);
        $response = $api->payment->fetch($request->razorpay_payment_id);


        $planId = $response['notes']['plan_id'] ?? null;
        $plan = Plan::find($planId);

        $generatedSignature = hash_hmac(
            'sha256',
            $response['order_id'] . '|' . $request->razorpay_payment_id,
            $razorpayPayment->razorpay_secret
        );

        $transactionID = $response['id'] ?? null;


        if ($generatedSignature !== $request->razorpay_signature) {
            Notification::make()
                ->danger()
                ->title(__('Invalid signature.'))
                ->send();

            return redirect()->route('filament.user.pages.manage-subscription');
        }

        $subscriptionAmount = $response['amount'] / 100;

        DB::beginTransaction();

        try {

            $transaction = Transaction::create([
                'transaction_id' => $transactionID,
                'type' => Subscription::TYPE_RAZORPAY,
                'amount' => $subscriptionAmount,
                'status' => Transaction::SUCCESS,
                'user_id' => getLoggedInUserId(),
            ]);


            $planData = [
                'plan' => $plan ? $plan->toArray() : [],
                'user_id' => getLoggedInUserId(),
                'payment_type' => Subscription::TYPE_RAZORPAY,
                'transaction_id' => $transaction->id,
            ];


            $subscription = CreateSubscription::run($planData);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => __('messages.subscription.subscription_created_successfully'),
                'redirect' => route('filament.user.pages.manage-subscription'),
            ]);
        } catch (HttpException $ex) {
            DB::rollBack();
            throw $ex;
        }
    }


    public function failed()
    {
        return redirect()->route('filament.user.pages.manage-subscription');
    }
}
