@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Dashboard') }}</div>

                    <div class="card-body">
                        <form action="{{ route('pay') }}" method="POST" id="paymentForm">
                            @csrf

                            <div class="row">
                                <div class="col-auto">
                                    <label for="">How much you want to pay?</label>
                                    <input type="number" min="5" step="0.01" class="form-control" name="value"
                                        value="{{ mt_rand(500, 100000) / 100 }}" required>
                                    <small class="form-text text-muted">
                                        Use values with up to two decimal positions, using dot "."
                                    </small>
                                </div>
                                <div class="col-auto">
                                    <label for="">Currency</label>
                                    <select class="custom-select" name="currency" required id="">
                                        @foreach ($currencies as $currency)
                                            <option value="{{ $currency->iso }}"> {{ strtoupper($currency->iso) }} </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            @php
                                use Illuminate\Support\Str;
                            @endphp

                            <div class="row mt-3">
                                <div class="col">
                                    <label for="payment_platform">Select the desired payment platform:</label>
                                    <div class="form-group" id="toggler">
                                        <div class="btn-group" role="group" aria-label="Payment Platforms">
                                            @foreach ($paymentPlatforms as $paymentPlatform)
                                                <input type="radio" class="btn-check" name="payment_platform"
                                                    id="payment_platform_{{ $paymentPlatform->id }}"
                                                    value="{{ $paymentPlatform->id }}" autocomplete="off"
                                                    @if (old('payment_platform') == $paymentPlatform->id) checked @endif required
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#{{ Str::slug($paymentPlatform->name) }}Collapse">
                                                <label class="btn btn-outline-secondary rounded m-2 p-1"
                                                    for="payment_platform_{{ $paymentPlatform->id }}">
                                                    <img class="img-thumbnail" src="{{ asset($paymentPlatform->image) }}"
                                                        alt="{{ $paymentPlatform->name }}">
                                                </label>
                                            @endforeach
                                        </div>

                                        @foreach ($paymentPlatforms as $paymentPlatform)
                                            <div id="{{ Str::slug($paymentPlatform->name) }}Collapse"
                                                class="collapse @if (old('payment_platform') == $paymentPlatform->id) show @endif"
                                                data-bs-parent="#toggler">
                                                @includeIf(
                                                    'components.' .
                                                        strtolower($paymentPlatform->name) .
                                                        '-collapse')
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>


                            <div class="row">
                                <div class="col-auto">
                                    <p class="border-bottom border-primary rounded">
                                        @if (!optional(auth()->user())->hasActiveSubscription())
                                            Would you like a discount every time?
                                            <a href="#">Subscribe</a>
                                        @else
                                        You get a <span class="font-weight-bold">10% off</span> as part of your subscription (will be applied in the checkout)
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="text-center mt-3">
                                <button type="submit" id="payButton" class="btn btn-primary btn-lg">Pay</button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
