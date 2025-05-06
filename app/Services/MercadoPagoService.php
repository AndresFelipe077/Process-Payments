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
        $request->validate([
            'card_network' => 'required',
            'card_token'   => 'required',
            'email'        => 'required',
        ]);

        $payment = $this->createPayment(
            $request->value,
            $request->currency,
            $request->card_network,
            $request->card_token,
            $request->email,
        );

        if ($payment->status === "approved") {
            $name = $payment->payer->first_name ?? auth('web')->user()->name;
            $currency = strtoupper($payment->currency_id);
            $amount   = number_format($payment->transaction_amount, 0, ',', '.');

            $originalAmount   = $request->value;
            $originalCurrency = strtoupper($request->currency);

            return redirect()
                ->route('home')
                ->withSuccess(['payment' => "Thanks, {$name}. We received your {$originalAmount}{$originalCurrency} payment ({$amount}{$currency})."]);
        }

        return redirect()
            ->route('home')
            ->withErrors('We were unable to confirm your payment. Try again, please');
    }

    public function handleApproval(Request $request): RedirectResponse {}

    public function createPayment(
        string|int|float $value,
        string $currency,
        string $cardNetwork,
        string $cardToken,
        string $email,
        int $installments = 1
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
