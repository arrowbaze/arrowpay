<?php

return [

    // Credentials
    'merchant_key'   => env('ARROWPAY_MERCHANT_KEY'),
    'client_id'      => env('ARROWPAY_CLIENT_ID'),
    'client_secret'  => env('ARROWPAY_CLIENT_SECRET'),

    // API endpoints
    'initialize_url'          => env('ARROWPAY_INITIALIZE_URL', 'https://api.orange.com/orange-money-webpay/ml/v1/webpayment'),
    'transaction_status_url'  => env('ARROWPAY_TRANSACTION_STATUS_URL', 'https://api.orange.com/orange-money-webpay/ml/v1/transactionstatus'),

    'token_url'  => env('ARROWPAY_GENERATION_URL', 'https://api.orange.com/oauth/v3/token'),

        // Default routes
    'routes' => [
        'return' => env('ARROWPAY_RETURN_ROUTE', 'arrowpay/return/{orderId}'),
        'cancel' => env('ARROWPAY_CANCEL_ROUTE', 'arrowpay/cancel'),
        'notify' => env('ARROWPAY_NOTIFY_ROUTE', 'arrowpay/notify'),
    ],

    // Defaults
    'currency' => env('ARROWPAY_CURRENCY', 'XOF'),
    'license_key' => env('ARROWPAY_LICENSE_KEY')
];
