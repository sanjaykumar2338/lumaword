<?php

return [
    'products' => [
        'luma_monthly' => [
            'entitlement' => 'premium',
            'app_store_id' => env('APPLE_MONTHLY_PRODUCT_ID'),
            'play_store_id' => env('GOOGLE_MONTHLY_PRODUCT_ID'),
        ],
        'luma_annual' => [
            'entitlement' => 'premium',
            'app_store_id' => env('APPLE_ANNUAL_PRODUCT_ID'),
            'play_store_id' => env('GOOGLE_ANNUAL_PRODUCT_ID'),
        ],
    ],
    'app_store' => [
        'enabled' => (bool) env('APPLE_PURCHASES_ENABLED', false) && filled(env('APPLE_BUNDLE_ID')) && filled(env('APPLE_PRIVATE_KEY')) && filled(env('APPLE_KEY_ID')) && filled(env('APPLE_ISSUER_ID')) && filled(env('APPLE_ROOT_CERTIFICATE')) && filled(env('APPLE_MONTHLY_PRODUCT_ID')) && filled(env('APPLE_ANNUAL_PRODUCT_ID')),
        'bundle_id' => env('APPLE_BUNDLE_ID'), 'private_key' => env('APPLE_PRIVATE_KEY'),
        'key_id' => env('APPLE_KEY_ID'), 'issuer_id' => env('APPLE_ISSUER_ID'), 'shared_secret' => env('APPLE_SHARED_SECRET'),
        'root_certificate' => env('APPLE_ROOT_CERTIFICATE'),
    ],
    'play_store' => [
        'enabled' => (bool) env('GOOGLE_PURCHASES_ENABLED', false) && filled(env('GOOGLE_PACKAGE_NAME')) && filled(env('GOOGLE_ACCESS_TOKEN')) && filled(env('GOOGLE_MONTHLY_PRODUCT_ID')) && filled(env('GOOGLE_ANNUAL_PRODUCT_ID')),
        'package_name' => env('GOOGLE_PACKAGE_NAME'), 'service_account_json' => env('GOOGLE_SERVICE_ACCOUNT_JSON'), 'access_token' => env('GOOGLE_ACCESS_TOKEN'),
    ],
];
