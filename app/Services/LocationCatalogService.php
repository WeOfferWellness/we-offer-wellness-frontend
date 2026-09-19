<?php

namespace App\Services;

use App\Models\OfferingV3;
use App\Models\Product;
use App\Models\VendorLocation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LocationCatalogService
{
    public function load(bool $fresh = false): array
    {
        $path = $this->catalogPath();

        if (! $fresh && File::exists($path)) {
            $decoded = json_decode((string) File::get($path), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return $this->build();
    }

    public function export(?string $path = null): string
    {
        $path = $path ?: $this->catalogPath();
        $catalog = $this->build();

        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($catalog, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $path;
    }

    public function catalogPath(): string
    {
        return public_path('cache/locations.json');
    }

    public function build(): array
    {
        $countyColumn = $this->countyColumn();
        $rows = VendorLocation::query()
            ->select([
                'vendor_id',
                'label',
                'city',
                'formatted_address',
                'country',
                'lat',
                'lng',
                'image_path',
                DB::raw("COALESCE({$countyColumn}, '') as county_raw"),
            ])
            ->where(function ($query) use ($countyColumn): void {
                $query->whereNotNull('city')
                    ->orWhereNotNull($countyColumn)
                    ->orWhereNotNull('country');
            })
            ->orderByRaw("LOWER(COALESCE(country, ''))")
            ->orderByRaw("LOWER(COALESCE({$countyColumn}, ''))")
            ->orderByRaw("LOWER(COALESCE(city, ''))")
            ->get();

        $vendorProductCounts = Product::query()
            ->whereHas('status', function ($status): void {
                $status->whereIn('status', ['live', 'approved']);
            })
            ->selectRaw('vendor_id, COUNT(*) as total')
            ->groupBy('vendor_id')
            ->pluck('total', 'vendor_id')
            ->map(fn ($value): int => (int) $value)
            ->all();

        $vendorOfferingCounts = OfferingV3::query()
            ->whereIn('status', ['live', 'approved'])
            ->selectRaw('vendor_id, COUNT(*) as total')
            ->groupBy('vendor_id')
            ->pluck('total', 'vendor_id')
            ->map(fn ($value): int => (int) $value)
            ->all();

        $onlineProductCount = Product::query()
            ->whereHas('status', function ($status): void {
                $status->whereIn('status', ['live', 'approved']);
            })
            ->whereHas('options.values', function ($values): void {
                $values->whereRaw("LOWER(TRIM(COALESCE(value, ''))) = 'online'");
            })
            ->distinct('products.id')
            ->count('products.id');

        $onlineOfferingCount = DB::table('offerings as o')
            ->join('offering_channels as oc', 'oc.offering_id', '=', 'o.id')
            ->whereIn('o.status', ['live', 'approved'])
            ->where('oc.is_enabled', true)
            ->whereRaw("LOWER(TRIM(COALESCE(oc.channel, ''))) = 'online'")
            ->distinct()
            ->count('o.id');

        $onlineNode = [
            'key' => 'online',
            'slug' => 'online',
            'title' => 'Online',
            'country' => 'United Kingdom',
            'county' => null,
            'district' => null,
            'region' => null,
            'city' => 'Online',
            'town' => 'Online',
            'path' => '/online',
            'online' => true,
            'lat' => null,
            'lng' => null,
            'counts' => [
                'products' => (int) $onlineProductCount,
                'offerings' => (int) $onlineOfferingCount,
                'total' => (int) ($onlineProductCount + $onlineOfferingCount),
            ],
            'locations' => [],
        ];

        $nodes = [];
        $countries = [];
        $seenVendorPaths = [];

        foreach ($rows as $row) {
            $countryLabel = $this->normalizeCountryLabel((string) ($row->country ?? 'United Kingdom'));
            $segments = $this->parseLocationSegments((string) ($row->label ?? ''), (string) ($row->formatted_address ?? ''), (string) ($row->city ?? ''), (string) ($row->county_raw ?? ''));

            $townLabel = $this->normalizeTownLabel((string) ($row->city ?? ''), $segments);
            $districtLabel = $this->normalizeDistrictLabel((string) ($row->county_raw ?? ''), $segments);
            $countyLabel = $this->resolveCountyLabel($countryLabel, $districtLabel, $townLabel, $segments);

            $countrySlug = $this->normalizeCountrySlug($countryLabel);
            $countySlug = $countyLabel !== null ? $this->normalizeCountySlug($countyLabel) : null;
            $townSlug = $townLabel !== null ? Str::slug($townLabel) : null;

            $path = $this->canonicalLocationPath($countrySlug, $countySlug, $townSlug);
            $vendorPathKey = ($row->vendor_id ?? '0') . '|' . $path;

            if (isset($seenVendorPaths[$vendorPathKey])) {
                continue;
            }

            $seenVendorPaths[$vendorPathKey] = true;

            $counts = [
                'products' => (int) ($vendorProductCounts[$row->vendor_id] ?? 0),
                'offerings' => (int) ($vendorOfferingCounts[$row->vendor_id] ?? 0),
            ];
            $counts['total'] = $counts['products'] + $counts['offerings'];

            $title = $townLabel ?: $districtLabel ?: $countryLabel;
            $slug = trim(str_replace('/locations/', '', $path), '/');
            $node = [
                'key' => $slug,
                'slug' => $slug,
                'title' => $title,
                'country' => $countryLabel,
                'country_slug' => $countrySlug,
                'county' => $countyLabel,
                'county_slug' => $countySlug,
                'district' => $districtLabel,
                'region' => $districtLabel,
                'city' => $townLabel,
                'town' => $townLabel,
                'path' => $path,
                'online' => false,
                'lat' => isset($row->lat) ? (float) $row->lat : null,
                'lng' => isset($row->lng) ? (float) $row->lng : null,
                'image_path' => trim((string) ($row->image_path ?? '')) ?: null,
                'counts' => $counts,
                'locations' => [$this->formatLocationSummary($townLabel, $countyLabel, $countryLabel)],
                'source' => $this->formatSourceSummary($row),
            ];

            $nodes[$path] = $node;

            $countryKey = $countrySlug;
            $countyKey = $countySlug ?: Str::slug($townLabel ?: $countryLabel);

            if (!isset($countries[$countryKey])) {
                $countries[$countryKey] = [
                    'key' => $countryKey,
                    'slug' => $countryKey,
                    'label' => $countryLabel,
                    'title' => $countryLabel,
                    'path' => '/locations/' . $countryKey,
                    'online' => false,
                    'counts' => ['products' => 0, 'offerings' => 0, 'total' => 0],
                    'counties' => [],
                ];
            }

            if (!isset($countries[$countryKey]['counties'][$countyKey])) {
                $countyLabelForGroup = $countyLabel ?: $townLabel ?: $countryLabel;
                $countyPath = $countyLabel ? '/locations/' . $countryKey . '/' . $countyKey : $path;
                $countries[$countryKey]['counties'][$countyKey] = [
                    'key' => $countryKey . '/' . $countyKey,
                    'slug' => $countyKey,
                    'label' => $countyLabelForGroup,
                    'title' => $countyLabelForGroup,
                    'path' => $countyPath,
                    'country' => $countryLabel,
                    'county' => $countyLabel,
                    'district' => $districtLabel,
                    'region' => $districtLabel,
                    'counts' => ['products' => 0, 'offerings' => 0, 'total' => 0],
                    'towns' => [],
                ];
            }

            $countries[$countryKey]['counts']['products'] += $counts['products'];
            $countries[$countryKey]['counts']['offerings'] += $counts['offerings'];
            $countries[$countryKey]['counts']['total'] += $counts['total'];

            $countries[$countryKey]['counties'][$countyKey]['counts']['products'] += $counts['products'];
            $countries[$countryKey]['counties'][$countyKey]['counts']['offerings'] += $counts['offerings'];
            $countries[$countryKey]['counties'][$countyKey]['counts']['total'] += $counts['total'];

            $countries[$countryKey]['counties'][$countyKey]['towns'][$slug] = $node;
        }

        foreach ($countries as &$country) {
            foreach ($country['counties'] as &$county) {
                $county['towns'] = array_values($county['towns']);
                usort($county['towns'], static function (array $left, array $right): int {
                    return strcasecmp((string) ($left['title'] ?? ''), (string) ($right['title'] ?? ''));
                });
            }
            unset($county);

            $country['counties'] = array_values($country['counties']);
            usort($country['counties'], static function (array $left, array $right): int {
                return strcasecmp((string) ($left['label'] ?? ''), (string) ($right['label'] ?? ''));
            });
        }
        unset($country);

        $countryList = array_values($countries);
        usort($countryList, static function (array $left, array $right): int {
            return strcasecmp((string) ($left['label'] ?? ''), (string) ($right['label'] ?? ''));
        });

        $flat = array_values($nodes);
        usort($flat, static function (array $left, array $right): int {
            $leftTotal = (int) data_get($left, 'counts.total', 0);
            $rightTotal = (int) data_get($right, 'counts.total', 0);

            if ($leftTotal !== $rightTotal) {
                return $rightTotal <=> $leftTotal;
            }

            return strcasecmp((string) ($left['title'] ?? ''), (string) ($right['title'] ?? ''));
        });

        array_unshift($flat, $onlineNode);
        array_unshift($countryList, [
            'key' => 'online',
            'slug' => 'online',
            'label' => 'Online',
            'title' => 'Online',
            'path' => '/online',
            'online' => true,
            'counts' => $onlineNode['counts'],
            'counties' => [],
        ]);

        $suggestions = $this->buildTrendingSuggestions($onlineNode, $countries, $flat);

        return [
            'generated_at' => now()->toIso8601String(),
            'stats' => [
                'countries' => count($countryList),
                'counties' => collect($countryList)->sum(fn (array $country): int => count($country['counties'] ?? [])),
                'towns' => count($nodes),
                'products' => array_sum($vendorProductCounts),
                'offerings' => array_sum($vendorOfferingCounts),
                'online_products' => (int) $onlineProductCount,
                'online_offerings' => (int) $onlineOfferingCount,
            ],
            'countries' => $countryList,
            'flat' => $flat,
            'suggestions' => $suggestions,
        ];
    }

    /**
     * Build the five-item trending list shown in the homepage/search dropdown.
     *
     * We keep this separate from the full flat catalog so typed search can still
     * search the entire dataset without exposing street-level address rows.
     *
     * @param array<string, mixed> $onlineNode
     * @param array<string, array<string, mixed>> $countries
     * @param array<int, array<string, mixed>> $flat
     * @return array<int, array<string, mixed>>
     */
    private function buildTrendingSuggestions(array $onlineNode, array $countries, array $flat): array
    {
        $suggestions = [$onlineNode];

        $preferredPaths = [
            '/locations/united-kingdom/kent',
            '/locations/united-kingdom/london',
            '/locations/united-kingdom/manchester',
            '/locations/united-kingdom/east-sussex/brighton-and-hove',
        ];

        foreach ($preferredPaths as $path) {
            $node = $this->findNodeByPath($countries, $flat, $path);
            if ($node === null) {
                continue;
            }

            if ($path === '/locations/united-kingdom/east-sussex/brighton-and-hove') {
                $node['title'] = 'Brighton & Hove';
                $node['label'] = 'Brighton & Hove';
                $node['value'] = 'Brighton & Hove';
            }

            if ($path === '/locations/united-kingdom/manchester') {
                $node['title'] = 'Manchester';
                $node['label'] = 'Manchester';
                $node['value'] = 'Manchester';
            }

            if ($path === '/locations/united-kingdom/london') {
                $node['title'] = 'London';
                $node['label'] = 'London';
                $node['value'] = 'London';
            }

            if ($path === '/locations/united-kingdom/kent') {
                $node['title'] = 'Kent';
                $node['label'] = 'Kent';
                $node['value'] = 'Kent';
            }

            $suggestions[] = $node;
        }

        usort($suggestions, static function (array $left, array $right): int {
            $leftOnline = !empty($left['online']);
            $rightOnline = !empty($right['online']);

            if ($leftOnline !== $rightOnline) {
                return $leftOnline ? -1 : 1;
            }

            $leftTotal = (int) data_get($left, 'counts.total', 0);
            $rightTotal = (int) data_get($right, 'counts.total', 0);
            if ($leftTotal !== $rightTotal) {
                return $rightTotal <=> $leftTotal;
            }

            return strcasecmp((string) ($left['title'] ?? ''), (string) ($right['title'] ?? ''));
        });

        return array_values(array_slice($suggestions, 0, 5));
    }

    /**
     * @param array<string, array<string, mixed>> $countries
     * @param array<int, array<string, mixed>> $flat
     * @return array<string, mixed>|null
     */
    private function findNodeByPath(array $countries, array $flat, string $path): ?array
    {
        foreach ($flat as $node) {
            if ((string) ($node['path'] ?? '') === $path) {
                return $node;
            }
        }

        foreach ($countries as $country) {
            foreach (($country['counties'] ?? []) as $county) {
                if ((string) ($county['path'] ?? '') === $path) {
                    return $county;
                }

                foreach (($county['towns'] ?? []) as $town) {
                    if ((string) ($town['path'] ?? '') === $path) {
                        return $town;
                    }
                }
            }
        }

        return null;
    }

    private function formatSourceSummary(object $row): string
    {
        $parts = $this->extractLocationSegments(
            (string) ($row->label ?? ''),
            (string) ($row->formatted_address ?? ''),
            (string) ($row->city ?? ''),
            (string) ($row->county_raw ?? '')
        );

        $country = $this->normalizeCountryLabel((string) ($row->country ?? 'United Kingdom'));
        if ($country !== '' && !in_array($country, $parts, true)) {
            $parts[] = $country;
        }

        return implode(' | ', array_values(array_unique($parts)));
    }

    private function formatLocationSummary(?string $town, ?string $county, string $country): string
    {
        return collect(array_filter([$town, $county, $country]))->implode(', ');
    }

    /**
     * Extract non-street location fragments from raw location fields.
     *
     * This intentionally drops house numbers, street names, and postcode lines so
     * the exported catalog only ever stores town/city-level labels and above.
     *
     * @return array<int, string>
     */
    private function extractLocationSegments(string ...$sources): array
    {
        $segments = [];

        foreach ($sources as $source) {
            $source = trim($source);
            if ($source === '') {
                continue;
            }

            foreach (preg_split('/\s*,\s*/', $source) ?: [] as $part) {
                $part = trim((string) $part);
                if ($part === '') {
                    continue;
                }

                if ($this->isGenericCountryLabel($part)) {
                    continue;
                }

                if ($this->looksLikePostcode($part)) {
                    continue;
                }

                if ($this->looksLikeStreetLevelLabel($part)) {
                    continue;
                }

                $segments[] = $part;
            }
        }

        return array_values(array_unique($segments));
    }

    private function parseLocationSegments(string ...$sources): array
    {
        $segments = $this->extractLocationSegments(...$sources);

        return [
            'city' => $segments[0] ?? null,
            'town' => $segments[0] ?? null,
            'district' => $segments[1] ?? null,
            'county' => $segments[1] ?? null,
            'region' => $segments[1] ?? null,
            'segments' => $segments,
        ];
    }

    private function normalizeCountryLabel(string $country): string
    {
        $country = trim($country);
        if ($country === '' || $this->isGenericCountryLabel($country)) {
            return 'United Kingdom';
        }

        return Str::of($country)->headline()->toString();
    }

    private function normalizeTownLabel(string $town, array $segments): ?string
    {
        $town = trim($town);
        if ($town !== '' && ! $this->isGenericCountryLabel($town)) {
            return Str::of($town)->headline()->toString();
        }

        $candidate = $segments['city'] ?? null;
        if (is_string($candidate) && $candidate !== '' && ! $this->looksLikePostcode($candidate)) {
            return Str::of($candidate)->headline()->toString();
        }

        return null;
    }

    private function normalizeDistrictLabel(string $district, array $segments): ?string
    {
        $district = trim($district);
        if ($district !== '' && ! $this->isGenericCountryLabel($district)) {
            return Str::of($district)->headline()->toString();
        }

        $candidate = $segments['district'] ?? null;
        if (is_string($candidate) && $candidate !== '' && ! $this->looksLikePostcode($candidate)) {
            return Str::of($candidate)->headline()->toString();
        }

        return null;
    }

    private function resolveCountyLabel(string $country, ?string $district, ?string $town, array $segments): ?string
    {
        $candidate = $district ?: ($segments['county'] ?? null);

        if (is_string($candidate)) {
            $candidate = trim($candidate);
        }

        if ($candidate === null || $candidate === '' || $this->isGenericCountyLabel($candidate)) {
            $candidate = $segments['region'] ?? null;
        }

        if (is_string($candidate)) {
            $candidate = trim($candidate);
        }

        if ($candidate === null || $candidate === '' || $this->isGenericCountyLabel($candidate)) {
            $candidate = null;
        }

        if ($candidate !== null) {
            $candidate = $this->aliasCountyLabel($candidate);
        }

        if ($town !== null && Str::slug($town) === 'london') {
            return null;
        }

        return $candidate !== null && $candidate !== '' ? Str::of($candidate)->headline()->toString() : null;
    }

    private function aliasCountyLabel(string $county): string
    {
        $key = Str::slug($county);
        $aliases = $this->countyAliases();

        return $aliases[$key] ?? $county;
    }

    private function countyAliases(): array
    {
        return [
            'medway' => 'Kent',
            'greater-london' => 'London',
            'greater-manchester' => 'Manchester',
            'brighton-and-hove' => 'East Sussex',
            'bristol-city' => 'Bristol',
            'cardiff' => 'Cardiff',
            'glasgow-city' => 'Glasgow City',
            'peterborough' => 'Cambridgeshire',
            'portsmouth' => 'Hampshire',
            'southend-on-sea' => 'Essex',
            'torbay' => 'Devon',
            'plymouth' => 'Devon',
            'milton-keynes' => 'Buckinghamshire',
            'luton' => 'Bedfordshire',
            'stoke-on-trent' => 'Staffordshire',
            'york' => 'North Yorkshire',
            'leicester' => 'Leicestershire',
            'nottingham' => 'Nottinghamshire',
            'reading' => 'Berkshire',
            'wokingham' => 'Berkshire',
            'bracknell-forest' => 'Berkshire',
            'slough' => 'Berkshire',
            'blackpool' => 'Lancashire',
            'halton' => 'Cheshire',
            'warrington' => 'Cheshire',
            'thurrock' => 'Essex',
            'south-gloucestershire' => 'South Gloucestershire',
            'west-bromwich' => 'West Midlands',
            'north-east-lincolnshire' => 'Lincolnshire',
            'north-lincolnshire' => 'Lincolnshire',
            'herefordshire' => 'Herefordshire',
            'rutland' => 'Rutland',
            'wiltshire' => 'Wiltshire',
            'bath-and-north-east-somerset' => 'Somerset',
            'bournemouth-christchurch-and-poole' => 'Dorset',
            'bournemouth' => 'Dorset',
            'poole' => 'Dorset',
            'cornwall' => 'Cornwall',
        ];
    }

    private function normalizeCountrySlug(string $country): string
    {
        $slug = Str::slug($country);

        if ($slug === '' || in_array($slug, ['uk', 'u-k', 'gb', 'great-britain', 'united-kingdom', 'england', 'scotland', 'wales', 'northern-ireland'], true)) {
            return 'united-kingdom';
        }

        return $slug;
    }

    private function normalizeCountySlug(string $county): ?string
    {
        $slug = Str::slug($county);

        if ($slug === '' || $this->isGenericCountySlug($slug)) {
            return null;
        }

        return $slug;
    }

    private function canonicalLocationPath(string $countrySlug, ?string $countySlug = null, ?string $townSlug = null): string
    {
        $countrySlug = $this->normalizeCountrySlug($countrySlug);
        $countySlug = $countySlug !== null ? trim((string) $countySlug, '/') : null;
        $townSlug = $townSlug !== null ? trim((string) $townSlug, '/') : null;

        if ($countySlug !== null && $this->isGenericCountySlug($countySlug)) {
            $countySlug = null;
        }

        if ($townSlug !== null && $townSlug !== '' && $townSlug === $countrySlug) {
            $townSlug = null;
        }

        if ($townSlug !== null && $countySlug !== null && $townSlug === $countySlug) {
            $countySlug = null;
        }

        if ($townSlug !== null && Str::slug($townSlug) === 'london') {
            $countySlug = null;
        }

        $segments = ['/locations', $countrySlug];

        if ($countySlug !== null && $countySlug !== '') {
            $segments[] = $countySlug;
        }

        if ($townSlug !== null && $townSlug !== '') {
            $segments[] = $townSlug;
        }

        return implode('/', $segments);
    }

    private function isGenericCountryLabel(string $label): bool
    {
        $slug = Str::slug($label);

        return in_array($slug, ['uk', 'u-k', 'gb', 'great-britain', 'united-kingdom', 'england', 'scotland', 'wales', 'northern-ireland'], true);
    }

    private function isGenericCountyLabel(string $label): bool
    {
        $slug = Str::slug($label);

        return in_array($slug, ['england', 'scotland', 'wales', 'northern-ireland', 'united-kingdom', 'uk', 'u-k', 'gb', 'great-britain'], true);
    }

    private function isGenericCountySlug(string $slug): bool
    {
        return in_array($slug, ['england', 'scotland', 'wales', 'northern-ireland', 'united-kingdom', 'uk', 'u-k', 'gb', 'great-britain'], true);
    }

    private function looksLikePostcode(string $value): bool
    {
        $value = trim($value);

        return (bool) preg_match('/\b[A-Z]{1,2}\d[\dA-Z]?\s*\d[A-Z]{2}\b/i', $value)
            || (bool) preg_match('/\b[A-Z]{1,2}\d[\dA-Z]?\b/i', $value);
    }

    private function looksLikeStreetLevelLabel(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }

        if (preg_match('/^\d+[A-Za-z]?(?:[-\/]\d+)?\s+.+$/', $value)) {
            return true;
        }

        if (preg_match('/\b(?:road|street|avenue|lane|drive|close|crescent|court|place|way|terrace|gardens|square|highway|boulevard|path|alley|row)\b\.?$/i', $value)) {
            return true;
        }

        if (preg_match('/\b(?:flat|apartment|suite|unit|building|block|house)\b\s*\d*/i', $value)) {
            return true;
        }

        return false;
    }

    private function countyColumn(): string
    {
        static $column = null;

        if ($column !== null) {
            return $column;
        }

        if (Schema::hasColumn('vendor_locations', 'county_region')) {
            return $column = 'county_region';
        }

        if (Schema::hasColumn('vendor_locations', 'county')) {
            return $column = 'county';
        }

        return $column = 'county';
    }
}
