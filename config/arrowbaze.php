<?php

return [

    // Credentials
    'merchant_key'   => env('ARROWBAZE_MERCHANT_KEY'),
    'client_id'      => env('ARROWBAZE_CLIENT_ID'),
    'client_secret'  => env('ARROWBAZE_CLIENT_SECRET'),

    // API endpoints
    'initialize_url'          => env('ARROWBAZE_INITIALIZE_URL', 'https://api.orange.com/orange-money-webpay/ml/v1/webpayment'),
    'transaction_status_url'  => env('ARROWBAZE_TRANSACTION_STATUS_URL', 'https://api.orange.com/orange-money-webpay/ml/v1/transactionstatus'),

    // Default routes
    'routes' => [
        'return' => env('ARROWBAZE_RETURN_ROUTE', 'arrowbaze/return/{orderId}'),
        'cancel' => env('ARROWBAZE_CANCEL_ROUTE', 'arrowbaze/cancel'),
        'notify' => env('ARROWBAZE_NOTIFY_ROUTE', 'arrowbaze/notify'),
    ],

    // Defaults
    'currency' => env('ARROWBAZE_CURRENCY', 'XOF'),
    'license_key' => env('ARROWBAZE_LICENSE_KEY'),

    'license_server_url' => 'https://secureapplicensearrowpay.arrowbaze.tech/api/license',
    'license_api_secret' => 'ccce99afb65c471dcc9984f464ecba5146b13dc92c01f6bc43a92ea0ae71df61',   // fixed, internal

];
