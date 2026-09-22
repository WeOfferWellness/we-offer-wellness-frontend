@include('partials.product_showcase_section', [
    'section' => [
        'id' => 'home-gifts',
        'section_class' => 'section',
        'kicker' => 'Give the gift of wellbeing',
        'title' => 'Thoughtful gifts under £50',
        'description' => 'Choose an instant digital gift card, a wellbeing product or a bookable experience they can enjoy in their own time.',
        'cta' => [
            'label' => 'Browse gifts',
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
