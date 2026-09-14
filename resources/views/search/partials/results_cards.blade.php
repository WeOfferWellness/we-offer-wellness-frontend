@php
    $productsCount = 0;
    if (is_object($products ?? null) && method_exists($products, 'count')) {
        $productsCount = (int) $products->count();
    } elseif (is_countable($products ?? null)) {
        $productsCount = count($products);
    }
    $asyncBoot = (bool) ($searchAsyncBoot ?? false);
    $ghostCount = max(1, (int) ($ghostCount ?? 4));
@endphp

@if($asyncBoot && $productsCount === 0)
    @for($i = 0; $i < $ghostCount; $i++)
        <div class="col-12 col-md-6">
            <div class="wow-card-sm-wrap">
                <div class="result-view-map">
                    @include('partials.product_card_v4_1_ghost')
                </div>
                <div class="result-view-list">
                    @include('partials.product_card_v4_1_ghost')
                </div>
            </div>
        </div>
    @endfor
@else
    @forelse($products as $product)
        <div class="col-12 col-md-6" data-pid="{{ $product->id }}">
            <div class="wow-card-sm-wrap">
                <div class="result-view-map">
                    @include('partials.product_card_v4_1', ['product' => $product, 'preferredLocation' => null])
                </div>
                <div class="result-view-list">
                    @include('partials.product_card_v4_1', ['product' => $product, 'preferredLocation' => null])
                </div>
            </div>
        </div>
    @empty
        @if($showEmpty ?? true)
            <div class="col-12">
                <div class="card p-6 text-ink-600">No results matched your filters. Try widening your search.</div>
            </div>
        @endif
    @endforelse
@endif
