<?php

namespace App\Services;

use App\Traits\ConsumesExternalServices;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;

class MercadoPagoService
{
    use ConsumesExternalServices;

    protected $baseUri;
    protected $key;
    protected $secret;
    protected $baseCurrency;
    protected $converter;

    public function __construct(CurrencyConversionService $converter)
    {
        $this->baseUri      = config('services.mercadopago.base_uri');
        $this->key          = config('services.mercadopago.key');
        $this->secret       = config('services.mercadopago.secret');
        $this->baseCurrency = config('services.mercadopago.base_currency');
        $this->converter    = $converter;
    }

    public function resolveAuthorization(array &$queryParams, array &$formParams, array &$headers): void
    {
        $queryParams['access_token'] = $this->resolveAccessToken();
    }

    public function decodeResponse($response): mixed
    {
        return json_decode($response);
    }

    public function resolveAccessToken(): string
    {
        return $this->secret;
    }

    public function handlePayment(Request $request): Redirector | RedirectResponse
    {
        dd($request->all());
    }

    public function handleApproval(): RedirectResponse {}

    public function createPayment(
        string|int|float $value,
        string $currency,
        string $cardNetwork,
        string $cardToken,
        string $email,
        int $installments
    ): mixed {
        return $this->makeRequest(
            'POST',
            '/v1/payments',
            [],
            [
                'payer' => [
                    'email' => $email,
                ],
                'binary_mode'          => true,
                'transaction_amount'   => round($value * $this->resolveFactor($currency)),
                'payment_method_id'    => $cardNetwork,
                'token'                => $cardToken,
                'installments'         => $installments,
                'statement_descriptor' => config('app.name'),
            ],
            [],
            isJsonRequest: true
        );
    }

    public function resolveFactor(string $currency): int
    {
        return $this->converter->convertCurrency($currency, $this->baseCurrency);
    }
}
