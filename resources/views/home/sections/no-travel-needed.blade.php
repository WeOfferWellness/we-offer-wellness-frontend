<section id="comfort-section" class="section" aria-labelledby="comfort-title">
    <div class="container-page">
        <div class="product-showcase-heading mb-6">
            <div class="product-showcase-heading__copy">
                <div class="kicker">Online support when you need it</div>
                <h2 id="comfort-title">Support From the comfort of your own home</h2>
                <p>Find online therapies, classes and one-to-one sessions that fit around your day — whether you want quiet time alone or support you can share.</p>
            </div>
            <div class="product-showcase-heading__actions">
                <a href="/search?format=online" class="btn-wow btn-wow--outline btn-sm btn-arrow" data-loader-init="1">
                    <span class="btn-label">View all online offerings</span>
                    <span class="btn-icon-wrap" aria-hidden="true">
                        <svg class="btn-icon-hover" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H5m14 0-4 4m4-4-4-4"></path></svg>
                        <svg class="btn-icon-default" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="none" stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12l-4 4m4-4-4-4"></path></svg>
                    </span>
                </a>
            </div>
        </div>

        <div>
            @foreach([
                ['label' => 'Under £50', 'min' => 0, 'max' => 50, 'items' => ($onlineUnder50 ?? collect())],
                ['label' => 'Under £100', 'min' => 50, 'max' => 100, 'items' => ($onlineUnder100 ?? collect())],
            ] as $row)
                <section class="{{ $loop->last ? 'mt-5' : '' }}" aria-label="{{ $row['label'] }}">
                    <div class="mb-3">
                        <h3 class="m-0 text-xl font-semibold text-ink-900">{{ $row['label'] }}</h3>
                    </div>

                    <div
                        class="comfort-price-rail grid grid-cols-2 gap-4 lg:grid-cols-4"
                        data-comfort-rail
                        data-price-min="{{ $row['min'] }}"
                        data-price-max="{{ $row['max'] }}"
                        data-page="1"
                        data-loading="false"
                    >
                        @forelse($row['items'] as $product)
                            @include('partials.product_card_v4_1', ['product' => $product, 'preferredLocation' => null, 'forceNewCard' => true, 'cardVersion' => 'v4.10'])
                        @empty
                            <p class="text-muted col-span-2 lg:col-span-4">No online offerings are available in this price range right now.</p>
                        @endforelse
                    </div>
                    <template data-comfort-ghost>
                        @include('partials.product_card_v4_1_ghost')
                        @include('partials.product_card_v4_1_ghost')
                    </template>
                </section>
            @endforeach
        </div>

    </div>
</section>

<style>
    /* Price rails own the desktop card width; the shared card minimum must
       not turn a four-column homepage grid into 223px cards. */
    .comfort-price-rail > .wow49-blade-card,
    .comfort-price-rail > .wow49-store-blade {
        width: 100%;
        min-width: 0;
        max-width: none;
    }

    @media (max-width: 767.98px) {
        .comfort-price-rail {
            display: flex !important;
            grid-template-columns: none !important;
            flex-wrap: nowrap;
            overflow-x: auto;
            overflow-y: visible;
            scroll-snap-type: x mandatory;
            scrollbar-width: none;
            -webkit-overflow-scrolling: touch;
        }

        .comfort-price-rail::-webkit-scrollbar {
            display: none;
        }

        .comfort-price-rail > article {
            flex: 0 0 calc((100% - 16px) / 2);
            min-width: calc((100% - 16px) / 2);
            scroll-snap-align: start;
        }

        .comfort-price-rail > .product-v4-1-ghost-card-scope {
            flex: 0 0 calc((100% - 16px) / 2);
            min-width: calc((100% - 16px) / 2);
        }
    }
</style>
