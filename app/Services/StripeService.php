<?php

declare(strict_types=1);

namespace App\Services;

use App\Traits\ConsumesExternalServices;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;

class StripeService
{
    use ConsumesExternalServices;

    protected $key;
    protected $secret;
    protected $baseUri;

    public function __construct()
    {
        $this->baseUri = config('services.stripe.base_uri');
        $this->key     = config('services.stripe.key');
        $this->secret  = config('services.stripe.secret');
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

    public function handlePayment(Request $request): Redirector | RedirectResponse {}

    public function handleApproval(): RedirectResponse {}

    public function createIntent(float|int $value, string $currency, string $paymentMethod): mixed
    {
        return $this->makeRequest(
            'POST',
            '/v1/payment_intents',
            [],
            [
                'amount'              => (int) round($value * $this->resolveFactor($currency)),
                'currency'            => strtolower($currency),
                'payment_method'      => $paymentMethod,
                'confirmation_method' => 'manual',
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

    public function resolveFactor(string $currency): int
    {
        $zeroDecimalCurrencies = ['JPY'];

        if (in_array(strtoupper($currency), $zeroDecimalCurrencies)) {
            return 1;
        }

        return 100;
    }
}
