<?php

namespace Crealab\WebpayPlusPaymentGateway;

use Transbank\Webpay\WebpayPlus\MallTransaction;
use Crealab\PaymentGateway\Contracts\PaymentGatewayInterface;
use Crealab\WebpayPlusPaymentGateway\Models\WebpayMallPayment;
use Crealab\WebpayPlusPaymentGateway\Models\WebpayMallSubTransaction;
use Illuminate\Support\Facades\DB;
use Crealab\PaymentGateway\Payment;
use Crealab\PaymentGateway\Models\PaymentModel;
use Transbank\Webpay\WebpayPlus;
use DateTime;
use Exception;

class WebpayMallGateway implements PaymentGatewayInterface{
    private $returnUrl;
    private $isTesting;
    private const MAX_REFUND_DAYS = 90;

    public function __construct(){
        $this->returnUrl = config('webpay.mall.return_url');
        $this->isTesting = config('webpay.mall.debug');
        if(!$this->isTesting){
            WebpayPlus::configureForProduction( config('webpay.mall.commerce_code'), config('webpay.mall.api_key'));
        }
    }

    private function getSubTransactions($payment){
        $subTransactions = collect($payment->detail);
        return $subTransactions->map(function($item){
            return new WebpayMallSubTransaction($item);
        });
    }
    
    public function charge(Payment $payment){
        $paymentModel = $payment->getPersistentData();
        $WMPayment = new WebpayMallPayment([
            'amount'    => ($payment->amount - $payment->discount),
            'buy_order' => $payment->buyOrder(),
            'return_url'=> config('webpay.mall.return_url'),
        ]);
        $WMPayment->save();
        $WMPayment->subTransactions()->saveMany($this->getSubTransactions($payment));
        $paymentModel->implementation()->associate($WMPayment)->save();
        $payment->beforeProcess($paymentModel);
        $response = (new MallTransaction)->create($WMPayment->buy_order, $paymentModel->id, url($this->returnUrl) , $payment->detail );
        $WMPayment->submit_url = $response->getUrl();
        $WMPayment->token_ws = $response->getToken();
        $WMPayment->save();
        return $WMPayment;
    }

    private function findTokenOnRequest(){
        $request = request();
        return  $request->TBK_TOKEN ?? $request->token_ws;
    }

    public function findPayment($uid = null):PaymentModel{
        $token = is_null($uid) ? $this->findTokenOnRequest() : $uid;
        $WMPayment = WebpayMallPayment::where('token_ws', $token)->first();
        if(is_null($WMPayment)){
            throw new Exception("Webpay Mall Payment nor found");
        }
        if($WMPayment->payment->isPending()){ 
            $this->commitTransaction($WMPayment);
        }else{
            $WMPayment = $this->updateWebpayTransactionData($WMPayment, (new MallTransaction)->status($token));
        }
        return $WMPayment->payment;
    }

    private function commitTransaction(WebpayMallPayment $WMPayment){
        try {
            $response = (new MallTransaction)->commit($WMPayment->token_ws);
            $this->updateWebpayTransactionData($WMPayment, $response);
        } catch (\Throwable $th) { //Manejar error
            $WMPayment->payment->setStatus('rejected');
        }
        if($response->isApproved()){
            $WMPayment->payment->recreatePayment()->afterProcess($WMPayment->payment);
        }
    }

    private function updateWebpayTransactionData(WebpayMallPayment $WMPayment, $response){
        $cardDetail = $response->getCardDetail();
        $WMPayment->buy_order = $response->getBuyOrder();
        $WMPayment->card_number = isset($cardDetail['card_number']) ? $cardDetail['card_number'] : 0000;
        $WMPayment->card_expiration =  isset($cardDetail->cardExpirationDate) ? $cardDetail->cardExpirationDate : NULL ; 
        $details = $response->getDetails();
        foreach($details as $detail){ 
            $subTransaction = $WMPayment->subTransactions()->where('buy_order', $detail->getBuyOrder())->first();
            $subTransaction->amount = $detail->getAmount();
            $subTransaction->authorization_code = $detail->getAuthorizationCode();
            $subTransaction->payment_fees = $detail->getInstallmentsNumber();
            $type= DB::table('webpay_payment_type')->where('key', $detail->getPaymentTypeCode())->first(['id']);
            $subTransaction->webpay_payment_type_id = isset($type) ? $type->id : null;
            $subTransaction->response_code = $detail->getResponseCode();
        }
        $response->isApproved() ? $WMPayment->payment->setStatus('accepted'): $WMPayment->payment->setStatus('rejected'); 
        return $WMPayment;
    }

    public function refund($payment, int $amount){
        if((new DateTime($payment->created_at))->diff(new DateTime())->days > self::MAX_REFUND_DAYS){
            throw new Exception("This payment cannot be refund");
        }
        throw new Exception("Not implemented");
    }

    public function captureTransacation(){
        throw new Exception("Not implemented");
    }

}