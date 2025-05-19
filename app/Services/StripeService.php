<?php

declare(strict_types=1);

namespace App\Services;

use App\Traits\ConsumesExternalServices;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;

class StripeService
{
    use ConsumesExternalServices;

    protected $key;
    protected $secret;
    protected $baseUri;
    protected $plans;

    public function __construct()
    {
        $this->baseUri = config('services.stripe.base_uri');
        $this->key     = config('services.stripe.key');
        $this->secret  = config('services.stripe.secret');
        $this->plans   = config('services.stripe.plans');
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
        return "Bearer {$this->secret}";
    }

    public function handlePayment(Request $request): Redirector | RedirectResponse
    {
        $request->validate([
            'payment_method' => 'required',
        ]);

        $intent = $this->createIntent($request->value, $request->currency, $request->payment_method);

        session()->put('paymentIntentId', $intent->id);

        return redirect()->route('approval');
    }

    public function handleApproval(): RedirectResponse|View
    {
        if (session()->has('paymentIntentId')) {
            $paymentIntentId = session()->get('paymentIntentId');

            $confirmation = $this->confirmPayment($paymentIntentId);

            if ($confirmation->status === 'requires_action') {
                $clientSecret = $confirmation->client_secret;

                return view('stripe.3d-secure')->with([
                    'clientSecret' => $clientSecret,
                ]);
            }

            if ($confirmation->status === 'succeeded') {
                $name = $confirmation->customer
                    ?? (
                        isset($confirmation->changes)
                        && isset($confirmation->changes->data[0]->billing_details->name)
                        ? $confirmation->changes->data[0]->billing_details->name
                        : auth('web')->user()->name
                    );
                $currency = strtoupper($confirmation->currency);
                $amount   = $confirmation->amount / $this->resolveFactor($currency);

                return redirect()
                    ->route('home')
                    ->withSuccess(['payment' => "Thanks, {$name}. We received your {$amount}{$currency} payment."]);
            }
        }

        return redirect()
            ->route('home')
            ->withErrors('We were unable to confirm your payment. Try again, please');
    }

    public function handleSubscription(Request $request): Redirector | RedirectResponse | View
    {
        $customer = $this->createCustomer(
            $request->user()->name,
            $request->user()->email,
            $request->payment_method
        );

        $subscription = $this->createSubscription(
            $customer->id,
            $request->payment_method,
            $this->plans[$request->plan]
        );

        if ($subscription->status === 'active') {
            session()->put('subscriptionId', $subscription->id);
            return redirect()
                ->route(
                    'subscribe.approval',
                    [
                        'plan'            => $request->plan,
                        'subscription_id' => $subscription->id,
                    ]
                )
                ->withSuccess(['payment' => 'Thanks for your subscription.']);
        }

        $paymentIntentId = $subscription->latest_invoice->payment_intent;

        if ($paymentIntentId->status === 'requires_action') {
            $clientSecret = $paymentIntentId->client_secret;

            session()->put('subscriptionId', $subscription->id);

            return view('stripe.3d-secure-subscription')->with([
                'clientSecret'   => $clientSecret,
                'plan'           => $request->plan,
                'paymentMethod'  => $request->payment_method,
                'subscriptionId' => $subscription->id,
            ]);
        }

        return redirect()
            ->route('subscribe.show')
            ->withErrors('We were unable to active your subscription. Try again, please');
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

    public function createIntent(float|int|string $value, string $currency, string $paymentMethod): mixed
    {
        return $this->makeRequest(
            'POST',
            '/v1/payment_intents',
            [],
            [
                'amount'              => round($value * $this->resolveFactor($currency)),
                'currency'            => strtolower($currency),
                'payment_method'      => $paymentMethod,
                'confirmation_method' => 'manual',
                'payment_method_types' => ['card'],
            ],
        );
    }

    public function confirmPayment(string $paymentIntentId): mixed
    {
        return $this->makeRequest(
            'POST',
            "/v1/payment_intents/{$paymentIntentId}/confirm",
        );
    }

    public function createCustomer(string $name, string $email, string $paymentMethod): mixed
    {
        return $this->makeRequest(
            'POST',
            '/v1/customers',
            [],
            [
                'name'           => $name,
                'email'          => $email,
                'payment_method' => $paymentMethod,
            ],
        );
    }

    public function createSubscription(string $customerId, string $paymentMethod, string|float $priceId)
    {
        return $this->makeRequest(
            'POST',
            '/v1/subscriptions',
            [],
            [
                'customer' => $customerId,
                'items' => [
                    ['price' => $priceId],
                ],
                'default_payment_method' => $paymentMethod,
                'expand' => ['latest_invoice.payment_intent']
            ],
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
