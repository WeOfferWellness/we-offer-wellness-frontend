<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'shopify_id',
        'title',
        'summary',
        'handle',
        'description',
        'body_html',
        'what_to_expect',  // Added field
        'included',        // Added field
        'product_type',
        'price',
        'vendor_id',
        'product_status_id',
        'category_id',
        'subcategory_id',
        'is_women_owned',  // Added field
        'is_lgbtq_friendly', // Added field
        'tags_list',
        'meta_json',
        'by_need',
    ];

    protected $casts = [
        'meta_json' => 'array',
        'by_need' => 'array',
    ];


    // Relationships

    /**
     * The vendor who owns the product.
     */
    public function vendor()
    {
        return $this->belongsTo(VendorDetail::class, 'vendor_id');
    }

    /**
     * Media associated with the product.
     */
    public function product_media()
    {
        return $this->hasMany(ProductMedia::class, 'product_id');
    }

    /**
     * The status of the product.
     */
    public function status()
    {
        return $this->belongsTo(ProductStatus::class, 'product_status_id');
    }

    /**
     * Options associated with the product.
     */
    public function options()
    {
        return $this->hasMany(ProductOption::class);
    }

    /**
     * Variants associated with the product.
     */
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Reviews associated with the product.
     */
    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }

    /**
     * Inventory details associated with the product.
     */
    public function inventory()
    {
        return $this->hasOne(ProductInventory::class);
    }

    /**
     * The category the product belongs to.
     */
    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function subcategory()
    {
        return $this->belongsTo(ProductSubcategory::class, 'subcategory_id');
    }

    /**
     * Media files associated with the product.
     */
    public function media()
    {
        return $this->hasMany(ProductMedia::class)->orderBy('order')->orderBy('id');
    }

    // Accessors and Utility Methods

    /**
     * Get a summary of the product description.
     *
     * @return string
     */
    public function getSummaryAttribute(?string $value): ?string
    {
        if (filled($value)) {
            return $value;
        }

        $source = $this->description ?? $this->body_html ?? '';
        $decoded = html_entity_decode($source, ENT_QUOTES | ENT_HTML5);
        $cleanedText = trim(preg_replace('/\s+/', ' ', strip_tags($decoded) ?? ''));

        if ($cleanedText === '') {
            return null;
        }

        return Str::limit(Str::words($cleanedText, 30, ''), 150, '...');
    }

    /**
     * Get all option names and values for the product.
     *
     * @return array
     */
    public function getOptionNamesAndValues()
    {
        return $this->options->mapWithKeys(function ($option) {
            return [
                $option->name => $option->values->pluck('value')->toArray(),
            ];
        })->toArray();
    }

    /**
     * Get locations formatted display.
     *
     * @return string
     */
    public function getFormattedLocations()
    {
        $locations = $this->getLocations();
        $isOnline = in_array('Online', $locations, true);
        $physicalLocations = array_diff($locations, ['Online']);
        $physicalCount = count($physicalLocations);

        if ($isOnline && $physicalCount === 0) {
            return 'Available only Online';
        } elseif ($isOnline && $physicalCount > 0) {
            return 'Available in ' . $physicalCount . ' locations & Online';
        } elseif ($physicalCount > 0) {
            return 'Available in ' . $physicalCount . ' locations';
        }

        return 'No locations available';
    }

    /**
     * Fetch all available locations for the product.
     *
     * @return array
     */
    public function getLocations()
    {
        try {
            if (Schema::hasTable('vendor_locations')) {
                $select = array_values(array_filter([
                    'label',
                    'city',
                    'county',
                    'country',
                    'formatted_address',
                    'line1',
                    'line2',
                    'street_address',
                    'address_line2',
                    'postcode',
                ]));

                $locationQuery = DB::table('vendor_locations')->orderBy('id');
                $rows = collect();
                if ($this->getKey() !== null && Schema::hasColumn('vendor_locations', 'product_id')) {
                    $rows = (clone $locationQuery)
                        ->where('product_id', $this->getKey())
                        ->get($select);
                }
                if ($rows->isEmpty() && $this->vendor_id) {
                    $rows = (clone $locationQuery)
                        ->where('vendor_id', $this->vendor_id)
                        ->get($select);
                }

                $normalizeCountryShort = static function (string $country): string {
                    $country = trim($country);
                    if ($country === '') {
                        return 'UK';
                    }

                    $slug = Str::slug($country);
                    if (in_array($slug, ['uk', 'u-k', 'gb', 'great-britain', 'united-kingdom', 'england', 'scotland', 'wales', 'northern-ireland'], true)) {
                        return 'UK';
                    }

                    return Str::of($country)->headline()->toString();
                };

                $extractSegments = static function (string ...$sources): array {
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

                            if (Str::contains(Str::lower($part), 'online')) {
                                continue;
                            }

                            if (preg_match('/\b[A-Z]{1,2}\d[\dA-Z]?\s*\d[A-Z]{2}\b/i', $part) || preg_match('/\b[A-Z]{1,2}\d[\dA-Z]?\b/i', $part)) {
                                continue;
                            }

                            if (preg_match('/^\d+[A-Za-z]?(?:[-\/]\d+)?\s+.+$/', $part)) {
                                continue;
                            }

                            if (preg_match('/\b(?:road|street|avenue|lane|drive|close|crescent|court|place|way|terrace|gardens|square|highway|boulevard|path|alley|row)\b\.?$/i', $part)) {
                                continue;
                            }

                            $segments[] = $part;
                        }
                    }

                    return array_values(array_unique($segments));
                };

                $locations = [];
                foreach ($rows as $row) {
                    $rowOnline = data_get($row, 'online', data_get($row, 'is_online', null));
                    if (filter_var($rowOnline, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE)) {
                        $locations[] = 'Online';
                        continue;
                    }

                    $label = trim((string) ($row->label ?? ''));
                    if ($label !== '' && Str::contains(Str::lower($label), 'online')) {
                        $locations[] = 'Online';
                        continue;
                    }

                    $city = trim((string) ($row->city ?? ''));
                    $county = trim((string) ($row->county ?? ''));
                    $country = $normalizeCountryShort((string) ($row->country ?? ''));
                    $segments = $extractSegments(
                        (string) ($row->label ?? ''),
                        (string) ($row->formatted_address ?? ''),
                        (string) ($row->line1 ?? ''),
                        (string) ($row->line2 ?? ''),
                        (string) ($row->street_address ?? ''),
                        (string) ($row->address_line2 ?? ''),
                        $city,
                        $county
                    );

                    if ($city === '' && isset($segments[0])) {
                        $city = trim((string) $segments[0]);
                    }
                    if ($county === '' && isset($segments[1])) {
                        $county = trim((string) $segments[1]);
                    }

                    $pieces = array_values(array_filter([
                        $city !== '' ? $city : null,
                        $county !== '' && Str::lower($county) !== Str::lower($city) ? $county : null,
                        $country !== '' ? $country : null,
                    ]));

                    $locationLabel = trim(implode(', ', $pieces));
                    if ($locationLabel !== '') {
                        $locations[] = $locationLabel;
                        continue;
                    }

                    if ($label !== '') {
                        $locations[] = $label;
                    }
                }

                $locations = array_values(array_unique(array_filter($locations)));
                if ($locations !== []) {
                    return $locations;
                }
            }
        } catch (\Throwable $e) {
            // Fall back to option values below.
        }

        $locationsOption = $this->relationLoaded('options')
            ? $this->options->firstWhere('meta_name', 'locations')
            : $this->options()->where('meta_name', 'locations')->with('values')->first();

        if (!$locationsOption) {
            return [];
        }

        return $locationsOption->values
            ->pluck('value')
            ->filter()
            ->map(function ($value) {
                $text = is_string($value) ? trim($value) : (string) $value;
                if ($text === '') {
                    return $text;
                }

                if (Str::contains(Str::lower($text), 'online')) {
                    return 'Online';
                }

                return $text;
            })
            ->values()
            ->toArray();
    }

    /**
     * Get the first image URL of the product.
     *
     * @return string
     */
    public function getFirstImageUrl()
    {
        $firstImage = $this->relationLoaded('media')
            ? $this->media->sortBy(function ($item) {
                return sprintf('%010d-%010d', (int) ($item->order ?? 0), (int) ($item->id ?? 0));
            })->first()
            : $this->media()->orderBy('order')->orderBy('id')->first();
        if (!$firstImage) {
            return asset('assets/img/no-product-image.jpg');
        }

        $path = (string) ($firstImage->media_url ?? '');
        if ($path === '') {
            return asset('assets/img/no-product-image.jpg');
        }

        // Build a URL from known sources first
        $url = null;
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            $url = $path; // absolute, will normalize host below
        } else {
            // Prefer serving from Backend's storage if configured
            $backend = rtrim((string) env('BACKEND_ASSET_URL', env('BACKEND_URL', '')), '/');
            $clean = ltrim($path, '/');
            if ($backend) {
                $url = $backend . '/storage/' . $clean;
            } else {
                // Fallback to local storage URL (requires public/storage symlink)
                $url = asset('storage/' . $clean);
            }
        }

        // Normalize all product image hosts to atease domain
        $atease = rtrim((string) env('ATEASE_BASE_URL', env('ALT_STORAGE_BASE', 'https://atease.weofferwellness.co.uk')), '/');
        try {
            if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
                $parts = parse_url($url) ?: [];
                $p = ($parts['path'] ?? '/') ?: '/';
                $q = isset($parts['query']) && $parts['query'] !== '' ? ('?'.$parts['query']) : '';
                return $atease . $p . $q;
            }
        } catch (\Throwable $e) {}

        // Relative or failed parse: ensure single leading slash
        return $atease . '/' . ltrim((string) $url, '/');
    }

    public function hasDisplayableImage(): bool
    {
        return $this->getFirstImageUrl() !== asset('assets/img/no-product-image.jpg');
    }

    /**
     * Map variants with their respective options.
     *
     * @return array
     */
    public function getMappedVariants()
    {
        return $this->variants->map(function ($variant) {
            $decodedOptionIds = json_decode($variant->option_ids, true) ?? [];
            $optionValues = collect($decodedOptionIds)->map(function ($optionId) {
                $value = $this->options->flatMap(fn ($option) => $option->values)
                    ->firstWhere('id', $optionId);
                return $value ? $value->value : 'N/A';
            });

            return [
                'option_values' => $optionValues,
                'price' => $variant->price,
                'sku' => $variant->sku,
                'inventory_quantity' => $variant->inventory_quantity,
                'option_ids' => $variant->option_ids,
            ];
        });
    }

    /**
     * Generate structured tabular data for display.
     *
     * @return array
     */
    public function getTabularData()
    {
        $options = $this->options()->with('values')->get();
        $optionValues = $options->mapWithKeys(function ($option) {
            return [$option->meta_name => $option->values->pluck('value')->toArray()];
        })->toArray();

        $combinations = $this->generateCombinations($optionValues);

        $variants = $this->variants->map(function ($variant) {
            return [
                'price' => $variant->price ?? 'NULL',
                'sku' => $variant->sku ?? 'NULL',
                'inventory' => $variant->inventory_quantity ?? 'NULL',
            ];
        })->toArray();

        $tabularData = [];
        foreach ($combinations as $combination) {
            $variant = array_shift($variants) ?? ['price' => 'NULL', 'sku' => 'NULL', 'inventory' => 'NULL'];
            $tabularData[] = array_merge($combination, $variant);
        }

        return $tabularData;
    }

    /**
     * Generate combinations of option values.
     *
     * @param array $optionValues
     * @return array
     */
    private function generateCombinations($optionValues)
    {
        if (empty($optionValues)) {
            return [];
        }

        $keys = array_keys($optionValues);
        $values = array_values($optionValues);

        $combinations = [[]];

        foreach ($values as $valueSet) {
            $newCombinations = [];
            foreach ($combinations as $combination) {
                foreach ($valueSet as $value) {
                    $newCombinations[] = array_merge($combination, [$value]);
                }
            }
            $combinations = $newCombinations;
        }

        return array_map(function ($combination) use ($keys) {
            return array_combine($keys, $combination);
        }, $combinations);
    }

    public function painpoints()
    {
        return $this->hasMany(ProductPainpoint::class);
    }

    /**
     * Accessor for tags as an array.
     *
     * @return array
     */
    public function getTagsArrayAttribute()
    {
        return explode(',', $this->tags_list);
    }

    /**
     * Mutator to set tags as a comma-separated string.
     *
     * @param array $value
     * @return void
     */
    public function setTagsArrayAttribute($value)
    {
        $this->attributes['tags_list'] = implode(',', array_filter($value));
    }

    public function sections()
    {
        return $this->belongsToMany(Section::class, 'section_product')
                    ->withPivot('sort_order');
    }

}
