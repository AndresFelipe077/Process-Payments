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

    public function __construct()
    {
        $this->baseUri      = config('services.mercadopago.base_uri');
        $this->key          = config('services.mercadopago.key');
        $this->secret       = config('services.mercadopago.secret');
        $this->baseCurrency = config('services.mercadopago.base_currency');
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
        return "";
    }

    public function handlePayment(Request $request): Redirector | RedirectResponse
    {

    }

    public function handleApproval(): RedirectResponse
    {

    }

    public function resolveFactor(string $currency): int
    {
        return 0;
    }
}
