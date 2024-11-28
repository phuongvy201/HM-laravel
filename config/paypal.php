<?php

return [
    'sandbox' => [
        'client_id' => env('PAYPAL_SANDBOX_CLIENT_ID'),
        'client_secret' => env('PAYPAL_SANDBOX_CLIENT_SECRET'),
        'account_id' => '62WZJHDWTFC4E', // ID tài khoản sandbox business của bạn
        'email' => 'sb-6lva934335495@business.example.com', // Email sandbox business
        'url' => 'https://api.sandbox.paypal.com'
    ]
];
