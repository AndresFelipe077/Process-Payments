<?php

namespace App\Services;

use App\Traits\ConsumesExternalServices;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;

class PaypalService
{
    use ConsumesExternalServices;

    protected $baseUri;
    protected $clientId;
    protected $clientSecret;
    protected $plans;

    public function __construct()
    {
        $this->baseUri      = config('services.paypal.base_uri');
        $this->clientId     = config('services.paypal.client_id');
        $this->clientSecret = config('services.paypal.client_secret');
        $this->plans        = config('services.paypal.plans');
    }

    public function resolveAuthorization(array &$queryParams, array &$formParams, array &$headers): void
    {
        $headers['Authorization'] = $this->resolveAccessToken();
    }

    public function decodeResponse($response): mixed
    {
        return json_decode($response);
    }

    public function resolveAccessToken(): string
    {
        $credentials = base64_encode("{$this->clientId}:{$this->clientSecret}");
        return "Basic {$credentials}";
    }

    public function handlePayment(Request $request): Redirector | RedirectResponse
    {
        $order = $this->createOrder($request->value, $request->currency);

        $orderLinks = collect($order->links);

        $approve = $orderLinks->where('rel', 'approve')->first();

        session()->put('approvalId', $order->id);

        return redirect($approve->href);
    }

    public function handleApproval(): RedirectResponse
    {
        if (session()->has('approvalId')) {
            $approvalId = session()->get('approvalId');

            $paymentData = $this->capturePayment($approvalId);
            $payment     = $paymentData->purchase_units[0]->payments->captures[0]->amount;

            $name     = $paymentData->payer->name->given_name;
            $amount   = $payment->value;
            $currency = $payment->currency_code;

            return redirect()
                ->route('home')
                ->withSuccess(['payment' => "Thanks, {$name}. We received your {$amount}{$currency} payment."]);
        }

        return redirect()
            ->route('home')
            ->withErrors('We cannot capture your payment. Try again, please');
    }

    public function handleSubscription(Request $request): Redirector | RedirectResponse
    {
        $subscription = $this->createSubscription(
            $request->plan,
            $request->user()->name,
            $request->user()->email
        );

        $subcriptionLinks = collect($subscription->links);

        $approve = $subcriptionLinks->where('rel', 'approve')->first();

        session()->put('subscriptionId', $subscription->id);

        return redirect($approve->href);
    }

    public function validateSubscription(Request $request): bool
    {
        if (session()->has('subscriptionId')) {
            $subscriptionId = session()->get('subscriptionId');
            session()->forget('subscriptionId');
            return $request->subscription_id == $subscriptionId;
        }

        return false;
    }

    public function createOrder(float $value, string $currency): mixed
    {
        $factor = $this->resolveFactor($currency);
        return $this->makeRequest(
            'POST',
            '/v2/checkout/orders',
            [],
            [
                'intent'         => 'CAPTURE',
                'purchase_units' => [
                    0 => [
                        'amount' => [
                            'value'         => round($value * $factor) / $factor,
                            'currency_code' => strtoupper($currency),
                        ],
                    ]
                ],
                'application_context' => [
                    'brand_name'           => config('app.name'),
                    'shipping_preferences' => 'NO_SHIPPING',
                    'return_url'           => route('approval'),
                    'cancel_url'           => route('cancelled'),
                ]
            ],
            [],
            $isJsonRequest = true
        );
    }

    public function capturePayment(string|int $approvalId): mixed
    {
        return $this->makeRequest(
            'POST',
            "/v2/checkout/orders/{$approvalId}/capture",
            [],
            [],
            [
                'Content-Type' => 'application/json',
            ],
        );
    }

    public function createSubscription($planSlug, $name, $email): mixed
    {
        return $this->makeRequest(
            'POST',
            '/v1/billing/subscriptions',
            [],
            [
                'plan_id' => $this->plans[$planSlug],
                'subscriber' => [
                    'name' => [
                        'given_name' => $name,
                    ],
                    'email_address'  => $email,
                ],
                'application_context' => [
                    'brand_name'          => config('app.name'),
                    'shipping_preference' => 'NO_SHIPPING',
                    'user_action'         => 'SUBSCRIBE_NOW',
                    'return_url'          => route('subscribe.approval', ['plan' => $planSlug]),
                    'cancel_url'          => route('subscribe.cancelled'),
                ]
            ],
            [],
            $isJsonRequest = true,
        );
    }

    public function resolveFactor(string $currency): int
    {
        $zeroDecimalCurrencies = ['JPY'];

        if (in_array(strtoupper($currency), $zeroDecimalCurrencies)) {
            return 1;
        }

        return 100;
    }
}
