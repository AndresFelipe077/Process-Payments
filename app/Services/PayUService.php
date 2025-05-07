<?php

namespace App\Services;

use App\Traits\ConsumesExternalServices;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Str;

class PayUService
{
    use ConsumesExternalServices;

    protected $baseUri;
    protected $key;
    protected $secret;
    protected $baseCurrency;
    protected $merchantId;
    protected $accountId;
    protected $converter;

    public function __construct(CurrencyConversionService $converter)
    {
        $this->baseUri      = config('services.payu.base_uri');
        $this->key          = config('services.payu.key');
        $this->secret       = config('services.payu.secret');
        $this->merchantId   = config('services.payu.merchant_id');
        $this->accountId    = config('services.payu.account_id');
        $this->baseCurrency = strtoupper(config('services.payu.base_currency'));
        $this->converter    = $converter;
    }

    public function resolveAuthorization(array &$queryParams, array &$formParams, array &$headers): void
    {
        $formParams['merchant']['apiKey']   = $this->key;
        $formParams['merchant']['apiLogin'] = $this->secret;
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

    public function createPayment(
        string|int|float $value,
        string $currency,
        string $name,
        string $email,
        string $card,
        string $cvc,
        string $year,
        string $month,
        string $network,
        int $installments = 1,
        string $paymentCountry = 'CO',
    ): mixed {

        $language = config('app.locale');

        return $this->makeRequest(
            'POST',
            '/payments-api/4.0/service.cgi',
            [],
            [
                'languague'   => $language,
                'command'     => 'SUBMIT_TRANSACTION',
                'test'        => false,
                'transaction' => [
                    'type'            => 'AUTHORIZATION_AND_CAPTURE',
                    'paymentMethod'   => strtoupper($network),
                    'paymentCountry'  => strtoupper($paymentCountry),
                    'deviceSessionId' => session()->getId(),
                    'ipAddress'       => request()->ip(),
                    'userAgent'       => request()->header('User-Agent'),
                    'creditCard'    => [
                        'number'         => $card,
                        'securityCode'   => $cvc,
                        'expirationDate' => "{$year}/{$month}",
                        'name'           => 'APPROVED',
                    ],
                    'extraParamenters' => [
                        'INSTALLMENTS_NUMBER' => $installments,
                    ],
                    'payer' => [
                        'fullName'     => $name,
                        'emailAddress' => $email,
                    ],
                    'order' => [
                        'accountId'     => $this->accountId,
                        'referenceCode' => $reference = Str::random(12),
                        'description'   => 'Testing PayU',
                        'language'      => $language,
                        'signature'     => $this->generateSignature($reference, $value = round($value * $this->resolveFactor($currency))),
                        'additionalValues' => [
                            'TX_VALUE' => [
                                'value'    => $value,
                                'currency' => $this->baseCurrency,

                            ],
                        ],
                        'buyer' => [
                            'fullName'     => $name,
                            'emailAddress' => $email,
                            'shippingAddress' => [
                                'street1' => '',
                                'city'    => '',
                            ]
                        ]
                    ]
                ],
            ],
            [
                'Accept' => 'application/json',
            ],
            isJsonRequest: true
        );
    }

    public function resolveFactor(string $currency): int
    {
        return $this->converter->convertCurrency($currency, $this->baseCurrency);
    }

    public function generateSignature(string $referenceCode, string|int|float $value): string
    {
        return md5("{$this->key}~{$this->merchantId}~{$referenceCode}~{$value}~{$this->baseCurrency}");
    }
}
