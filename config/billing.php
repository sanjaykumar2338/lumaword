<?php

return [
    'enabled' => (bool) env('BILLING_ENABLED', false),
    'subscription_name' => 'premium',
    'prices' => [
        'monthly' => ['stripe_id' => env('STRIPE_PREMIUM_MONTHLY_PRICE'), 'amount' => 999, 'label' => '$9.99/month'],
        'annual' => ['stripe_id' => env('STRIPE_PREMIUM_ANNUAL_PRICE'), 'amount' => 7999, 'label' => '$79.99/year'],
    ],
];
