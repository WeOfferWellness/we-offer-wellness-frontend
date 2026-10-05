<?php

return [
    'pages' => [
        'home' => [
            'default_layout' => [
                ['component' => 'hero-slider', 'enabled' => true, 'config' => []],
                ['component' => 'popular_searches', 'enabled' => true, 'config' => []],
                ['component' => 'schedule', 'enabled' => true, 'config' => []],
                ['component' => 'latest_catalogue', 'enabled' => true, 'config' => []],
                ['component' => 'discover_category', 'enabled' => true, 'config' => []],
                ['component' => 'gift_cards_occasion', 'enabled' => true, 'config' => []],
                ['component' => 'no-travel-needed', 'enabled' => true, 'config' => []],
                ['component' => 'gifts', 'enabled' => true, 'config' => []],
                ['component' => 'mindful_times_ribbon', 'enabled' => true, 'config' => []],
                ['component' => 'trust-feel-safe', 'enabled' => true, 'config' => []],
                ['component' => 'mindfultimes_guides_interviews', 'enabled' => true, 'config' => []],
                ['component' => 'practitioner_chats_converstions', 'enabled' => true, 'config' => []],
                ['component' => 'our_approach', 'enabled' => true, 'config' => []],
            ],
        ],
    ],

    'components' => [
        'legacy_content' => ['view' => null],
        'hero-slider' => ['view' => 'home.sections.hero-slider'],
        'popular_searches' => ['view' => 'home.sections.popular_searches'],
        'schedule' => ['view' => 'home.sections.schedule', 'condition' => 'hasClassesThisWeek'],
        'latest_catalogue' => ['view' => 'home.sections.latest_catalogue'],
        'collection_rail' => ['view' => 'home.sections.collection_rail'],
        'offering_tabs' => ['view' => 'home.sections.offering_tabs'],
        'offering_filters' => ['view' => 'home.sections.offering_filters'],
        'discover_category' => ['view' => 'home.sections.discover_category'],
        'gift_cards_occasion' => ['view' => 'home.sections.gift_cards_occasion'],
        'no-travel-needed' => ['view' => 'home.sections.no-travel-needed'],
        'gifts' => ['view' => 'home.sections.gifts'],
        'mindful_times_ribbon' => ['view' => 'home.sections.mindful_times_ribbon'],
        'trust-feel-safe' => ['view' => 'home.sections.trust-feel-safe'],
        'mindfultimes_guides_interviews' => ['view' => 'home.sections.mindfultimes_guides_interviews'],
        'practitioner_chats_converstions' => ['view' => 'home.sections.practitioner_chats_converstions'],
        'our_approach' => ['view' => 'home.sections.our_approach', 'wrapper' => 'container'],
    ],
];
