<?php

namespace App\Http\Controllers;

use App\Http\Middleware\Unsubscribed;
use App\Resolvers\PaymentPlatformResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\View\View;

class PaymentController extends Controller
{

    protected $paymentPlatformResolver;

    public function __construct(PaymentPlatformResolver $paymentPlatformResolver)
    {
        $this->middleware(['auth', Unsubscribed::class]);
        $this->paymentPlatformResolver = $paymentPlatformResolver;
    }

    public function pay(Request $request): Redirector|RedirectResponse|View
    {
        $rules = [
            'value'            => ['required', 'numeric', 'min:5'],
            'currency'         => ['required', 'exists:currencies,iso'],
            'payment_platform' => ['required', 'exists:payment_platforms,id']
        ];

        $request->validate($rules);

        $paymentPlatform = $this->paymentPlatformResolver->resolveService($request->payment_platform);

        session()->put('paymentPlatformId', $request->payment_platform);

        if ($request->user()->hasActiveSubscription()) {
            $request->value = round($request->value * 0.9, 2);
        }

        return $paymentPlatform->handlePayment($request);
    }

    public function approval(): RedirectResponse|View
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
