<?php

return [
    // One public GA4 property for the marketplace. Keep this empty in local/test
    // environments unless analytics is explicitly being exercised.
    'measurement_id' => env('GA_ID', env('VITE_GA_ID', env('GA4_MEASUREMENT_ID', 'G-MZMQNETBYH'))),
    // Keep analytics enabled on deployed environments unless explicitly
    // disabled. Local and test environments remain opt-in by default.
    'enabled' => filter_var(
        env(
            'ANALYTICS_ENABLED',
            ! in_array(strtolower((string) env('APP_ENV', 'production')), ['local', 'testing'], true)
        ),
        FILTER_VALIDATE_BOOLEAN
    ),
    'consent_default' => [
        'analytics_storage' => 'denied',
        'ad_storage' => 'denied',
        'ad_user_data' => 'denied',
        'ad_personalization' => 'denied',
        'personalization_storage' => 'denied',
        'wait_for_update' => 500,
    ],
    'health' => [
        'property_id' => env('GOOGLE_ANALYTICS_PROPERTY_ID', '422680391'),
        'timezone' => env('GOOGLE_ANALYTICS_TIMEZONE', 'Europe/London'),
        'client_id' => env('GOOGLE_ANALYTICS_CLIENT_ID', env('GOOGLE_SEARCH_CONSOLE_CLIENT_ID')),
        'client_secret' => env('GOOGLE_ANALYTICS_CLIENT_SECRET', env('GOOGLE_SEARCH_CONSOLE_CLIENT_SECRET')),
        'client_credentials_file' => env('GOOGLE_ANALYTICS_CLIENT_CREDENTIALS_FILE', env('GOOGLE_SEARCH_CONSOLE_CLIENT_CREDENTIALS_FILE')),
        'refresh_token' => env('GOOGLE_ANALYTICS_REFRESH_TOKEN', env('GOOGLE_SEARCH_CONSOLE_REFRESH_TOKEN')),
        'alert_email' => env('ANALYTICS_HEALTH_ALERT_EMAIL'),
        'core_urls' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('ANALYTICS_HEALTH_CORE_URLS', 'https://www.weofferwellness.co.uk/,https://www.weofferwellness.co.uk/therapies/reiki,https://www.weofferwellness.co.uk/locations/united-kingdom/london'))
        ))),
    ],
];
