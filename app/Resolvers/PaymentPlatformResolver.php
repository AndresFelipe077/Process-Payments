<?php

namespace App\Resolvers;

use Exception;
use App\Models\PaymentPlatform;

class PaymentPlatformResolver
{

    protected $paymentPlatforms;

    public function __construct()
    {
        $this->paymentPlatforms = PaymentPlatform::all();
    }

    public function resolveService(string|int $paymentPlatformId)
    {
        $name = strtolower($this->paymentPlatforms->firstWhere('id', $paymentPlatformId)->name);

        $service = config("services.{$name}.class");

        if ($service) {
            return resolve($service);
        }

        throw new Exception("The selected payment platform is not in the configuration");
    }
}
