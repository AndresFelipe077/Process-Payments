@push('style')
    <style>
        .StripElement {
            box-sizing: border-box;
            height: 40px;
            padding: 10px 12px;
            border: 1px solid transparent;
            border-radius: 4px;
            background-color: white;
            box-shadow: 0 1px 3px 0 #e6ebf1;
        }
    </style>
@endpush

<div class="container mt-5">
    <label class="mt-3">Card details:</label>
    <div id="cardElement" class="StripElement"></div>
    <small class="form-text text-muted" id="cardErrors" role="alert"></small>
</div>

@push('scripts')

    <script src="https://js.stripe.com/v3/"></script>
    <script>
        const stripe = Stripe("{{ config('services.stripe.key') }}");
        const elements = stripe.elements({
            locale: 'en'
        });

        try {
            const cardElement = elements.create('card');
            cardElement.mount('#cardElement');
            console.log('Card element montado');
        } catch (e) {
            console.error('Error montando el elemento de tarjeta:', e);
        }

    </script>
@endpush
