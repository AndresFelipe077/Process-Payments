<?php

namespace App\Http\Controllers;

use App\Services\PaypalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;

class PaymentController extends Controller
{
    public function pay(Request $request): Redirector|RedirectResponse
    {
        $rules = [
            'value'            => ['required', 'numeric', 'min:5'],
            'currency'         => ['required', 'exists:currencies,iso'],
            'payment_platform' => ['required', 'exists:payment_platforms,id']
        ];

        $request->validate($rules);

        $paymentPlatform = resolve(PaypalService::class);

        return $paymentPlatform->handlePayment($request);
    }

    public function approval(): RedirectResponse
    {
        $paymentPlatform = resolve(PaypalService::class);
        return $paymentPlatform->handleApproval();
    }

    public function cancelled() {}
}
