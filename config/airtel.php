<?php

return [
    "environnement" => env('AIRTEL_MONEY_TARGET_ENVIRONNEMENT', 'sandbox'),

    "endpoints" => [
        "collection_uri" => env('AIRTEL_MONEY_BASE_URL') . "/merchant/v1/payments/",
        "disbursement_uri" => env('AIRTEL_MONEY_BASE_URL') . "/standard/v1/disbursements/",
        "token_uri" => env('AIRTEL_MONEY_BASE_URL') . "/auth/oauth2/token",
        "encryption_uri" => env('AIRTEL_MONEY_BASE_URL') . "/v1/rsa/encryption-keys",
        "collection_pay_status" => env('AIRTEL_MONEY_BASE_URL') . "/standard/v1/payments/",
        "disbursement_pay_status" => env('AIRTEL_MONEY_BASE_URL') . "/standard/v1/disbursements/",
    ],

    "credentials" => [
        "client_id" => env('AIRTEL_MONEY_CLIENT_ID'),
        "client_secret" => env('AIRTEL_MONEY_CLIENT_SECRET'),
        "grant_type" => "client_credentials",
        "encrypted_pin" => env('AIRTEL_MONEY_DISBURSEMENT_PIN_ENCRYPT'),
    ],

    "location" => [
        "country" => env('AIRTEL_MONEY_COUNTRY', 'CG'),
        "currency" => env('AIRTEL_MONEY_CURRENCY', 'XAF'),
    ],

    "headers" => [
        "content_type" => "application/json",
        "accept" => "*/*"
    ],
];
