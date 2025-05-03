<?php

namespace App\Services;

use App\Traits\ConsumesExternalServices;
use Illuminate\Http\Request;

class CurrencyConversionService
{
    use ConsumesExternalServices;

    protected $baseUri;
    protected $apiKey;
    private $queryParam = 'access_key';

    public function __construct()
    {
        $this->baseUri = config('services.currency_conversion.base_uri');
        $this->apiKey  = config('services.currency_conversion.api_key');
    }

    public function resolveAuthorization(array &$queryParams, array &$formParams, array &$headers): void
    {
        $queryParams[$this->queryParam] = $this->resolveAccessToken();
    }

    public function decodeResponse($response): mixed
    {
        return json_decode($response);
    }

    public function resolveAccessToken(): string
    {
        return $this->apiKey;
    }

    public function convertCurrency(string $from, string $to, string|float $amount = 0): mixed
    {
        $response = $this->makeRequest(
            'GET',
            '',
            [
                'from'   => $from,
                'to'     => $to,
                'amount' => $amount
            ]
        );
        return $response;
    }
}
