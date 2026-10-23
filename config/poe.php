<?php

return [
    'ninja' => [
        'base_url' => env('NINJA_BASE_URL'),
    ],

    'compact_price' => [
        'days_fresh' => env('COMPACT_PRICE_FRESH_DAYS', 7),
    ]
];
