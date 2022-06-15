<?php

namespace Crealab\WebpayPlusPaymentGateway;

use Crealab\PaymentGateway\Payment;
use Crealab\PaymentGateway\Contracts\PaymentGatewayInterface;
use Crealab\WebpayPlusPaymentGateway\Models\WebpayPlusPayment;
use Exception;
use DateTime;
use Illuminate\Support\Facades\DB;
use Transbank\Webpay\WebpayPlus\Transaction;
use Transbank\Webpay\WebpayPlus;
use Crealab\PaymentGateway\Models\PaymentModel;

class WebpayPlusGateway implements PaymentGatewayInterface{
    private $returnUrl;
    private $isTesting;
    private const MAX_REFUND_DAYS = 7;

    public function __construct(){
        $this->returnUrl = config('webpay.plus.return_url');
        $this->isTesting = config('webpay.plus.debug');
        if(!$this->isTesting){
            WebpayPlus::configureForProduction( config('webpay.plus.commerce_code'), config('webpay.plus.api_key'));
        }
    }

    public function charge($payment){
        $paymentModel = $payment->getPersistentData();
        $WPPPayment = WebpayPlusPayment::fromPayment($paymentModel);
        $WPPPayment->save();
        $payment->beforeProcess($paymentModel);
        $response = (new Transaction)->create($WPPPayment->buy_order, $paymentModel->id, ($payment->amount - $payment->discount), url($this->returnUrl) );
        $WPPPayment->submit_url = $response->getUrl();
        $WPPPayment->token_ws = $response->getToken();
        $WPPPayment->save();
        $paymentModel->implementation()->associate($WPPPayment)->save();
        return $WPPPayment;
    }

    private function findTokenOnRequest(){
        $request = request();
        return  $request->TBK_TOKEN ?? $request->token_ws;
    }

    public function findPayment($uid = null):PaymentModel{
        $token = is_null($uid) ? $this->findTokenOnRequest() : $uid;
        $WPPPayment = WebpayPlusPayment::where('token_ws', $token)->first();
        if(is_null($WPPPayment)){
            throw new Exception("Webpay Mall Payment nor found");
        }
        if($WPPPayment->payment->isPending()){ 
            $this->commitTransaction($WPPPayment);
        }
        return $WPPPayment->payment;
    }

    public function captureTransacation($token, $amount){
        $WPPPayment = WebpayPlusPayment::where('token_ws', $token)->first();
        if($WPPPayment->amount < $amount){
            throw new Exception("The captured amount can't be greater than the transaction amount");
        }
        return (new Transaction)->capture($token, $WPPPayment->buy_order, $WPPPayment->authorization_code, $amount);
    }

    private function commitTransaction(WebpayPlusPayment $WPPPayment){
        try {
            $response = (new Transaction)->commit($WPPPayment->token_ws);
        } catch (\Throwable $th) { //Manejar error
            $WPPPayment->payment->setStatus('rejected');
        }
        if($response->getResponseCode() != 0){
            $WPPPayment->payment->setStatus('rejected');
        }else {
            $WPPPayment->payment->setStatus('accepted');
            $WPPPayment->payment->recreatePayment()->afterProcess($WPPPayment->payment);
        }
        $this->saveWebpayTransactionData($WPPPayment, $response);
    }

    public function refund($payment, int $amount){
        if((new DateTime($payment->created_at))->diff(new DateTime())->days > self::MAX_REFUND_DAYS){
            throw new Exception("This payment cannot be refund");
        }
        return (new Transaction)->refund($payment->token_ws, $amount);
    }

    private function saveWebpayTransactionData($WPPPayment, $webpayResponse){
        $cardDetail = $webpayResponse->getCardDetail();

        $payment = $WPPPayment->payment;
        $payment->fees_number = $webpayResponse->getInstallmentsNumber();
        $payment->fee_amount = $webpayResponse->getInstallmentsAmount();
        $payment->save();

        $WPPPayment->authorization_code = $webpayResponse->getAuthorizationCode();
        $WPPPayment->card_number = isset($cardDetail['card_number']) ? $cardDetail['card_number'] : 0000;
        $WPPPayment->card_expiration =  isset($cardDetail->cardExpirationDate) ? $cardDetail->cardExpirationDate : NULL ; 
        $WPPPayment->webpay_payment_type_id = DB::table('webpay_payment_type')->where('key', $webpayResponse->getPaymentTypeCode())->first(['id'])->id;
        $WPPPayment->save();
    }
}
