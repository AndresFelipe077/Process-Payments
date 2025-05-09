<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Traits\ConsumesExternalServices;

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

    // public function convertCurrency(string $from, string $to, string|float $amount = 0): mixed
    // {
    //     $response = $this->makeRequest(
    //         'GET',
    //         '/v1/convert',
    //         [
    //             'from'   => $from,
    //             'to'     => $to,
    //             'amount' => $amount
    //         ]
    //     );
    //     return $response;
    // }

    public function convertCurrency(string $from, string $to): int|float|array
    {
        $from = strtoupper($from);
        $response = $this->makeRequest(
            'GET',
            '/v1/latest',
            []
        );

        if (!($response->success ?? false)) {
            return [
                'error'   => 'No se pudo obtener tasas de cambio',
                'details' => $response->error ?? null,
            ];
        }

        $rates = (array) $response->rates;

        if (!isset($rates[$from]) || !isset($rates[$to]) || $rates[$from] == 0) {
            return [
                'error'           => 'Monedas no válidas o divisor es cero',
                'available_rates' => array_keys($rates),
            ];
        }

        $factor = $rates[$to] / $rates[$from];

        return round($factor, 6);
    }
}
