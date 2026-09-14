<section data-v-f43bb09d="" id="comfort-section" class="section" aria-labelledby="comfort-title">
    <div data-v-f43bb09d="">
        <div class="product-showcase-heading mb-6">
            <div class="product-showcase-heading__copy">
                <div data-v-f43bb09d="" class="kicker">No travel needed</div>
                <h2 data-v-f43bb09d="" id="comfort-title">From the comfort of your own home</h2>
                <p data-v-f43bb09d="">When your mind won’t slow down, soften into rituals that meet you where you are — gentle sessions that bring calm, clarity and care right to your space.</p>
            </div>

            <div class="product-showcase-heading__actions">
                <div class="product-showcase-heading__controls">
                    <button data-v-f43bb09d="" id="comfort-prev" class="hidden sm:inline-flex carousel-arrow" type="button" aria-label="Previous">
                        <svg data-v-f43bb09d="" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path data-v-f43bb09d="" d="M15 18l-6-6 6-6"></path>
                        </svg>
                    </button>
                    <button data-v-f43bb09d="" id="comfort-next" class="hidden sm:inline-flex carousel-arrow" type="button" aria-label="Next">
                        <svg data-v-f43bb09d="" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path data-v-f43bb09d="" d="M9 6l6 6-6 6"></path>
                        </svg>
                    </button>
                </div>
                <a data-v-f43bb09d="" id="comfort-cta" href="/search?price_max=50&amp;format=online" class="btn-wow btn-wow--outline btn-sm btn-arrow product-showcase-heading__cta" data-loader-init="1">
                    <span data-v-f43bb09d="" class="btn-label">See all under £50 (solo)</span>
                    <span data-v-f43bb09d="" class="btn-icon-wrap" aria-hidden="true">
                        <svg data-v-f43bb09d="" class="btn-icon-hover" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path data-v-f43bb09d="" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H5m14 0-4 4m4-4-4-4"></path>
                        </svg>
                        <svg data-v-f43bb09d="" class="btn-icon-default" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path data-v-f43bb09d="" fill="none" stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12l-4 4m4-4-4-4"></path>
                        </svg>
                    </span>
                    <span data-v-f43bb09d="" class="btn-spinner" aria-hidden="true"><span class="spin"></span></span>
                </a>
            </div>
        </div>

        <div data-v-f43bb09d="" class="flex items-center justify-between gap-4 mb-4 flex-wrap">
            <div data-v-f43bb09d="" class="flex items-center gap-4 flex-wrap">
                <div data-v-f43bb09d="" class="flex items-center gap-2"><span data-v-f43bb09d=""
                                                                              class="font-semibold text-ink-800">Under</span>
                    <div data-v-f43bb09d="" class="seg-group" role="tablist" aria-label="Under price">
                        <button data-v-f43bb09d="" class="seg active" role="tab" aria-selected="true" data-price="50">£50</button>
                        <button data-v-f43bb09d="" class="seg" role="tab" aria-selected="false" data-price="100">£100</button>
                        <button data-v-f43bb09d="" class="seg" role="tab" aria-selected="false" data-price="500">£500</button>
                    </div>
                </div>
                <div data-v-f43bb09d="" class="flex items-center gap-2"><span data-v-f43bb09d=""
                                                                              class="font-semibold text-ink-800">For</span>
                    <div data-v-f43bb09d="" class="seg-group" role="tablist" aria-label="For">
                        <button data-v-f43bb09d="" class="seg active" role="tab" aria-selected="true" data-group="solo">Solo</button>
                        <button data-v-f43bb09d="" class="seg" role="tab" aria-selected="false" data-group="couple">Couple</button>
                    </div>
                </div>
            </div>
        </div>
        <div data-v-f43bb09d="">
            <div data-v-f43bb09d="" id="comfort-cards"
                 class="flex gap-6 overflow-x-auto overflow-y-visible no-scrollbar snap-x snap-mandatory pt-2 pb-2 -mx-4 px-4 sm:mx-0 sm:px-0 bg-transparent">
                @forelse(($onlineUnder50 ?? collect()) as $product)
                    @include('partials.product_card_v4_1', ['product' => $product, 'preferredLocation' => null, 'forceNewCard' => true])
                @empty
                    <div class="text-muted">No online offerings are available right now.</div>
                @endforelse
            </div>
        </div>
    </div>
</section>
