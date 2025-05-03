<label for="cardNumber" class="form-label mt-3">Card Details:</label>

<div class="row g-3 mb-3">
    <div class="col-md-5">
        <input class="form-control" type="text" id="cardNumber" data-checkout="cardNumber" placeholder="Card Number"
            required>
    </div>

    <div class="col-md-2">
        <input class="form-control" type="text" data-checkout="securityCode" placeholder="CVC" required>
    </div>

    <div class="col-md-1">
        <input class="form-control" type="text" data-checkout="cardExpirationMonth" placeholder="MM" maxlength="2"
            required>
    </div>

    <div class="col-md-1">
        <input class="form-control" type="text" data-checkout="cardExpirationYear" placeholder="YY" maxlength="2"
            required>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-5">
        <input class="form-control" type="text" data-checkout="cardholderName" placeholder="Cardholder Name"
            required>
    </div>

    <div class="col-md-5">
        <input class="form-control" type="email" data-checkout="cardholderEmail" placeholder="email@example.com"
            name="email" required>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-2">
        <select class="form-select" id="docType" data-checkout="docType" required>
            <option value="" disabled selected>Select Document Type</option>
        </select>
    </div>

    <div class="col-md-3">
        <input class="form-control" type="text" data-checkout="docNumber" placeholder="Document Number" required>
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
@endpush
