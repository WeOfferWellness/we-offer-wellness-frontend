@if(!empty($recommendations))
    <section class="wow-search-recommendations" aria-labelledby="wow-search-recommendations-title">
        <div class="wow-search-recommendations__heading">
            <div>
                <p class="wow-search-recommendations__kicker">Curated from your search</p>
                <h2 id="wow-search-recommendations-title">Recommended for you</h2>
            </div>
            <span>{{ number_format((int) ($resultCount ?? 0)) }} matching offerings</span>
        </div>
        <div class="wow-search-recommendations__grid">
            @foreach($recommendations as $recommendation)
                <article class="wow-search-recommendation {{ !empty($recommendation['priority']) ? 'is-primary' : '' }}" data-pid="{{ $recommendation['item']->id }}">
                    <span class="wow-search-recommendation__label">{{ $recommendation['label'] }}</span>
                    @include('partials.product_card_v4_1', ['product' => $recommendation['item'], 'preferredLocation' => null])
                </article>
            @endforeach
        </div>
    </section>
@endif
