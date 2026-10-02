<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OfferingV3;
use App\Models\Product;
use App\Services\SeoStructureService;
use App\Support\EventListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class WellnessMatchResultsController extends Controller
{
    public function __invoke(Request $request, SeoStructureService $seo): JsonResponse
    {
        $payload = $request->validate([
            'answers' => ['required', 'array', 'max:40'],
            'answers.*.question' => ['nullable', 'string', 'max:500'],
            'answers.*.values' => ['required', 'array', 'max:20'],
            'answers.*.values.*' => ['nullable', 'string', 'max:500'],
        ]);

        $profile = $this->profile(collect($payload['answers']));

        $products = Product::query()
            ->whereHas('status', fn ($query) => $query->whereIn('status', ['live', 'approved']))
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->withMin('variants', 'price')
            ->with(['media', 'options.values', 'category', 'subcategory', 'vendor'])
            ->latest('updated_at')
            ->limit(180)
            ->get()
            ->reject(fn (Product $product) => EventListing::isPast($product))
            ->map(fn (Product $product) => $this->productCandidate($product, $seo));

        $offerings = OfferingV3::query()
            ->whereIn('status', ['live', 'approved'])
            ->with(['category', 'subcategory', 'type', 'vendor', 'media', 'coverMedia'])
            ->latest('updated_at')
            ->limit(180)
            ->get()
            ->reject(fn (OfferingV3 $offering) => EventListing::isPast($offering))
            ->map(fn (OfferingV3 $offering) => $this->offeringCandidate($offering, $seo));

        $matches = $products->concat($offerings)
            ->filter(fn (array $item) => filled($item['title']) && filled($item['image']))
            ->map(function (array $item) use ($profile) {
                [$score, $reasons] = $this->score($item, $profile);
                $item['match_score'] = $score;
                $item['reasons'] = $reasons;

                return $item;
            })
            ->sortByDesc(fn (array $item) => ($item['match_score'] * 1000) + ($item['rating'] * 10) + min($item['review_count'], 99))
            ->unique(fn (array $item) => Str::lower($item['title'].'|'.$item['vendor_name']))
            ->take(12)
            ->values();

        return response()->json([
            'data' => $matches,
            'profile' => [
                'mode' => $profile['mode'],
                'budget' => $profile['budget_label'],
                'postcode' => $profile['postcode'],
                'preferences' => array_slice($profile['display_values'], 0, 8),
            ],
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    private function profile(Collection $answers): array
    {
        $rows = $answers->map(function (array $answer) {
            return [
                'question' => Str::lower(Str::squish((string) ($answer['question'] ?? ''))),
                'values' => collect($answer['values'] ?? [])->map(fn ($value) => Str::squish((string) $value))->filter()->values()->all(),
            ];
        });
        $values = $rows->pluck('values')->flatten()->filter()->values();
        $combined = Str::lower($rows->flatMap(fn ($row) => [$row['question'], ...$row['values']])->implode(' '));

        $mode = str_contains($combined, 'online') && ! str_contains($combined, 'in person') ? 'online'
            : (str_contains($combined, 'in person') && ! str_contains($combined, 'online') ? 'in-person' : 'either');
        $postcodeRow = $rows->first(fn ($row) => str_contains($row['question'], 'postcode'));
        $postcode = (string) data_get($postcodeRow, 'values.0', '');
        $budgetValue = (string) $values->first(fn ($value) => str_contains(Str::lower($value), '£') || str_contains(Str::lower($value), 'under'));
        [$budgetMin, $budgetMax] = $this->budgetRange($budgetValue);

        $terms = $values->flatMap(fn ($value) => $this->expandedTerms($value))->map(fn ($value) => Str::lower($value))->unique()->values()->all();

        return [
            'combined' => $combined,
            'terms' => $terms,
            'mode' => $mode,
            'postcode' => Str::lower($postcode),
            'budget_min' => $budgetMin,
            'budget_max' => $budgetMax,
            'budget_label' => $budgetValue,
            'display_values' => $values->reject(fn ($value) => $value === $postcode)->unique()->values()->all(),
            'avoid_touch' => str_contains($combined, 'prefer no touch'),
            'avoid_spiritual' => str_contains($combined, 'non-spiritual'),
        ];
    }

    private function expandedTerms(string $value): array
    {
        $normal = Str::lower($value);
        $terms = [$value];
        $groups = [
            ['stress', 'overwhelm', 'anxiety', 'calm', 'relax', 'grounded', 'restored', 'clearer'],
            ['sleep', 'meditation', 'hypnotherapy', 'sound bath', 'massage'],
            ['energy', 'energised', 'yoga', 'pilates', 'breathwork', 'reiki', 'fitness'],
            ['pain', 'tension', 'massage', 'physiotherapy', 'osteopathy', 'acupuncture', 'chiropractic', 'reflexology'],
            ['emotional', 'confidence', 'talking', 'reflection', 'counselling', 'coaching', 'hypnotherapy', 'mindfulness'],
            ['hands-on', 'massage', 'reflexology', 'acupuncture', 'osteopathy'],
            ['movement', 'yoga', 'pilates', 'fitness', 'dance', 'tai chi'],
            ['sound', 'sound bath', 'sound healing'],
            ['spiritual', 'energy work', 'reiki', 'energy healing'],
            ['small group', 'class', 'workshop', 'event', 'group'],
            ['one-to-one', '1:1', 'private', 'consultation', 'session'],
        ];

        foreach ($groups as $group) {
            if (collect($group)->contains(fn ($needle) => str_contains($normal, $needle))) {
                $terms = [...$terms, ...$group];
            }
        }

        return $terms;
    }

    private function budgetRange(string $value): array
    {
        $normal = Str::lower($value);
        preg_match_all('/\d+/', $normal, $matches);
        $numbers = array_map('intval', $matches[0] ?? []);

        if (str_contains($normal, 'under') && isset($numbers[0])) return [null, $numbers[0]];
        if (count($numbers) >= 2) return [$numbers[0], $numbers[1]];
        if (str_contains($normal, '+') && isset($numbers[0])) return [$numbers[0], null];

        return [null, null];
    }

    private function score(array $item, array $profile): array
    {
        $haystack = Str::lower(implode(' ', array_filter([
            $item['title'], $item['summary'], $item['type'], $item['category'], $item['subcategory'],
            implode(' ', $item['tags']), implode(' ', $item['locations']), $item['mode'],
        ])));
        $score = 0;
        $reasons = [];

        foreach ($profile['terms'] as $term) {
            $term = Str::lower(trim($term));
            if (strlen($term) < 3 || ! str_contains($haystack, $term)) continue;
            $score += str_contains(Str::lower($item['title'].' '.$item['category'].' '.$item['type']), $term) ? 7 : 3;
            $reasons[] = Str::headline($term);
        }

        if ($profile['mode'] === 'online') {
            $score += $item['mode'] === 'online' || in_array('Online', $item['locations'], true) ? 14 : -18;
            $reasons[] = 'Available online';
        } elseif ($profile['mode'] === 'in-person') {
            $score += $item['mode'] === 'in-person' ? 14 : -18;
            $reasons[] = 'In person';
        }

        if ($profile['postcode'] !== '' && str_contains(Str::lower(implode(' ', $item['locations'])), strtok($profile['postcode'], ' '))) {
            $score += 16;
            $reasons[] = 'Near your postcode';
        }

        if ($item['price'] !== null) {
            if ($profile['budget_max'] !== null) $score += $item['price'] <= $profile['budget_max'] ? 10 : -12;
            if ($profile['budget_min'] !== null) $score += $item['price'] >= $profile['budget_min'] ? 5 : -4;
            if (($profile['budget_min'] !== null || $profile['budget_max'] !== null) && $score > 0) $reasons[] = 'Fits your budget';
        }

        if ($profile['avoid_touch'] && preg_match('/massage|hands-on|reflexology|osteopath|chiropract|acupuncture/', $haystack)) $score -= 24;
        if ($profile['avoid_spiritual'] && preg_match('/reiki|spiritual|energy healing|chakra/', $haystack)) $score -= 24;
        $score += min(5, (int) floor($item['review_count'] / 5));

        return [$score, array_values(array_unique(array_slice($reasons, 0, 3)))];
    }

    private function productCandidate(Product $product, SeoStructureService $seo): array
    {
        $locations = $product->getLocations();
        $price = $product->variants_min_price ?? $product->price;
        $price = $price !== null ? (float) $price : null;
        if ($price !== null && $price >= 1000) $price /= 100;

        return [
            'id' => 'product-'.$product->id,
            'source' => 'store',
            'source_id' => (int) $product->id,
            'title' => (string) $product->title,
            'summary' => (string) ($product->summary ?? ''),
            'type' => (string) ($product->product_type ?: 'Wellness experience'),
            'category' => (string) ($product->category?->name ?? ''),
            'subcategory' => (string) ($product->subcategory?->name ?? ''),
            'vendor_name' => (string) ($product->vendor?->vendor_name ?? ''),
            'mode' => in_array('Online', $locations, true) && count($locations) === 1 ? 'online' : 'in-person',
            'locations' => $locations,
            'price' => $price,
            'currency' => 'GBP',
            'rating' => round((float) ($product->reviews_avg_rating ?? 0), 1),
            'review_count' => (int) ($product->reviews_count ?? 0),
            'image' => $product->getFirstImageUrl(),
            'tags' => array_values(array_filter(array_map('trim', explode(',', (string) $product->tags_list)))),
            'url' => $seo->canonicalProductUrl($product),
        ];
    }

    private function offeringCandidate(OfferingV3 $offering, SeoStructureService $seo): array
    {
        $locations = $offering->getLocations();

        return [
            'id' => 'offering-'.$offering->id,
            'source' => 'v3',
            'source_id' => (int) $offering->id,
            'title' => (string) $offering->title,
            'summary' => (string) ($offering->summary ?? ''),
            'type' => (string) ($offering->type?->name ?? 'Wellness experience'),
            'category' => (string) ($offering->category?->name ?? ''),
            'subcategory' => (string) ($offering->subcategory?->name ?? ''),
            'vendor_name' => (string) ($offering->vendor?->vendor_name ?? ''),
            'mode' => in_array('Online', $locations, true) && count($locations) === 1 ? 'online' : 'in-person',
            'locations' => $locations,
            'price' => $offering->price !== null ? (float) $offering->price : null,
            'currency' => 'GBP',
            'rating' => 0,
            'review_count' => 0,
            'image' => $offering->getFirstImageUrl(),
            'tags' => array_values(array_filter([$offering->type?->name, $offering->category?->name])),
            'url' => $seo->canonicalOfferingUrl($offering),
        ];
    }
}
