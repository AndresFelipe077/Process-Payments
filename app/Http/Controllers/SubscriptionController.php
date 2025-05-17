<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;
use App\Models\PaymentPlatform;
use App\Resolvers\PaymentPlatformResolver;
use Illuminate\Contracts\View\View;

class SubscriptionController extends Controller
{

    protected $paymentPlatformResolver;

    public function __construct(PaymentPlatformResolver $paymentPlatformResolver)
    {
        $this->middleware('auth');
        $this->paymentPlatformResolver = $paymentPlatformResolver;
    }

    public function show(): View
    {
        $paymentPlaftforms = PaymentPlatform::/*where('subscriptions_enabled', true)->*/get();
        return view('subscribe')->with([
            'plans'            => Plan::all(),
            'paymentPlatforms' => $paymentPlaftforms
        ]);
    }

    public function store(Request $request)
    {
        $rules = [
            'plan'             => ['required', 'exists:plans,slug'],
            'payment_platform' => ['required', 'exists:payment_platforms,id'],
        ];

        $request->validate($rules);

        $paymentPlatform = $this->paymentPlatformResolver->resolveService($request->payment_platform);

        session()->put('subscriptionPlatformId', $request->payment_platform);

        return $paymentPlatform->handleSubscription($request);
    }

    public function approval() {}

    public function cancelled() {}
}
