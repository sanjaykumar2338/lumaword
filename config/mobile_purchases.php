<?php

return [
    'products' => ['luma_monthly' => 'premium', 'luma_annual' => 'premium'],
    'app_store' => [
        'enabled' => (bool) env('APPLE_PURCHASES_ENABLED', false) && filled(env('APPLE_BUNDLE_ID')) && filled(env('APPLE_SHARED_SECRET')),
        'bundle_id' => env('APPLE_BUNDLE_ID'), 'private_key' => env('APPLE_PRIVATE_KEY'),
        'key_id' => env('APPLE_KEY_ID'), 'issuer_id' => env('APPLE_ISSUER_ID'), 'shared_secret' => env('APPLE_SHARED_SECRET'),
    ],
    'play_store' => [
        'enabled' => (bool) env('GOOGLE_PURCHASES_ENABLED', false) && filled(env('GOOGLE_PACKAGE_NAME')) && filled(env('GOOGLE_ACCESS_TOKEN')),
        'package_name' => env('GOOGLE_PACKAGE_NAME'), 'service_account_json' => env('GOOGLE_SERVICE_ACCOUNT_JSON'), 'access_token' => env('GOOGLE_ACCESS_TOKEN'),
    ],
];
