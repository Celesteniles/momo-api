<?php

return [
    "endpoints" => [
        "token_uri" => "https://proxy.momoapi.mtn.com/:target/token/", // collection - disbursement
        "pay_uri" => "https://proxy.momoapi.mtn.com/:target/v1_0/:action", // deposit - requesttopay
        "pay_status" => "https://proxy.momoapi.mtn.com/:target/v1_0/:action/", // deposit - requesttopay
        "account_balance" => "https://proxy.momoapi.mtn.com/disbursement/v1_0/account/balance"
    ],
    "headers" => [
        "token" => [
            "ocp_apim_subscription_key" => [env('MTN_SUBSCRIPTION_KEY_COLLECTION'), env('MTN_SUBSCRIPTION_KEY_DISBURSEMENT')], // collection, disbursement
            "x_target_environment" => "mtncongo",
            "x_callback_url" => "https://techno-dev.com/momo/status"
        ],
        "api_keys" => [
            "user_id" => [env('MTN_USER_ID_COLLECTION'), env('MTN_USER_ID_DISBURSEMENT')], // collection, disbursement
            "api_key" => [env('MTN_API_KEY_COLLECTION'), env('MTN_API_KEY_DISBURSEMENT')] // collection, disbursement
        ]
    ]
];
