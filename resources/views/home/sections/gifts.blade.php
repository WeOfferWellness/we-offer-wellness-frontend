@include('partials.product_showcase_section', [
    'section' => [
        'id' => 'home-gifts',
        'container_class' => 'container',
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
        'card_version' => 'v4.10',
        'page_size' => 12,
        'load_more' => true,
        'products' => $giftsUnder50 ?? collect(),
        'prev_id' => 'gifts-prev',
        'next_id' => 'gifts-next',
        'rail_class' => 'flex gap-6 overflow-x-auto overflow-y-visible no-scrollbar snap-x snap-mandatory pt-2 pb-2 -mx-4 px-4 sm:mx-0 sm:px-0 bg-transparent',
        'empty_html' => 'No gifts found under £50 right now. <a class="link-wow" href="/gifts">Browse all gifts</a>.',
    ],
])

<style>
    #home-gifts .product-showcase-heading {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 32px;
        margin-bottom: 26px !important;
    }
    #home-gifts .product-showcase-heading__copy { min-width: 0; }
    #home-gifts .product-showcase-heading__copy .kicker {
        margin: 0 0 8px;
        color: #4f9482;
        font-family: "Instrument Sans", sans-serif;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .18em;
        line-height: 1.2;
        text-transform: uppercase;
    }
    #home-gifts .product-showcase-heading__copy h2 {
        margin: 0;
        color: #0b3028;
        font-family: "Playfair Display", Georgia, "Times New Roman", serif;
        font-size: clamp(38px, 4vw, 54px);
        font-weight: 500;
        line-height: 1;
        letter-spacing: -.04em;
    }
    #home-gifts .product-showcase-heading__copy p {
        max-width: 650px;
        margin: 12px 0 0;
        color: #68736f;
        font-family: "Instrument Sans", sans-serif;
        font-size: 15px;
        line-height: 1.55;
    }
    #home-gifts .product-showcase-heading__actions { width: auto; }
    @media (max-width: 575.98px) {
        #home-gifts .product-showcase-heading { display: block; margin-bottom: 21px !important; }
        #home-gifts .product-showcase-heading__copy h2 { font-size: 38px; }
        #home-gifts .product-showcase-heading__copy p { font-size: 14px; }
        #home-gifts .product-showcase-heading__actions { margin-top: 19px; }
    }
</style>
