<?php

namespace App\Http\Controllers;

use App\Resolvers\PaymentPlatformResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;

class PaymentController extends Controller
{

    protected $paymentPlatformResolver;

    public function __construct(PaymentPlatformResolver $paymentPlatformResolver)
    {
        $this->middleware('auth');
        $this->paymentPlatformResolver = $paymentPlatformResolver;
    }

    public function pay(Request $request): Redirector|RedirectResponse
    {
        $rules = [
            'value'            => ['required', 'numeric', 'min:5'],
            'currency'         => ['required', 'exists:currencies,iso'],
            'payment_platform' => ['required', 'exists:payment_platforms,id']
        ];

        $request->validate($rules);

        $paymentPlatform = $this->paymentPlatformResolver->resolveService($request->payment_platform);

        session()->put('paymentPlatformId', $request->payment_platform);

        return $paymentPlatform->handlePayment($request);
    }

    public function approval(): RedirectResponse
    {
        if (session()->has('paymentPlatformId')) {

            $paymentPlatformId = session()->get('paymentPlatformId');

            $paymentPlatform = $this->paymentPlatformResolver->resolveService($paymentPlatformId);

            return $paymentPlatform->handleApproval();
        }

        return redirect()
            ->route('home')
            ->withErrors('We cannot retrieve your payment platform. Try again, please.');
    }

    public function cancelled(): RedirectResponse
    {
        return redirect()
            ->route('home')
            ->withErrors('You cancelled the payment');
    }
}
