@include('partials.product_showcase_section', [
        'section' => [
        'id' => 'home-latest-catalogue',
        'section_class' => 'section',
        'kicker' => 'Freshly added',
        'title' => 'Just landed on WOW',
        'description' => 'Freshly published listings lead the rail, followed by the newest live favourites in one seamless flow.',
        'cta' => [
            'label' => 'Browse therapies',
            'href' => '/therapies',
        ],
        'ghost_view' => 'partials.product_card_v4_1_ghost',
        'force_new_card' => true,
        // Server-render a usable rail first. The client may refresh it, but a
        // privacy extension must never leave the homepage on skeleton cards.
        'loading' => false,
        'products' => $latestCatalogue ?? collect(),
        'prev_id' => 'latest-prev',
        'next_id' => 'latest-next',
        'rail_class' => 'flex gap-6 overflow-x-auto overflow-y-visible no-scrollbar snap-x snap-mandatory pt-2 pb-2 -mx-4 px-4 sm:mx-0 sm:px-0 bg-transparent',
        'empty_html' => 'No latest catalogue items are available right now.',
    ],
])
