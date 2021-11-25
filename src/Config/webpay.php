<?php

return [
    'RETURN_URL'     => env('WEBPAY_RETURN_URL', '/webpay'),
    'TESTING'        => env('WEBPAY_TESTING', true),
    'API_KEY'        => env('WEBPAY_APP_KEY_PATH', null),
    'COMMERCE_CODE'  => env('WEBPAY_COMMERCE_CODE', null)
];
