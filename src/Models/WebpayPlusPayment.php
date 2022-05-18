<?php

namespace Crealab\WebpayPlusPaymentGateway\Models;

use Crealab\PaymentGateway\Models\GatewayPayment;
use Crealab\PaymentGateway\Models\PaymentModel;
use Crealab\PaymentGateway\Models\Util\BelongsToPayment;

class WebpayPlusPayment extends GatewayPayment {
    use BelongsToPayment;
    protected $table = 'webpay_plus_payment';

    protected $fillable = ['amount', 'buy_order', 'return_url', 'final_url','discount'];

    public function getWebpayArray(){
        return [$this->amount, $this->buy_order, $this->id, $this->return_url, $this->final_url];
    }

    public function webpayPaymentType(){
        return $this->belongsTo(WebpayPlusPaymentType::class);
    }

    public static function fromPayment(PaymentModel $payment){
        $data = new self([
            'amount'    => ($payment->amount - $payment->discount),
            'buy_order' => self::generateBuyOrder(),
            'return_url'=> config('webpay.plus.return_url'),
        ]);
        $data->save();
        return $data;
    }

    private static function generateBuyOrder(){
        return 'WP-'.date("dmYhi");
    }
}
