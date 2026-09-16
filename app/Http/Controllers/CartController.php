<?php

namespace App\Http\Controllers;

use App\Models\OfferingV3;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\SeoStructureService;
use App\Services\MarketplacePricingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\StoreAbandonedCartService;
class CartController extends Controller
{
    public function page(Request $request)
    {
        $items = $this->getCartItems();
        if (!empty($items) && ($request->user()?->email || $request->user())) {
            try {
                app(StoreAbandonedCartService::class)->record(
                    array_values($items),
                    $request->user()?->id,
                    $request->user()?->email,
                    $request->user()?->name,
                    $request->session()->getId()
                );
            } catch (\Throwable $e) { report($e); }
        }

        return view('cart.index');
    }

    public function promo(Request $request)
    {
        $code = strtoupper(trim((string)$request->input('code', '')));
        if ($code !== '') session(['cart_promo_code' => $code]); else session()->forget('cart_promo_code');
        return response()->json(['ok' => true, 'code' => $code]);
    }

    public function count()
    {
        $items = $this->listCartItems();
        $count = array_sum(array_map(fn($it)=> (int)($it['qty'] ?? 0), $items));
        return response()->json(['count'=>$count])->header('Cache-Control','no-store, no-cache, must-revalidate')->header('Pragma','no-cache')->header('Vary','Cookie');
    }

    public function mini(Request $request)
    {
        $items = $this->listCartItems();
        $normalized = [];
        $count = 0;
        $total = 0.0;
        foreach ($items as $row) {
            $price = $this->formatPrice($row['price'] ?? 0);
            $qty = max(1, (int) ($row['qty'] ?? 1));
            $normalized[] = [
                'id' => (string) ($row['id'] ?? Str::uuid()->toString()),
                'title' => $row['title'] ?? 'Item',
                'price' => $price,
                'qty' => $qty,
                'image' => $row['image'] ?? null,
                'url' => $row['url'] ?? '#',
                'variant_label' => $row['variant_label'] ?? null,
                'product_id' => $row['product_id'] ?? null,
                'variant_id' => $row['variant_id'] ?? null,
            ];
            $count += $qty;
            $total += $price * $qty;
        }

        if ($request->wantsJson()) {
            return response()->json([
                'items' => $normalized,
                'count' => $count,
                'total' => $total,
                'currency' => 'GBP',
            ])->header('Cache-Control','no-store, no-cache, must-revalidate')
              ->header('Pragma','no-cache')
              ->header('Vary','Cookie');
        }

        return response(view('partials.mini_cart', [
            'items' => $normalized,
            'count' => $count,
            'total' => $total,
        ])->render(), 200)
            ->header('Content-Type','text/html')
            ->header('Cache-Control','no-store, no-cache, must-revalidate')
            ->header('Pragma','no-cache')
            ->header('Vary','Cookie');
    }

