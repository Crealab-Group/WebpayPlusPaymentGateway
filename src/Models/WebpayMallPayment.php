<?php

namespace Crealab\WebpayPlusPaymentGateway\Models;

use Crealab\PaymentGateway\Models\GatewayPayment;
use Crealab\PaymentGateway\Models\Util\BelongsToPayment;

class WebpayMallPayment extends GatewayPayment {
    use BelongsToPayment;
    
    protected $table = 'webpay_mall_payment';

    protected $fillable = ['amount', 'buy_order', 'return_url', 'final_url','discount'];

    public function subTransactions(){
        return $this->hasMany(WebpayMallSubTransaction::class);
    }

    public function webpayPaymentType(){
        return $this->belongsTo(WebpayPlusPaymentType::class);
    }

}
