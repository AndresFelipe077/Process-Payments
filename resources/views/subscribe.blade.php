@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Subscribe') }}</div>

                    <div class="card-body">
                        <form action="{{ route('subscribe.store') }}" method="POST" id="paymentForm">
                            @csrf

                            @php
                                use Illuminate\Support\Str;
                            @endphp

                            <div class="row mt-3">
                                <div class="col">
                                    <label for="payment_platform">Select your plan:</label>
                                    <div class="form-group" id="toggler">
                                        <div class="btn-group" role="group" aria-label="Payment Platforms">
                                            @foreach ($plans as $plan)
                                                <label class="btn btn-outline-info rounded m-2 p-3">
                                                    <input type="radio" name="plan" value="{{ $plan->slug }}"
                                                        required>
                                                    <p class="h2 font-weight-bold text-capitalize">
                                                        {{ $plan->slug }}
                                                    </p>

                                                    <p class="display-4 text-capitalize">
                                                        {{ $plan->visual_price }}
                                                    </p>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>

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

                            <div class="text-center mt-3">
                                <button type="submit" id="payButton" class="btn btn-primary btn-lg">Subscribe</button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
