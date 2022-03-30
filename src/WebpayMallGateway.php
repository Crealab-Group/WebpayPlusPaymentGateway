<?php

namespace Crealab\WebpayPlusPaymentGateway;

use Transbank\Webpay\WebpayPlus\MallTransaction;
use Crealab\PaymentGateway\Contracts\PaymentGatewayInterface;
use Crealab\WebpayPlusPaymentGateway\Models\WebpayMallPayment;
use Crealab\WebpayPlusPaymentGateway\Models\WebpayMallSubTransaction;
use Illuminate\Support\Facades\DB;
use Crealab\PaymentGateway\Payment;
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
            WebpayPlus::configureForProduction( config('webpay.mall.commerce_code'), config('webpay.api_key'));
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
            'return_url'=> config('webpay.plus.return_url'),
        ]);
        $WMPayment->subTransactions()->saveMany($this->getSubTransactions($payment));
        $payment->beforeProcess($WMPayment);
        $response = (new MallTransaction)->create($WMPayment->buy_order, $paymentModel->id, $WMPayment->amount, url($this->returnUrl) , $payment->detail );
        $WMPayment->submit_url = $response->getUrl();
        $WMPayment->token_ws = $response->getToken();
        $WMPayment->payment_id = $paymentModel->id;
        $WMPayment->save();
        return $WMPayment;
    }

    private function findTokenOnRequest(){
        $request = request();
        return  $request->TBK_TOKEN ?? $request->token_ws;
    }

    public function findPayment($token = null):Payment{
        $token = is_null($token) ? $this->findTokenOnRequest() : $token;
        $WMPayment = WebpayMallPayment::where('token_ws', $token)->first();
        if($WMPayment->payment->isPending()){ 
            $this->commitTransaction($WMPayment);
        }else{
            $WMPayment = $this->updateWebpayTransactionData($WMPayment, (new MallTransaction)->status($token));
        }
        return $WMPayment;
    }

    private function commitTransaction(WebpayMallPayment $WMPayment){
        try {
            $response = (new MallTransaction)->commit($WMPayment->token_ws);
            $this->updateWebpayTransactionData($WMPayment, $response);
            if($response->isApproved()){
                $WMPayment->payment->recreatePayment()->afterProcess($WMPayment);
            }
        } catch (\Throwable $th) { //Manejar error
            $WMPayment->payment->setStatus('rejected');
            $WMPayment->subTransactions->each(function($subtransaction){
                $subtransaction->setStatus('rejected');
            });
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
            $subTransaction->webpay_payment_type_id = DB::table('webpay_payment_type')->where('key', $detail->getPaymentTypeCode())->first(['id'])->id;
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