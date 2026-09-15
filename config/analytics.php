<?php

return [
    // One public GA4 property for the marketplace. Keep this empty in local/test
    // environments unless analytics is explicitly being exercised.
    'measurement_id' => env('GA_ID', env('VITE_GA_ID', env('GA4_MEASUREMENT_ID'))),
    'enabled' => filter_var(env('ANALYTICS_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    'consent_default' => [
        'analytics_storage' => 'denied',
        'ad_storage' => 'denied',
        'ad_user_data' => 'denied',
        'ad_personalization' => 'denied',
        'wait_for_update' => 500,
    ],
];
