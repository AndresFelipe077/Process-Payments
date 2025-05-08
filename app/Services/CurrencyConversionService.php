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
            '/v1/convert',
            [
                'from'   => $from,
                'to'     => $to,
                'amount' => $amount
            ]
        );
        return $response;
    }

    // public function convertCurrency(string $from, string $to, string|float $amount = 1): mixed
    // {
    //     $response = $this->makeRequest(
    //         'GET',
    //         '/v1/latest',
    //         [
    //             'access_key' => env('EXCHANGERATES_API_KEY'),
    //             'symbols' => strtoupper($from) . ',' . strtoupper($to)
    //         ]
    //     );

    //     // Validar éxito
    //     if (!$response['success'] ?? false) {
    //         return [
    //             'error' => 'No se pudo obtener tasas de cambio',
    //             'details' => $response['error'] ?? null,
    //         ];
    //     }

    //     $rates = $response['rates'];

    //     if (!isset($rates[$from]) || !isset($rates[$to])) {
    //         return [
    //             'error' => 'Monedas no válidas',
    //             'available_rates' => array_keys($rates),
    //         ];
    //     }

    //     // Cálculo del factor FROM → TO (si base es EUR)
    //     $factor = $rates[$to] / $rates[$from];
    //     $converted = $amount * $factor;

    //     return [
    //         'success' => true,
    //         'from' => $from,
    //         'to' => $to,
    //         'rate' => round($factor, 6),
    //         'amount' => $amount,
    //         'converted' => round($converted, 2)
    //     ];
    // }
}
