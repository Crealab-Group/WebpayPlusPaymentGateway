<?php

return [
    'WEBPAY_RETURN_URL'     => env('WEBPAY_RETURN_URL', 'http://127.0.0.1:8000/webpay'),
    'WEBPAY_TESTING'        => env('WEBPAY_TESTING', true),
    'WEBPAY_API_KEY'        => env('WEBPAY_APP_KEY_PATH', null),
    'WEBPAY_COMMERCE_CODE'  => env('WEBPAY_COMMERCE_CODE', null)
];
