<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;
use App\Models\PaymentPlatform;
use App\Resolvers\PaymentPlatformResolver;

class SubscriptionController extends Controller
{

    protected $paymentPlatformResolver;

    public function __construct(PaymentPlatformResolver $paymentPlatformResolver)
    {
        $this->middleware('auth');
        $this->paymentPlatformResolver = $paymentPlatformResolver;
    }

    public function show()
    {
        $paymentPlaftforms = PaymentPlatform::/*where('subscriptions_enabled', true)->*/get();
        return view('subscribe')->with([
            'plans'            => Plan::all(),
            'paymentPlatforms' => $paymentPlaftforms
        ]);
    }
    public function store() {}
    public function approval() {}
    public function cancelled() {}
}
