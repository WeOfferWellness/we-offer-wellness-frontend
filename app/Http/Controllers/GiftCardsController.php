<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Support\Str;

class GiftCardsController extends Controller
{
    public function index()
    {
        $giftCards = Product::query()
            ->whereHas('options', function ($q) {
                $q->where('meta_name', 'denominations');
            })
            ->where(function ($q) {
                $q->whereRaw("LOWER(COALESCE(title,'')) like ?", ['%gift%'])
                  ->orWhereRaw("LOWER(COALESCE(product_type,'')) like ?", ['%gift%'])
                  ->orWhereRaw("LOWER(COALESCE(tags_list,'')) like ?", ['%gift%'])
                  ->orWhereRaw("LOWER(COALESCE(title,'')) like ?", ['%voucher%']);
            })
            ->whereRaw("LOWER(COALESCE(title,'')) not like ?", ['%corporate%'])
            ->where(function ($q) {
                $q->whereHas('status', function ($qs) {
                    $qs->whereIn('status', ['live', 'approved']);
                })->orWhereNull('product_status_id');
            })
            ->with(['options.values', 'variants', 'media', 'category', 'status'])
            ->get()
            ->map(function (Product $product) {
                $denominationOption = $product->options->firstWhere('meta_name', 'denominations');
                $denominations = [];

                $variants = $product->variants->sortBy(function ($variant) {
                    return (float) ($variant->price ?? 0);
                })->values();

                foreach ($variants as $variant) {
                    $variantData = [];
                    try {
                        $variantData = json_decode((string) ($variant->options ?? ''), true) ?: [];
                    } catch (\Throwable $e) {
                        $variantData = [];
                    }

                    $label = trim((string) ($variantData['option1'] ?? ''));
                    if ($label === '' && $denominationOption) {
                        $label = (string) ($denominationOption->values->pluck('value')->first() ?? '');
                    }
                    if ($label === '') {
                        $label = '£' . number_format((float) ($variant->price ?? 0), 2);
                    }

                    $denominations[] = [
                        'label' => $label,
                        'price' => (float) ($variant->price ?? 0),
                        'price_label' => '£' . number_format((float) ($variant->price ?? 0), 2),
                        'variant_id' => (int) $variant->id,
                        'sku' => (string) ($variant->sku ?? ''),
                    ];
                }

                $sortWeight = Str::lower((string) $product->title) === 'gift card' ? 0 : 1;

                return [
                    'id' => (int) $product->id,
                    'title' => (string) $product->title,
                    'summary' => (string) ($product->summary ?? ''),
                    'handle' => (string) ($product->handle ?? ''),
                    'image' => $product->getFirstImageUrl(),
                    'denominations' => $denominations,
                    'sort_weight' => $sortWeight,
                ];
            })
            ->sortBy([
                ['sort_weight', 'asc'],
                ['title', 'asc'],
            ])
            ->values()
            ->all();

        return view('giftcards.index', [
            'giftCards' => $giftCards,
        ]);
    }
}
