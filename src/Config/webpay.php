<?php

return [
    'api_key'            => env('WEBPAY_API_KEY', null),
    'plus' => [
        'return_url'     => env('WEBPAY_PLUS_RETURN_URL', '/webpay'),
        'debug'          => env('WEBPAY_PLUS_DEBUG', true),
        'commerce_code'  => env('WEBPAY_PLUS_COMMERCE_CODE', null)
    ],
    'mall' => [
        'return_url'     => env('WEBPAY_MALL_RETURN_URL', '/webpay-mall'),
        'debug'          => env('WEBPAY_MALL_DEBUG', true),
        'commerce_code'  => env('WEBPAY_MALL_COMMERCE_CODE', null)
    ]
];
