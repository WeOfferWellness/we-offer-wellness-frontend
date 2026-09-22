@php
    $section = is_array($section ?? null) ? $section : (array) ($section ?? []);
    $sectionId = $section['id'] ?? 'product-showcase';
    $ariaLabel = $section['aria_label'] ?? ($section['title'] ?? 'Product showcase');
    $kicker = $section['kicker'] ?? '';
    $title = $section['title'] ?? '';
    $titleSuffix = $section['title_suffix'] ?? '';
    $description = $section['description'] ?? '';
    $cta = $section['cta'] ?? [];
    $products = $section['products'] ?? collect();
    $loading = (bool) ($section['loading'] ?? false);
    $loadingCount = max(1, (int) ($section['loading_count'] ?? 4));
    $showArrows = (bool) ($section['show_arrows'] ?? true);
    $ctaLabel = $cta['label'] ?? 'View all';
    $ctaHref = $cta['href'] ?? '#';
    $prevId = $section['prev_id'] ?? ($sectionId . '-prev');
    $nextId = $section['next_id'] ?? ($sectionId . '-next');
    $titleTag = $section['title_tag'] ?? 'h2';
    $titleStyle = $section['title_style'] ?? '';
    $containerClass = $section['container_class'] ?? 'container-page';
    $railClass = $section['rail_class'] ?? 'flex gap-6 overflow-x-auto overflow-y-visible no-scrollbar snap-x snap-mandatory pt-2 pb-2 -mx-4 px-4 sm:mx-0 sm:px-0 bg-transparent';
    $pageSize = max(1, (int) ($section['page_size'] ?? 12));
    $loadMore = ! empty($section['load_more']);
    $cardView = $section['card_view'] ?? 'partials.product_card_v4_1';
    $ghostView = $section['ghost_view'] ?? 'partials.product_card_v4_ghost';
    $forceNewCard = (bool) ($section['force_new_card'] ?? false);
@endphp

<section id="{{ $sectionId }}" class="{{ $section['section_class'] ?? 'section' }}">
    <div class="{{ $containerClass }}">
        <div class="product-showcase-heading mb-6">
            <div class="product-showcase-heading__copy">
                @if($kicker !== '')
                    <div class="kicker">{{ $kicker }}</div>
                @endif

                @if($title !== '')
                    @if($titleTag === 'h1')
                        <h1 @if($titleStyle) style="{{ $titleStyle }}" @endif>{{ $title }}@if($titleSuffix !== '') <span>{{ $titleSuffix }}</span>@endif</h1>
                    @else
                        <h2 @if($titleStyle) style="{{ $titleStyle }}" @endif>{{ $title }}@if($titleSuffix !== '') <span>{{ $titleSuffix }}</span>@endif</h2>
                    @endif
                @endif

                @if($description !== '')
                    <p>{{ $description }}</p>
                @endif
            </div>

            <div class="product-showcase-heading__actions">
                @if($showArrows)
                    <div class="product-showcase-heading__controls">
                        <button class="hidden sm:inline-flex carousel-arrow" id="{{ $prevId }}" type="button" aria-label="Previous">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M15 18l-6-6 6-6"></path>
                            </svg>
                        </button>
                        <button class="hidden sm:inline-flex carousel-arrow" id="{{ $nextId }}" type="button" aria-label="Next">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 6l6 6-6 6"></path>
                            </svg>
                        </button>
                    </div>
                @endif

                @if($ctaHref !== '#' && $ctaLabel !== '')
                    @include('partials.wow-button', ['href' => $ctaHref, 'label' => $ctaLabel, 'variant' => 'outline', 'size' => 'sm', 'arrow' => true, 'class' => 'product-showcase-heading__cta'])
                @endif
            </div>
        </div>

        <div
            id="{{ $sectionId }}-cards"
            class="{{ $railClass }}"
            data-rail-page-size="{{ $pageSize }}"
            data-rail-load-more="{{ $loadMore ? '1' : '0' }}"
            @if($loading) aria-busy="true" @endif
        >
            @if($loading)
                @for($i = 0; $i < $loadingCount; $i++)
                    @include($ghostView)
                @endfor
            @else
                @forelse($products as $product)
                    @include($cardView, ['product' => $product, 'preferredLocation' => null, 'forceNewCard' => $forceNewCard])
                @empty
                    <div class="text-muted">{!! $section['empty_html'] ?? e($section['empty_text'] ?? 'Nothing to show right now.') !!}</div>
                @endforelse
            @endif
        </div>
    </div>
</section>

@once
<style>
    .product-showcase-heading{
        display:flex;
        flex-direction:column;
        gap:16px;
        align-items:flex-start;
    }
    .product-showcase-heading__copy{
        min-width:0;
    }
    .product-showcase-heading__copy .kicker{
        margin-bottom:10px;
        color:#344054;
        font-size:13px;
        font-weight:300;
        letter-spacing:.16em;
        text-transform:uppercase;
    }
    .product-showcase-heading__copy h1,
    .product-showcase-heading__copy h2{
        margin:0;
        color:#101828;
        font-family: "Playfair Display", Georgia, "Times New Roman", serif;
        font-size: clamp(44px, 5.5vw, 76px);
        font-weight:500;
        line-height:.94;
        letter-spacing:-.06em;
    }
    .product-showcase-heading__copy h1 span,
    .product-showcase-heading__copy h2 span{
        display:inline-block;
        font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        font-size: clamp(18px, 1.8vw, 26px);
        font-weight:500;
        letter-spacing:-.04em;
        vertical-align:middle;
    }
    .product-showcase-heading__copy p{
        max-width:680px;
        margin:14px 0 0;
        color:#596275;
        font-size:17px;
        line-height:1.58;
    }
    .product-showcase-heading__actions{
        display:flex;
        align-items:center;
        gap:10px;
        width:100%;
    }
    .product-showcase-heading__controls{
        display:flex;
        align-items:center;
        gap:8px;
    }
    .product-showcase-heading__cta{
        width:100%;
    }
    @media (min-width:768px){
        .product-showcase-heading{
            flex-direction:row;
            align-items:flex-end;
        }
        .product-showcase-heading__actions{
            flex-direction:row;
            align-items:center;
            justify-content:flex-end;
            gap:12px;
            margin-left:auto;
            width:auto;
        }
        .product-showcase-heading__cta{
            width:auto;
        }
    }

    @media (max-width:767.98px){
        #home-gifts{
            height:auto !important;
            min-height:0 !important;
            margin-bottom:50px !important;
            padding-bottom:0 !important;
        }
        #home-gifts > .container-page,
        #home-gifts-cards{
            height:auto !important;
            min-height:0 !important;
        }
        #home-gifts-cards{
            margin-bottom:0 !important;
        }
    }
</style>
@endonce