    public function add(Request $request)
    {
        $items = $this->getCartItems();
        $id = $request->input('id');
        $sourceVersion = strtolower(trim((string) $request->input('source_version', '')));
        $variantId = $request->input('variant_id');
        $qty = max(1, (int)$request->input('qty', 1));
        if (!is_numeric($id) || (int) $id <= 0) {
            return response()->json(['ok'=>false,'error'=>'invalid'], 400);
        }

        $normalizeArray = static function ($value): array {
            return is_array($value) ? $value : [];
        };

        $incomingMeta = $normalizeArray($request->input('meta', []));
        $incomingBooking = $normalizeArray($request->input('booking', $incomingMeta['booking'] ?? []));
        $incomingSelected = $request->input('selected', $incomingMeta['selected'] ?? []);
        if (!is_array($incomingSelected)) {
            $incomingSelected = [];
        }
        $incomingOptions = $normalizeArray($request->input('options', $incomingMeta['variant_options'] ?? []));
        $groupCount = $request->input(
            'group_count',
            $request->input('groupCount', $incomingMeta['group_count'] ?? ($incomingMeta['groupCount'] ?? null))
        );
        $reservationId = $request->input(
            'reservation_id',
            $request->input('reservationId', $incomingMeta['reservation_id'] ?? ($incomingMeta['reservationId'] ?? null))
        );
        $holdExpiresAt = $request->input(
            'hold_expires_at',
            $request->input('holdExpiresAt', $incomingMeta['hold_expires_at'] ?? ($incomingMeta['holdExpiresAt'] ?? null))
        );
        $locationValue = $request->input('location', $incomingMeta['location'] ?? null);

        $variantLabel = trim((string)$request->input('variant_label', '')) ?: null;
        $variantOptions = [];
        $image = null;
        $url = '#';
        $vendorId = null;
        $productId = (int) $id;
        $price = 0.0;
        $key = null;
        $offeringVariantId = is_string($variantId) ? trim($variantId) : (string) $variantId;
        $variant = null;

        $product = null;
        $offering = null;
        if ($sourceVersion === 'v3') {
            $offering = OfferingV3::query()->with(['vendor', 'type', 'category', 'media', 'coverMedia'])->find((int) $id);
        }
        if (! $offering) {
            $product = Product::query()->with(['variants'])->withMin('variants','price')->find((int) $id);
        }
        if (! $product && ! $offering) {
            $offering = OfferingV3::query()->with(['vendor', 'type', 'category', 'media', 'coverMedia'])->find((int) $id);
        }
        if (! $product && ! $offering) {
            return response()->json(['ok'=>false,'error'=>'not_found'], 404);
        }

        if ($product) {
            $price = $product->variants_min_price ?? $product->price ?? 0;
            $price = $this->formatPrice($price);
            if (is_numeric($variantId) && (int) $variantId > 0) {
                $variant = $product->variants?->firstWhere('id', (int) $variantId);
                if (! $variant) {
                    $variant = ProductVariant::query()
                        ->where('id', (int) $variantId)
                        ->where('product_id', $product->id)
                        ->first();
                }
            }
            if ($variant) {
                $price = $this->formatPrice($variant->price ?? $price);
                $opts = $variant->options;
                if (is_string($opts)) {
                    $decoded = json_decode($opts, true);
                    $opts = is_array($decoded) ? array_values($decoded) : [];
                } elseif (!is_array($opts)) {
                    $opts = [];
                }
                $variantOptions = array_values(array_filter(array_unique(array_merge($opts, $incomingOptions))));
                if (! $variantLabel) {
                    $variantLabel = $this->buildVariantLabel($variant->title ?? null, $variantOptions);
                }
            }

            $url = app(SeoStructureService::class)->canonicalProductUrl($product);
            $image = method_exists($product,'getFirstImageUrl') ? $product->getFirstImageUrl() : null;
            $vendorId = $product->vendor_id ?? null;
            $key = $this->cartKey($product->id, $variant?->id);
            $productId = (int) $product->id;
        } else {
            $productId = (int) $offering->id;
            $vendorId = $offering->vendor_id ?? null;
            $image = method_exists($offering, 'getFirstImageUrl') ? $offering->getFirstImageUrl() : null;
            $url = app(SeoStructureService::class)->canonicalOfferingUrl($offering);

            $priceOptions = DB::table('offering_price_options')
                ->where('offering_id', $offering->id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
            $tiers = DB::table('offering_price_tiers')
                ->whereIn('price_option_id', $priceOptions->pluck('id'))
                ->get()
                ->keyBy('id');
            $selectedOption = null;
            $selectedTier = null;
            $matchedPriceOptionId = null;
            if ($offeringVariantId !== '') {
                if (preg_match('/^po_(\d+)(?:_tier_(\d+))?$/', $offeringVariantId, $m)) {
                    $matchedPriceOptionId = (int) $m[1];
                    if (! empty($m[2])) {
                        $selectedTier = $tiers->get((int) $m[2]);
                        if ($selectedTier) {
                            $matchedPriceOptionId = (int) $selectedTier->price_option_id;
                        }
                    }
                } elseif (preg_match('/^(\d+)$/', $offeringVariantId, $m)) {
                    $matchedPriceOptionId = (int) $m[1];
                }
            }
            if ($matchedPriceOptionId) {
                $selectedOption = $priceOptions->firstWhere('id', $matchedPriceOptionId);
            }
            if (! $selectedOption) {
                $selectedOption = $priceOptions->first();
            }
            if ($selectedOption) {
                $price = $selectedTier ? (float) ($selectedTier->price_amount ?? $selectedOption->price_amount ?? 0) : (float) ($selectedOption->price_amount ?? 0);
                $formatLabel = null;
                $channel = strtolower(trim((string) ($selectedOption->channel ?? '')));
                if ($channel === 'online') {
                    $formatLabel = 'Online';
                } elseif ($channel === 'in_person') {
                    $formatLabel = 'In-person';
                }
                $audienceType = strtolower(trim((string) ($selectedOption->audience_type ?? '')));
                $pricingType = strtolower(trim((string) ($selectedOption->pricing_type ?? '')));
                $peopleLabel = null;
                if ($selectedTier) {
                    $minQty = (int) ($selectedTier->min_qty ?? 0);
                    $maxQty = $selectedTier->max_qty !== null ? (int) $selectedTier->max_qty : null;
                    if ($minQty === 1) {
                        $peopleLabel = '1 Person';
                    } elseif ($minQty === 2) {
                        $peopleLabel = '2 Persons';
                    } elseif ($maxQty === null || $maxQty <= $minQty) {
                        $peopleLabel = $minQty === 3 ? '3+ Group' : ($minQty . ' People');
                    } elseif ($minQty > 0) {
                        $peopleLabel = $minQty . '-' . $maxQty . ' Group';
                    }
                } elseif ($audienceType === 'solo') {
                    $peopleLabel = '1 Person';
                } elseif ($audienceType === 'couple') {
                    $peopleLabel = '2 Persons';
                } elseif ($audienceType === 'group') {
                    $peopleLabel = $pricingType === 'per_person' ? '3+ Group' : '3+ Group';
                }
                $variantOptions = array_values(array_filter([$formatLabel, $peopleLabel], fn ($v) => $v !== null && $v !== ''));
                if (! $variantLabel) {
                    $variantLabel = $selectedOption->name ?? $this->buildVariantLabel(null, $variantOptions);
                }
                $key = $this->cartKey($offering->id, $offeringVariantId !== '' ? $offeringVariantId : ('po_'.$selectedOption->id));
            } else {
                $price = (float) ($offering->price ?? 0);
                $key = $this->cartKey($offering->id, $offeringVariantId !== '' ? $offeringVariantId : null);
            }
        }

        // Store the customer-facing price in the cart. The checkout fee is no
        // longer a separate line; eligible marketplace prices include it here.
        $price = app(MarketplacePricingService::class)->buyerPrice(
            $price,
            $product?->vendor ?? $offering?->vendor,
            $vendorId ? (int) $vendorId : null,
            $sourceVersion !== 'store'
        );

        $selectedValues = array_values(array_filter(array_unique(array_merge(
            $incomingSelected,
            $variantOptions,
            $variantLabel ? [$variantLabel] : []
        ))));
        $resolvedSourceVersion = $sourceVersion !== '' ? $sourceVersion : ($offering ? 'v3' : 'legacy');
        $metaPayload = array_merge($incomingMeta, [
            'booking' => $incomingBooking,
            'selected' => $selectedValues,
            'group_count' => $groupCount !== null ? (int) $groupCount : null,
            'reservation_id' => $reservationId,
            'hold_expires_at' => $holdExpiresAt,
            'location' => $locationValue,
            'variant_id' => $product ? ($variant?->id ?? ($offeringVariantId !== '' ? $offeringVariantId : null)) : ($offeringVariantId !== '' ? $offeringVariantId : null),
            'variant_label' => $variantLabel,
            'variant_options' => $variantOptions,
            'product_id' => $product ? (int) $productId : $productId,
            'source_version' => $resolvedSourceVersion,
            'price_includes_marketplace_markup' => true,
        ]);
        $metaPayload = array_filter($metaPayload, static function ($value) {
            return $value !== null && $value !== '';
        });

        if(isset($items[$key])){
            $items[$key]['qty'] = (int)($items[$key]['qty'] ?? 1) + $qty;
            $items[$key]['price'] = $price;
            if($variantLabel){ $items[$key]['variant_label'] = $variantLabel; }
            $items[$key]['options'] = $variantOptions;
            $items[$key]['meta'] = array_merge((array) ($items[$key]['meta'] ?? []), $metaPayload);
            $items[$key]['booking'] = $incomingBooking;
            $items[$key]['selected'] = $selectedValues;
            $items[$key]['group_count'] = $groupCount !== null ? (int) $groupCount : ($items[$key]['group_count'] ?? null);
            $items[$key]['reservation_id'] = $reservationId ?? ($items[$key]['reservation_id'] ?? null);
            $items[$key]['hold_expires_at'] = $holdExpiresAt ?? ($items[$key]['hold_expires_at'] ?? null);
            $items[$key]['location'] = $locationValue ?? ($items[$key]['location'] ?? null);
            $items[$key]['source_version'] = $resolvedSourceVersion;
        }
        else {
            $items[$key] = [
                'id' => $key,
                'product_id' => $productId,
                'variant_id' => $product ? ($variant?->id) : ($offeringVariantId !== '' ? $offeringVariantId : null),
                'variant_label' => $variantLabel,
                'options' => $variantOptions,
                'title' => $product ? $product->title : $offering->title,
                'price' => $price,
                'qty' => $qty,
                'image' => $image,
                'url' => $url,
                'vendor_id' => $vendorId,
                'source_version' => $resolvedSourceVersion,
                'meta' => $metaPayload,
                'booking' => $incomingBooking,
                'selected' => $selectedValues,
                'group_count' => $groupCount !== null ? (int) $groupCount : null,
                'reservation_id' => $reservationId,
                'hold_expires_at' => $holdExpiresAt,
                'location' => $locationValue,
            ];
        }
        session(['cart.items' => $items]);
        $cookie = cookie('wow_cart', $this->cartCookiePayload($items), 60*24*30);
        $count = array_sum(array_map(fn($it)=> (int)($it['qty'] ?? 0), $items));
        return response()->json(['ok'=>true,'count'=>$count,'key'=>$key])->withCookie($cookie);
    }

    public function remove(Request $request)
    {
        $items = $this->getCartItems();
        $id = (string) $request->input('id'); unset($items[$id]); session(['cart.items'=>$items]);
        $cookie = cookie('wow_cart', $this->cartCookiePayload($items), 60*24*30);
        return response()->json(['ok'=>true])->withCookie($cookie);
    }

    public function update(Request $request)
    {
        $items = $this->getCartItems();
        $id = (string)$request->input('id'); $qty = max(0,(int)$request->input('qty',1));
        if(!isset($items[$id])) return response()->json(['ok'=>false],404);
        if($qty===0){ unset($items[$id]); } else { $items[$id]['qty']=$qty; }
        session(['cart.items'=>$items]);
        $cookie = cookie('wow_cart', $this->cartCookiePayload($items), 60*24*30);
        return response()->json(['ok'=>true])->withCookie($cookie);
    }

    public function clear(Request $request)
    {
        session()->forget('cart.items');
        $cookie = cookie('wow_cart', '[]', 60*24*30);
        return response()->json(['ok' => true])->withCookie($cookie);
    }

    public function gift(Request $request)
    {
        $code = strtoupper(trim((string)$request->input('code', '')));
        if ($code !== '') session(['cart_gift_code' => $code]); else session()->forget('cart_gift_code');
        return response()->json(['ok' => true, 'code' => $code]);
    }

    protected function getCartItems(): array
    {
        $items = session('cart.items', []);
        if (empty($items)) {
            $cookie = request()->cookie('wow_cart');
            if ($cookie) {
                $restored = json_decode($cookie, true) ?: [];
                $items = $this->normalizeLegacyCart($restored);
                session(['cart.items' => $items]);
            }
        }

        if (isset($items['items']) && is_array($items['items'])) {
            $items = $this->normalizeLegacyCart($items);
            session(['cart.items' => $items]);
        }

        if (!is_array($items)) {
            return [];
        }

        $normalized = [];
        foreach ($items as $key => $row) {
            if (!is_array($row)) {
                continue;
            }
            $line = $this->mapLineItem($row);
            $productId = $line['product_id'] ?? (is_numeric($key) ? (int)$key : null);
            $variantId = $line['variant_id'] ?? null;
            $cartKey = $this->cartKey($productId ?? $key, $variantId);
            $line['id'] = $cartKey;
            $normalized[$cartKey] = $line;
        }
        session(['cart.items' => $normalized]);

        return $normalized;
    }

    protected function listCartItems(): array
    {
        $store = $this->getCartItems();
        $list = [];
        foreach ($store as $id => $row) {
            if (!is_array($row)) {
                continue;
            }
            $line = $row;
            $line['id'] = $line['id'] ?? $id;
            $list[] = $line;
        }
        return $list;
    }

    protected function cartCookiePayload(array $items): string
    {
        $payload = [];
        foreach ($items as $key => $row) {
            if (!is_array($row)) {
                continue;
            }
            $line = $this->mapLineItem($row);
            $lineId = $line['id'] ?? (is_string($key) ? $key : null);
            if ($lineId === null || $lineId === '') {
                $lineId = $this->cartKey($line['product_id'] ?? $key, $line['variant_id'] ?? null);
            }
            $payload[] = [
                'id' => (string) $lineId,
                'product_id' => $line['product_id'] ?? null,
                'variant_id' => $line['variant_id'] ?? null,
                'variant_label' => $line['variant_label'] ?? '',
                'title' => $line['title'] ?? '',
                'price' => $this->formatPrice($line['price'] ?? 0),
                'qty' => max(1, (int) ($line['qty'] ?? 1)),
                'image' => $line['image'] ?? null,
                'url' => $line['url'] ?? '#',
            ];
        }

        $encoded = json_encode(array_values($payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded !== false ? $encoded : '[]';
    }

    protected function normalizeLegacyCart(array $payload): array
    {
        if (isset($payload['items']) && is_array($payload['items'])) {
            $payload = $payload['items'];
        }

        $normalized = [];
        foreach ($payload as $key => $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $line = $this->mapLineItem($entry);
            $productId = $line['product_id'] ?? (is_numeric($key) ? (int)$key : null);
            $cartKey = $this->cartKey($productId ?? $key, $line['variant_id'] ?? null);
            $line['id'] = $cartKey;
            $normalized[$cartKey] = $line;
        }

        return $normalized;
    }

    protected function mapLineItem(array $entry): array
    {
        $priceIsMinor = ! array_key_exists('price', $entry) && ! array_key_exists('unit', $entry) && array_key_exists('unit_amount', $entry);
        $price = $entry['price'] ?? $entry['unit'] ?? ($priceIsMinor ? ((float) $entry['unit_amount'] / 100) : 0);
        $variantId = $entry['variant_id'] ?? null;
        $variantLabel = $entry['variant_label'] ?? $entry['options_label'] ?? null;
        $options = $entry['options'] ?? [];
        if (!is_array($options)) {
            $options = [];
        }
        $meta = is_array($entry['meta'] ?? null) ? $entry['meta'] : [];
        $booking = $entry['booking'] ?? ($meta['booking'] ?? []);
        if (!is_array($booking)) {
            $booking = [];
        }
        $selected = $entry['selected'] ?? ($meta['selected'] ?? []);
        if (!is_array($selected)) {
            $selected = [];
        }
        $groupCount = $entry['group_count'] ?? $entry['groupCount'] ?? ($meta['group_count'] ?? ($meta['groupCount'] ?? null));
        $reservationId = $entry['reservation_id'] ?? $entry['reservationId'] ?? ($meta['reservation_id'] ?? ($meta['reservationId'] ?? null));
        $holdExpiresAt = $entry['hold_expires_at'] ?? $entry['holdExpiresAt'] ?? ($meta['hold_expires_at'] ?? ($meta['holdExpiresAt'] ?? null));
        $location = $entry['location'] ?? ($meta['location'] ?? null);
        $sourceVersion = strtolower(trim((string) ($entry['source_version'] ?? ($meta['source_version'] ?? ''))));
        $rawProductId = $entry['product_id'] ?? $entry['productId'] ?? null;
        if ($rawProductId === null && isset($entry['id'])) {
            $rawProductId = str_starts_with((string)$entry['id'], 'p:') ? substr((string)$entry['id'], 2) : $entry['id'];
        }
        $meta = array_merge($meta, [
            'booking' => $booking,
            'selected' => array_values(array_filter($selected)),
            'group_count' => $groupCount !== null ? (int) $groupCount : null,
            'reservation_id' => $reservationId,
            'hold_expires_at' => $holdExpiresAt,
            'location' => $location,
            'product_id' => $rawProductId,
            'variant_id' => $variantId,
            'variant_label' => $variantLabel,
            'variant_options' => $options,
            'source_version' => $sourceVersion !== '' ? $sourceVersion : null,
        ]);
        $meta = array_filter($meta, static function ($value) {
            return $value !== null && $value !== '';
        });
        return [
            'product_id' => $rawProductId,
            'vendor_id' => $entry['vendor_id'] ?? $entry['vendorId'] ?? null,
            'variant_id' => $variantId,
            'variant_label' => $variantLabel,
            'options' => $options,
            'title' => $entry['title'] ?? $entry['name'] ?? 'Item',
            'price' => (float) ($price ?? 0),
            'qty' => max(1, (int) ($entry['qty'] ?? $entry['quantity'] ?? 1)),
            'image' => $entry['image'] ?? $entry['img'] ?? null,
            'url' => $entry['url'] ?? $entry['href'] ?? '#',
            'meta' => $meta,
            'booking' => $booking,
            'selected' => array_values(array_filter($selected)),
            'group_count' => $groupCount !== null ? (int) $groupCount : null,
            'reservation_id' => $reservationId,
            'hold_expires_at' => $holdExpiresAt,
            'location' => $location,
            'source_version' => $sourceVersion !== '' ? $sourceVersion : null,
        ];
    }

    protected function formatPrice($value): float
    {
        return (float) $value;
    }

    protected function buildVariantLabel(?string $title, array $options = []): ?string
    {
        $cleanTitle = trim((string)$title);
        if ($cleanTitle !== '' && stripos($cleanTitle, 'default') === false) {
            return $cleanTitle;
        }
        $values = array_values(array_filter(array_map(function ($value) {
            if (is_string($value)) {
                return trim($value);
            }
            if (is_scalar($value)) {
                return trim((string)$value);
            }
            return '';
        }, $options), fn($v) => $v !== ''));
        if (!empty($values)) {
            return implode(' • ', $values);
        }
        return null;
    }

    protected function cartKey($productId, $variantId = null): string
    {
        if ($variantId) {
            return 'v:' . (string)$variantId;
        }
        if (is_string($productId) && (str_starts_with($productId, 'v:') || str_starts_with($productId, 'p:'))) {
            return $productId;
        }
        return 'p:' . (string)$productId;
    }
}
