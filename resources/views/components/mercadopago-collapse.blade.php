<label for="cardNumber" class="form-label mt-3">Card Details:</label>

<div class="row g-3 mb-3">
    <div class="col-md-5">
        <input class="form-control" type="text" id="cardNumber" data-checkout="cardNumber" placeholder="Card Number"
            >
    </div>

    <div class="col-md-2">
        <input class="form-control" type="text" data-checkout="securityCode" placeholder="CVC" >
    </div>

    <div class="col-md-1">
        <input class="form-control" type="text" data-checkout="cardExpirationMonth" placeholder="MM" maxlength="2"
            >
    </div>

    <div class="col-md-1">
        <input class="form-control" type="text" data-checkout="cardExpirationYear" placeholder="YY" maxlength="2"
            >
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-5">
        <input class="form-control" type="text" data-checkout="cardholderName" placeholder="Cardholder Name"
            >
    </div>

    <div class="col-md-5">
        <input class="form-control" type="email" data-checkout="cardholderEmail" placeholder="email@example.com"
            name="email">
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-2">
        <select class="form-select" id="docType" data-checkout="docType" >
            <option value="" disabled selected>Select Document Type</option>
        </select>
    </div>

    <div class="col-md-3">
        <input class="form-control" type="text" data-checkout="docNumber" placeholder="Document Number" >
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col">
        <small class="form-text text-muted">
            Your payment will be converted to COP {{ strtoupper(config('services.mercadopago.base_currency')) }}
        </small>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col">
        <small class="form-text text-danger" id="paymentErros" role="alert"></small>
    </div>
</div>

<input type="hidden" id="cardNetwork" name="card_network">
<input type="hidden" id="cardToken" name="card_token">

@push('scripts')
    <script src="https://sdk.mercadopago.com/js/v2"></script>
    <script>
        const mp = new MercadoPago("{{ config('services.mercadopago.key') }}");

        mp.getIdentificationTypes().then(function(response) {
            const docTypeSelect = document.getElementById('docType');
            response.forEach(function(type) {
                const option = document.createElement('option');
                option.value = type.id;
                option.textContent = type.name;
                docTypeSelect.appendChild(option);
            });
        }).catch(function(error) {
            console.error('Error loading document types:', error);
        });
    </script>

    <script>
        function setCardNetwork() {
            const cardNumber = document.getElementById("cardNumber");
            mp.getPaymentMethod({
                "bin": cardNumber.value.substring(0, 6)
            }, function(status, response) {
                const cardNetwork = document.getElementById("cardNetwork");
                cardNetwork.value = response[0].id;
            });
        }
    </script>

    <script>
        const mercadoPagoForm = document.getElementById("paymentForm");

        mercadoPagoForm.addEventListener('submit', function(e) {
            if (mercadoPagoForm.elements.payment_platform.value === "{{ $paymentPlatform->id }}") {
                e.preventDefault();

                mp.createToken(mercadoPagoForm, function(status, response) {
                    if (status != 200 && status != 201) {
                        const errors = document.getElementById("paymentErrors");

                        errors.textContent = response.cause[0].description;
                    } else {
                        const cardToken = document.getElementById("cardToken");

                        setCardNetwork();

                        cardToken.value = response.id;

                        mercadoPagoForm.submit();
                    }
                });
            }
        });
    </script>
@endpush
