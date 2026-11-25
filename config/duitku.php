<?php

$production = env('DUITKU_ENV', 'sandbox') === 'production';

return [
    'merchant_code' => env('DUITKU_MERCHANT_CODE'),
    'api_key' => env('DUITKU_API_KEY'),
    'env' => $production ? 'production' : 'sandbox',
    'expiry_minutes' => (int) env('DUITKU_EXPIRY_MINUTES', 1440),

    'base_url' => $production ? 'https://api-prod.duitku.com' : 'https://api-sandbox.duitku.com',
    'js_url' => $production ? 'https://app-prod.duitku.com/lib/js/duitku.js' : 'https://app-sandbox.duitku.com/lib/js/duitku.js',
    'paths' => [
        'create_invoice' => '/api/merchant/createInvoice',
        'transaction_status' => '/api/merchant/transactionStatus',
    ],

    'timeout' => 15,
    'retries' => 2,
    'retry_sleep_ms' => 300,

    'payment_names' => [
        'BK' => 'BNI Virtual Account',
        'SP' => 'ShopeePay',
        'BC' => 'BCA Virtual Account',
        'M2' => 'Mandiri Virtual Account',
        'BR' => 'BRI Virtual Account',
        'I1' => 'BNI Virtual Account',
        'BT' => 'Permata Virtual Account',
        'B1' => 'CIMB Niaga Virtual Account',
        'A1' => 'ATM Bersama',
        'OV' => 'OVO',
        'DA' => 'DANA',
        'LA' => 'LinkAja',
        'VC' => 'Kartu kredit',
        'IR' => 'Indomaret',
        'NQ' => 'QRIS',
    ],
];
