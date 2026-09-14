@include('partials.product_showcase_section', [
    'section' => [
        'id' => 'home-gifts',
        'section_class' => 'section',
        'kicker' => 'Thoughtful ways to nourish someone you love',
        'title' => 'Gifts that glow',
        'title_suffix' => '(under £50)',
        'description' => 'Cleaner cards with availability-first booking cues. Practitioners with live availability stand out, while request-only bookings stay usable without stealing the show.',
        'cta' => [
            'label' => 'Find a thoughtful gift',
            'href' => '/search?tag=Gift&price_max=50',
        ],
        'loading' => false,
        'ghost_view' => 'partials.product_card_v4_1_ghost',
        'force_new_card' => true,
        'page_size' => 12,
        'load_more' => true,
        'products' => $giftsUnder50 ?? collect(),
        'prev_id' => 'gifts-prev',
        'next_id' => 'gifts-next',
        'rail_class' => 'flex gap-6 overflow-x-auto overflow-y-visible no-scrollbar snap-x snap-mandatory pt-2 pb-2 -mx-4 px-4 sm:mx-0 sm:px-0 bg-transparent',
        'empty_html' => 'No gifts found under £50 right now. <a class="link-wow" href="/gifts">Browse all gifts</a>.',
    ],
])
