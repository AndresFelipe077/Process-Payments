<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use App\Traits\ConsumesExternalServices;

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
            'payu_card'    => 'required',
            'payu_cvc'     => 'required',
            'payu_year'    => 'required',
            'payu_month'   => 'required',
            'payu_network' => 'required',
            'payu_name'    => 'required',
            'payu_email'   => 'required',
        ]);

        $payment = $this->createPayment(
            $request->value,
            $request->currency,
            $request->payu_name,
            $request->payu_email,
            $request->payu_card,
            $request->payu_cvc,
            $request->payu_year,
            $request->payu_month,
            $request->payu_network,
        );

        Log::debug(print_r($payment, true));

        if ($payment->transactionResponse->state === "APPROVED") {
            $name     = $payment->payu_name ?? auth('web')->user()->name;
            $currency = strtoupper($payment->value);
            $amount   = strtoupper($request->currency);

            return redirect()
                ->route('home')
                ->withSuccess(['payment' => "Thanks, {$name}. We received your {$amount}{$currency} payment."]);
        }

        return redirect()
            ->route('home')
            ->withErrors('We were unable to process your payment. Check your details and try again, please');
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
