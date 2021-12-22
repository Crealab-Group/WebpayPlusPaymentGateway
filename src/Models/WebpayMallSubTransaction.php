<?php

namespace Crealab\WebpayPlusPaymentGateway\Models;

use Illuminate\Database\Eloquent\Model;

class WebpayMallSubTransaction extends Model {
    protected $table = "webpay_mall_payment_subtransactions";

    protected $fillable = [
        'amount',
        'buy_order',
        'commerce_code',
        'payment_fees',
        'webpay_plus_mall_payment_id'
    ];

    public function parentTransaction(){
        return $this->belongsTo(WebpayMallPayment::class);
    }
}