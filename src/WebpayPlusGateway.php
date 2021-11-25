<?php

namespace Crealab\WebpayPlusPaymentGateway;

use Crealab\PaymentGateway\Payment;
use Crealab\PaymentGateway\Contracts\PaymentGatewayInterface;
use Crealab\WebpayPlusPaymentGateway\Models\WebpayPlusPayment;
use Exception;
use Illuminate\Support\Facades\DB;
use Transbank\Webpay\WebpayPlus\Transaction;
use Transbank\Webpay\WebpayPlus;

class WebpayPlusGateway implements PaymentGatewayInterface{
    private $returnUrl;
    private $isTesting;

    public function __construct(){
        $this->returnUrl = config('webpay.RETURN_URL');
        $this->isTesting = config('webpay.TESTING');
        if(!$this->isTesting){
            WebpayPlus::configureForProduction( config('webpay.COMMERCE_CODE'), config('webpay.PI_KEY'));
        }
    }

    public function charge(Payment $payment){
        $paymentModel = $payment->getPersistentData();
        $WPPPayment = WebpayPlusPayment::fromPayment($paymentModel);
        $payment->beforeProcess($WPPPayment);
        $response = (new Transaction)->create($WPPPayment->buy_order, $paymentModel->id, ($payment->amount - $payment->discount), url($this->returnUrl) );
        $WPPPayment->submit_url = $response->getUrl();
        $WPPPayment->token_ws = $response->getToken();
        $WPPPayment->payment_id = $paymentModel->id;
        $WPPPayment->save();
        return $WPPPayment;
    }

    public function showPayment(){
        $request = request();
        $token = isset($request->TBK_TOKEN) ? $request->TBK_TOKEN : $request->token_ws;
        $WPPPayment = WebpayPlusPayment::where('token_ws', $token)->first();
        if($WPPPayment->payment->payment_status_id == 1){ //no esta resuelta
            $this->commitTransaction($WPPPayment);
        }
        return $WPPPayment;
    }

    private function commitTransaction(WebpayPlusPayment $WPPPayment){
        try {
            $response = (new Transaction)->commit($WPPPayment->token_ws);
            if($response->getResponseCode() != 0){
                $WPPPayment->payment->setStatus('rejected');
            }else {
                $WPPPayment->payment->setStatus('accepted');
                $WPPPayment->payment->recreatePayment()->afterProcess($WPPPayment);
            }
            $this->saveWebpayTransactionData($WPPPayment, $response);
        } catch (\Throwable $th) {
            $WPPPayment->payment->setStatus('rejected');
        }
    }



    public function refund($payment, int $amount){
        //TODO Verificar que no tiene mas de 7 dias xd
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
        $WPPPayment->card_expiration =  isset($cardDetail->cardExpirationDate) ? $cardDetail->cardExpirationDate : NULL ; //no estoy seguro de que este llegando hmm
        $WPPPayment->webpay_payment_type_id = DB::table('webpay_payment_type')->where('key', $webpayResponse->getPaymentTypeCode())->first(['id'])->id;
        $WPPPayment->save();
    }

    public static function __callStatic($name, $arguments)
    {
        $staticCallMap = [
            'makeCharge' => 'charge',
            'findPayment'=> 'showPayment',
            'makeRefund' => 'refund'
        ];
        if( !array_key_exists($name, $staticCallMap) ){
            throw new Exception('Method not found exception');
        }
        $gateway  = new self();
        $method = $staticCallMap[$name];
        return $gateway->$method(...$arguments);
    }

}
