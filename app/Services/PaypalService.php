<?php

namespace App\Services;

use App\Traits\ConsumesExternalServices;

class PaypalService
{
    use ConsumesExternalServices;

    protected $baseUri;
    protected $clientId;
    protected $clientSecret;

    public function __construct()
    {
        $this->baseUri      = config('services.paypal.base_uri');
        $this->clientId     = config('services.paypal.client_id');
        $this->clientSecret = config('services.paypal.client_secret');
    }

    public function resolveAuthorization(array &$queryParams, array &$formParams, array &$headers)
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

    public function createOrder(float $value, string $currency): mixed
    {
        return $this->makeRequest(
            'POST',
            '/v2/checkout/orders',
            [],
            [
                'intent'         => 'CAPTURE',
                'purchase_units' => [
                    0 => [
                        'amount' => [
                            'value'         => $value,
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
}
