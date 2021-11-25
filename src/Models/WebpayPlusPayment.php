<?php

namespace Crealab\WebpayPlusPaymentGateway\Models;

use Crealab\PaymentGateway\Models\GatewayPayment;
use Crealab\PaymentGateway\Models\PaymentModel;

class WebpayPlusPayment extends GatewayPayment {
    protected $table = 'webpay_plus_payment';

    protected $fillable = ['amount', 'buy_order', 'return_url', 'final_url','discount'];

    public function getWebpayArray(){
        return [$this->amount, $this->buy_order, $this->id, $this->return_url, $this->final_url];
    }

    public static function fromPayment(PaymentModel $payment){
        $data = new self([
            'amount'    => ($payment->amount - $payment->discount),
            'buy_order' => self::generateBuyOrder(),
            'return_url'=> config('webpay.WEBPAY_RETURN_URL'),
        ]);
        $data->save();
        return $data;
    }

    private static function generateBuyOrder(){
        $date = date("dmYhi");
        return 'WPP-'.$date;
    }
}
