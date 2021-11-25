<?php

use Crealab\WebpayPlusPaymentGateway\Models\WebpayPlusPayment;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasWebpayPlusPayment{
    public function webpayPlusPayments():HasMany{
        return $this->hasMany(WebpayPlusPayment::class);
    }
}