<?php

$section = static fn (string $id, string $component, string $label): array => [
    'id' => $id,
    'component' => $component,
    'label' => $label,
];

return [
    'profiles' => [
        'page_content' => [
            $section('page-content', 'page_content', 'Page content'),
        ],

        'about' => [
            $section('breadcrumbs', 'breadcrumbs', 'Breadcrumbs'),
            $section('about-hero', 'about_hero', 'About hero'),
            $section('about-story', 'about_story', 'Our story'),
            $section('about-founders', 'about_founders', 'Founders'),
            $section('about-vision-mission', 'about_vision_mission', 'Vision & mission'),
        ],

        'contact' => [
            $section('breadcrumbs', 'breadcrumbs', 'Breadcrumbs'),
            $section('contact-hero', 'contact_hero', 'Contact hero'),
            $section('contact-topics', 'contact_topics', 'Contact topics'),
            $section('contact-details', 'contact_details', 'Contact details'),
        ],

        'help_centre' => [
            $section('breadcrumbs', 'breadcrumbs', 'Breadcrumbs'),
            $section('help-hero', 'help_hero', 'Help Centre hero'),
            $section('help-content', 'help_content', 'Help Centre content'),
        ],

        'help_faq' => [
            $section('breadcrumbs', 'breadcrumbs', 'Breadcrumbs'),
            $section('faq', 'faq', 'Frequently asked questions'),
        ],

        'help_giftcards' => [
            $section('help-giftcards-content', 'help_giftcards_content', 'Gift card help'),
        ],

        'partners' => [
            $section('partners-hero', 'partners_hero', 'Partner hero'),
            $section('partners-model', 'partners_model', 'Partner model'),
            $section('partners-marketplace', 'partners_marketplace', 'Marketplace positioning'),
            $section('partners-profile', 'partners_profile', 'Partner profile'),
            $section('partners-studio', 'partners_studio', 'WOW Studio'),
            $section('partners-cta', 'partners_cta', 'Partner call to action'),
        ],

        'corporate' => [
            $section('breadcrumbs', 'breadcrumbs', 'Breadcrumbs'),
            $section('corporate-content', 'corporate_content', 'Corporate content'),
        ],

        'corporate_coming' => [
            $section('corporate-hero', 'corporate_hero', 'Corporate hero'),
            $section('corporate-content', 'corporate_content', 'Corporate content'),
        ],

        'safety' => [
            $section('safety-content', 'safety_content', 'Safety guidance'),
            $section('faq', 'faq', 'Frequently asked questions'),
            $section('safety-note', 'safety_note', 'Safety disclaimer'),
        ],

        'wellness_match' => [
            $section('breadcrumbs', 'breadcrumbs', 'Breadcrumbs'),
            $section('landing-hero', 'landing_hero', 'Landing hero'),
            $section('hero-meta', 'hero_meta', 'Hero details'),
            $section('wellness-match-results', 'wellness_match_results', 'Wellness match results'),
            $section('faq', 'faq', 'Frequently asked questions'),
            $section('related-pages', 'related_pages', 'Related pages'),
        ],

        'landing' => [
            $section('breadcrumbs', 'breadcrumbs', 'Breadcrumbs'),
            $section('landing-hero', 'landing_hero', 'Landing hero'),
            $section('hero-meta', 'hero_meta', 'Hero details'),
            $section('discover-category', 'discover_category', 'Discover by modality'),
            $section('offering-filters', 'offering_filters', 'Offering filters & results'),
            $section('popular-price', 'popular_price', 'Popular price points'),
            $section('faq', 'faq', 'Frequently asked questions'),
            $section('related-pages', 'related_pages', 'Related pages'),
            $section('guide-panel', 'guide_panel', 'Guide panel'),
        ],

        'therapy' => [
            $section('breadcrumbs', 'breadcrumbs', 'Breadcrumbs'),
            $section('landing-hero', 'landing_hero', 'Therapy hero'),
            $section('hero-meta', 'hero_meta', 'Hero details'),
            $section('offering-filters', 'offering_filters', 'Offering filters & results'),
            $section('guide-panel', 'guide_panel', 'Guide panel'),
        ],

        'locations_discovery' => [
            $section('breadcrumbs', 'breadcrumbs', 'Breadcrumbs'),
            $section('landing-hero', 'landing_hero', 'Locations hero'),
            $section('hero-meta', 'hero_meta', 'Hero details'),
            $section('search-bar', 'search_bar', 'Location search'),
            $section('location-nearby-offerings', 'location_nearby_offerings', 'Wellness nearby'),
            $section('location-popular-places', 'location_popular_places', 'Popular places'),
            $section('location-new-nearby', 'location_new_nearby', 'New nearby'),
            $section('modality-board', 'modality_board', 'Modalities'),
            $section('location-price-bands', 'location_price_bands', 'Explore by price'),
            $section('location-online-offerings', 'location_online_offerings', 'Online wellness'),
            $section('location-directory', 'location_directory', 'Location directory'),
        ],

        'near_me' => [
            $section('breadcrumbs', 'breadcrumbs', 'Breadcrumbs'),
            $section('landing-hero', 'landing_hero', 'Near me hero'),
            $section('hero-meta', 'hero_meta', 'Hero details'),
            $section('location-search', 'location_search', 'Location search'),
        ],

        'online_near_me' => [
            $section('breadcrumbs', 'breadcrumbs', 'Breadcrumbs'),
            $section('landing-hero', 'landing_hero', 'Online & near me hero'),
            $section('hero-meta', 'hero_meta', 'Hero details'),
            $section('route-options', 'route_options', 'Online or nearby choices'),
            $section('quick-browse', 'quick_browse', 'Quick browse'),
            $section('trust-panel', 'trust_panel', 'Trust panel'),
        ],

        'giftcards' => [
            $section('breadcrumbs', 'breadcrumbs', 'Breadcrumbs'),
            $section('landing-hero', 'landing_hero', 'Gift cards hero'),
            $section('hero-meta', 'hero_meta', 'Hero details'),
            $section('giftcards-catalogue', 'giftcards_catalogue', 'Gift cards catalogue'),
        ],

        'seo_money' => [
            $section('breadcrumbs', 'breadcrumbs', 'Breadcrumbs'),
            $section('landing-hero', 'landing_hero', 'Landing hero'),
            $section('hero-meta', 'hero_meta', 'Hero details'),
            $section('seo-location-search', 'seo_location_search', 'Location search'),
            $section('seo-results', 'seo_results', 'Offering results'),
            $section('location-explorer', 'location_explorer', 'Nearby locations'),
            $section('related-pages', 'related_pages', 'Related pages'),
            $section('faq', 'faq', 'Frequently asked questions'),
            $section('guide-panel', 'guide_panel', 'Guide panel'),
        ],

        'location_landing' => [
            $section('breadcrumbs', 'breadcrumbs', 'Breadcrumbs'),
            $section('landing-hero', 'landing_hero', 'Location hero'),
            $section('hero-meta', 'hero_meta', 'Hero details'),
            $section('offering-tabs', 'offering_tabs', 'Offering tabs'),
            $section('modality-board', 'modality_board', 'Modalities'),
            $section('location-explorer', 'location_explorer', 'Nearby places'),
            $section('popular-price', 'popular_price', 'Popular price points'),
        ],

        'locations_index' => [
            $section('breadcrumbs', 'breadcrumbs', 'Breadcrumbs'),
            $section('landing-hero', 'landing_hero', 'Locations hero'),
            $section('hero-meta', 'hero_meta', 'Hero details'),
            $section('search-bar', 'search_bar', 'Location search'),
            $section('location-search-summary', 'location_search_summary', 'Search summary'),
            $section('offering-filters', 'offering_filters', 'Offering filters & results'),
            $section('nearby-locations', 'nearby_locations', 'Nearby practitioner locations'),
            $section('featured-locations', 'featured_locations', 'Featured locations'),
            $section('location-directory', 'location_directory', 'Location directory'),
            $section('gift-cards-occasion', 'gift_cards_occasion', 'Gift cards by occasion'),
        ],

        'location_show' => [
            $section('breadcrumbs', 'breadcrumbs', 'Breadcrumbs'),
            $section('landing-hero', 'landing_hero', 'Location hero'),
            $section('hero-meta', 'hero_meta', 'Hero details'),
            $section('offering-filters', 'offering_filters', 'Offering filters & results'),
        ],

        'needs_index' => [
            $section('breadcrumbs', 'breadcrumbs', 'Breadcrumbs'),
            $section('landing-hero', 'landing_hero', 'Needs hero'),
            $section('hero-meta', 'hero_meta', 'Hero details'),
            $section('needs-grid', 'needs_grid', 'Needs grid'),
            $section('quick-browse', 'quick_browse', 'Quick browse'),
            $section('trust-panel', 'trust_panel', 'Trust panel'),
        ],

        'need_show' => [
            $section('breadcrumbs', 'breadcrumbs', 'Breadcrumbs'),
            $section('landing-hero', 'landing_hero', 'Need hero'),
            $section('hero-meta', 'hero_meta', 'Hero details'),
            $section('offering-filters', 'offering_filters', 'Offering filters & results'),
        ],

        'online' => [
            $section('breadcrumbs', 'breadcrumbs', 'Breadcrumbs'),
            $section('landing-hero', 'landing_hero', 'Online wellness hero'),
            $section('hero-meta', 'hero_meta', 'Hero details'),
            $section('guide-panel', 'guide_panel', 'Guide panel'),
            $section('offering-filters', 'offering_filters', 'Offering filters & results'),
        ],

        'events' => [
            $section('breadcrumbs', 'breadcrumbs', 'Breadcrumbs'),
            $section('landing-hero', 'landing_hero', 'Events hero'),
            $section('offering-filters', 'offering_filters', 'Event filters & results'),
        ],

        'collection' => [
            $section('breadcrumbs', 'breadcrumbs', 'Breadcrumbs'),
            $section('landing-hero', 'landing_hero', 'Collection hero'),
            $section('hero-meta', 'hero_meta', 'Hero details'),
            $section('collection-results', 'collection_results', 'Collection offerings'),
        ],

        'schedule_discovery' => [
            $section('breadcrumbs', 'breadcrumbs', 'Breadcrumbs'),
            $section('landing-hero', 'landing_hero', 'Schedule discovery hero'),
            $section('hero-meta', 'hero_meta', 'Hero details'),
            $section('schedule-discovery', 'schedule_discovery', 'Schedule discovery'),
        ],

        'schedule_landing' => [
            $section('breadcrumbs', 'breadcrumbs', 'Breadcrumbs'),
            $section('landing-hero', 'landing_hero', 'Schedule hero'),
            $section('hero-meta', 'hero_meta', 'Hero details'),
            $section('schedule-links', 'schedule_links', 'Quick links'),
            $section('schedule-days', 'schedule_days', 'Day-by-day schedule'),
            $section('schedule-featured', 'schedule_featured', 'Featured events'),
            $section('schedule-online', 'schedule_online', 'Online events'),
            $section('schedule-upcoming', 'schedule_upcoming', 'Upcoming events'),
            $section('schedule-discovery-cards', 'schedule_discovery_cards', 'Discover more'),
            $section('schedule-content', 'schedule_content', 'Schedule guidance'),
            $section('faq', 'faq', 'Frequently asked questions'),
            $section('related-pages', 'related_pages', 'Related schedule pages'),
        ],

        'search' => [
            $section('search-bar', 'search_bar', 'Search bar'),
            $section('breadcrumbs', 'breadcrumbs', 'Breadcrumbs'),
            $section('offering-filters', 'offering_filters', 'Offering filters & results'),
        ],

        'provider_directory' => [
            $section('breadcrumbs', 'breadcrumbs', 'Breadcrumbs'),
            $section('landing-hero', 'landing_hero', 'Practitioner directory hero'),
            $section('hero-meta', 'hero_meta', 'Hero details'),
            $section('provider-directory', 'provider_directory', 'Practitioner directory'),
        ],

        'provider_profile' => [
            $section('breadcrumbs', 'breadcrumbs', 'Breadcrumbs'),
            $section('provider-hero', 'provider_hero', 'Practitioner hero'),
            $section('provider-navigation', 'provider_navigation', 'Profile navigation'),
            $section('provider-overview', 'provider_overview', 'Overview'),
            $section('provider-about', 'provider_about', 'About'),
            $section('provider-credentials', 'provider_credentials', 'Credentials'),
            $section('provider-offerings', 'provider_offerings', 'Offerings'),
            $section('provider-locations', 'provider_locations', 'Locations'),
            $section('provider-reviews', 'provider_reviews', 'Reviews'),
        ],

        'offering' => [
            $section('breadcrumbs', 'breadcrumbs', 'Breadcrumbs'),
            $section('offering-body', 'offering_body', 'Offering details & booking'),
        ],

        'guide_hub' => [
            $section('breadcrumbs', 'breadcrumbs', 'Breadcrumbs'),
            $section('landing-hero', 'landing_hero', 'Guide hub hero'),
            $section('hero-meta', 'hero_meta', 'Hero details'),
            $section('guide-hub-content', 'guide_hub_content', 'Guide collections'),
        ],

        'guide_article' => [
            $section('breadcrumbs', 'breadcrumbs', 'Breadcrumbs'),
            $section('landing-hero', 'landing_hero', 'Guide hero'),
            $section('guide-article-content', 'guide_article_content', 'Guide article'),
            $section('faq', 'faq', 'Frequently asked questions'),
            $section('guide-article-rail', 'guide_article_rail', 'Guide discovery rail'),
        ],

    ],

    'actions' => [
        'AboutController@index' => 'about',
        'AboutController@team' => 'provider_profile',

        'CategoryController@therapy' => 'therapy',
        'CategoryController@hub' => 'landing',
        'CategoryController@show' => 'landing',

        'CollectionController@show' => 'collection',
        'ContactController@index' => 'contact',
        'CorporateController@hub' => 'corporate',
        'CorporateController@comingSoon' => 'corporate_coming',
        'GiftCardsController@index' => 'giftcards',

        'GuideController@index' => 'guide_hub',
        'GuideController@format' => 'guide_hub',
        'GuideController@modality' => 'guide_hub',
        'GuideController@show' => 'guide_article',

        'HelpController@index' => 'help_centre',
        'HelpController@faq' => 'help_faq',
        'HelpController@giftCards' => 'help_giftcards',

        'LandingController@need' => 'landing',
        'LandingController@plan' => 'page_content',

        'LocationController@hierarchy' => 'location_landing',
        'LocationController@index' => 'locations_discovery',
        'LocationController@show' => 'location_show',
        'LocationController@nearMe' => 'near_me',
        'LocationController@cityCategory' => 'locations_index',
        'LocationController@online' => 'online',
        'LocationController@onlineModality' => 'online',
        'LocationController@onlineNearMe' => 'online_near_me',
        'LocationController@categoryNearMe' => 'seo_money',
        'LocationController@modalityLocation' => 'seo_money',

        'LocationsController@cityCategory' => 'locations_index',

        'NeedsController@index' => 'needs_index',
        'NeedsController@show' => 'need_show',

        'OfferingController@indexLegacy' => 'page_content',
        'OfferingController@showLegacy' => 'offering',
        'OfferingController@showLegacyByCategory' => 'offering',
        'OfferingController@showEvent' => 'offering',
        'OfferingController@show' => 'offering',
        'OfferingController@showAtLocation' => 'offering',
        'OfferingController@showOnline' => 'offering',

        'PageController@show' => 'page_content',

        'ProvidersController@index' => 'provider_directory',
        'ProvidersController@show' => 'provider_profile',

        'RedirectsController@experienceIndex' => 'page_content',
        'RedirectsController@experiencesIndex' => 'page_content',
        'RedirectsController@shopifyProductSandbox' => 'page_content',
        'RedirectsController@shopifyPage' => 'page_content',
        'RedirectsController@experienceSlug' => 'page_content',
        'RedirectsController@experiencesSlug' => 'page_content',

        'ReviewsController@index' => 'page_content',
        'SafetyContraindicationsController@index' => 'safety',

        'ScheduleDiscoveryController@index' => 'schedule_discovery',
        'ScheduleDiscoveryController@show' => 'schedule_landing',

        'SearchController@index' => 'search',
        'SeoLandingController@show' => 'landing',

        'StaticPagesController@show' => 'page_content',
        'StaticPagesController@giftCards' => 'page_content',
        'StaticPagesController@partners' => 'partners',

        'TypeController@classes' => 'landing',
        'TypeController@events' => 'events',
        'TypeController@retreats' => 'landing',
        'TypeController@holisticTherapiesUk' => 'landing',
        'TypeController@therapies' => 'landing',
        'TypeController@workshops' => 'landing',

        'ViewController@__invoke' => 'page_content',
    ],

    'route_profiles' => [
        '/wellness-match-finder/results' => 'wellness_match',
        '/404' => 'page_content',
    ],

    'default_profile' => 'page_content',
];
