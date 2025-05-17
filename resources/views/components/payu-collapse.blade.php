<label for="cardNumber" class="form-label mt-3">Card Details:</label>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <input class="form-control" type="text" name="payu_card" placeholder="Card Number">
    </div>

    <div class="col-md-2">
        <input class="form-control" type="text" name="payu_cvc" placeholder="CVC">
    </div>

    <div class="col-md-1">
        <input class="form-control" type="text" name="payu_month" placeholder="MM" maxlength="2">
    </div>

    <div class="col-md-1">
        <input class="form-control" type="text" name="payu_year" placeholder="YY" maxlength="2">
    </div>

    <div class="col-md-2">
        <select name="payu_network" class="form-select" id="">
            <option value="">Select</option>
            <option value="visa">VISA</option>
            <option value="amex">AMEX</option>
            <option value="diners">DINERS</option>
            <option value="mastercard">MASTERCARD</option>
        </select>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-5">
        <input class="form-control" type="text" name="payu_name" placeholder="Cardholder Name">
    </div>

    <div class="col-md-5">
        <input class="form-control" type="email" name="payu_email" placeholder="email@example.com">
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col">
        <small class="form-text text-muted">
            Your payment will be converted to {{ strtoupper(config('services.payu.base_currency')) }}
        </small>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col">
        <small class="form-text text-danger" id="paymentErros" role="alert"></small>
    </div>
</div>
