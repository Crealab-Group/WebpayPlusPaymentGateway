<?php

namespace Crealab\WebpayPlusPaymentGateway;

use Crealab\WebpayPlusPaymentGateway\WebpayPlusGateway;

class WebpayPlus{
    
    public function __call($method, $parameters)
    {
        return (new WebpayPlusGateway)->$method(...$parameters);
    }


    public static function __callStatic($method, $parameters)
    {
        $instance = new static;
        return call_user_func( [$instance, $method], ...$parameters );
    }
}