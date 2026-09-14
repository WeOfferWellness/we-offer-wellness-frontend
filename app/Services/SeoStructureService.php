<?php

namespace App\Services;

use App\Models\OfferingV3;
use App\Models\Product;
use Illuminate\Support\Str;

class SeoStructureService
{
    private const CANONICAL_FORMATS = ['therapies', 'classes', 'events', 'workshops', 'retreats'];

    private const TYPE_DEFINITIONS = [
        'therapies' => [
            'singular' => 'therapy',
            'plural' => 'therapies',
            'page_label' => 'Therapies',
            'seo_label' => 'therapy sessions',
            'noun' => 'sessions',
            'entity_label' => 'practitioners',
            'cta_view' => 'View session',
            'cta_book' => 'Book session',
            'schema' => 'Service',
            'bookable' => true,
            'dated' => false,
            'use_event_schema' => false,
            'appear_in_filters' => true,
            'can_combine_locations' => true,
            'can_combine_categories' => true,
        ],
        'classes' => [
            'singular' => 'class',
            'plural' => 'classes',
            'page_label' => 'Classes',
            'seo_label' => 'classes',
            'noun' => 'classes',
            'entity_label' => 'instructors',
            'cta_view' => 'View class',
            'cta_book' => 'Book class',
            'schema' => 'Event',
            'bookable' => true,
            'dated' => true,
            'use_event_schema' => true,
            'appear_in_filters' => true,
            'can_combine_locations' => true,
            'can_combine_categories' => true,
        ],
        'events' => [
            'singular' => 'event',
            'plural' => 'events',
            'page_label' => 'Events',
            'seo_label' => 'events',
            'noun' => 'events',
            'entity_label' => 'facilitators',
            'cta_view' => 'View event',
            'cta_book' => 'Book event',
            'schema' => 'Event',
            'bookable' => true,
            'dated' => true,
            'use_event_schema' => true,
            'appear_in_filters' => true,
            'can_combine_locations' => true,
            'can_combine_categories' => true,
        ],
        'retreats' => [
            'singular' => 'retreat',
            'plural' => 'retreats',
            'page_label' => 'Retreats',
            'seo_label' => 'retreats',
            'noun' => 'retreats',
            'entity_label' => 'hosts',
            'cta_view' => 'View retreat',
            'cta_book' => 'Book retreat',
            'schema' => 'Event',
            'bookable' => true,
            'dated' => true,
            'use_event_schema' => true,
            'appear_in_filters' => true,
            'can_combine_locations' => true,
            'can_combine_categories' => true,
        ],
        'workshops' => [
            'singular' => 'workshop',
            'plural' => 'workshops',
            'page_label' => 'Workshops',
            'seo_label' => 'workshops',
            'noun' => 'workshops',
            'entity_label' => 'facilitators',
            'cta_view' => 'View workshop',
            'cta_book' => 'Book workshop',
            'schema' => 'Event',
            'bookable' => true,
            'dated' => true,
            'use_event_schema' => true,
            'appear_in_filters' => true,
            'can_combine_locations' => true,
            'can_combine_categories' => true,
        ],
        'courses' => [
            'singular' => 'course',
            'plural' => 'courses',
            'page_label' => 'Courses',
            'seo_label' => 'courses',
            'noun' => 'courses',
            'entity_label' => 'teachers',
            'cta_view' => 'View course',
            'cta_book' => 'Start course',
            'schema' => 'Course',
            'bookable' => true,
            'dated' => false,
            'use_event_schema' => false,
            'appear_in_filters' => true,
            'can_combine_locations' => true,
            'can_combine_categories' => true,
        ],
        'readings' => [
            'singular' => 'reading',
            'plural' => 'readings',
            'page_label' => 'Readings',
            'seo_label' => 'readings',
            'noun' => 'readings',
            'entity_label' => 'readers',
            'cta_view' => 'View reading',
            'cta_book' => 'Book reading',
            'schema' => 'Service',
            'bookable' => true,
            'dated' => false,
            'use_event_schema' => false,
            'appear_in_filters' => true,
            'can_combine_locations' => true,
            'can_combine_categories' => true,
        ],
        'gifts' => [
            'singular' => 'gift',
            'plural' => 'gifts',
            'page_label' => 'Gifts',
            'seo_label' => 'gifts',
            'noun' => 'gifts',
            'entity_label' => 'providers',
            'cta_view' => 'View gift',
            'cta_book' => 'Buy gift',
            'schema' => 'Product',
            'bookable' => true,
            'dated' => false,
            'use_event_schema' => false,
            'appear_in_filters' => true,
            'can_combine_locations' => false,
            'can_combine_categories' => false,
        ],
    ];

    private const CATEGORY_LABEL_OVERRIDES = [
        'reflexology-and-reiki' => 'Reflexology & Reiki',
        'yoga-and-meditation' => 'Yoga & Meditation',
        'sound-healing-and-meditation' => 'Sound Healing & Meditation',
        'reiki-and-breathwork' => 'Reiki & Breathwork',
        'birth-chart-reading-and-tarot' => 'Birth Chart Reading & Tarot',
    ];

    private const CATEGORY_NOUN_OVERRIDES = [
        'reiki' => [
            'therapies' => 'sessions',
            'classes' => 'classes',
            'events' => 'events',
            'workshops' => 'workshops',
            'retreats' => 'retreats',
        ],
        'yoga' => [
            'therapies' => 'therapy sessions',
            'classes' => 'classes',
            'events' => 'events',
            'workshops' => 'workshops',
            'retreats' => 'retreats',
        ],
        'sound-healing' => [
            'therapies' => 'sessions',
            'classes' => 'classes',
            'events' => 'events',
            'workshops' => 'workshops',
            'retreats' => 'retreats',
        ],
        'breathwork' => [
            'therapies' => 'sessions',
            'classes' => 'classes',
            'events' => 'events',
            'workshops' => 'workshops',
            'retreats' => 'retreats',
        ],
        'reflexology' => [
            'therapies' => 'treatments',
            'classes' => 'classes',
            'events' => 'events',
            'workshops' => 'workshops',
        ],
        'meditation' => [
            'therapies' => 'sessions',
            'classes' => 'classes',
            'events' => 'events',
            'workshops' => 'workshops',
            'retreats' => 'retreats',
        ],
        'crystal-healing' => [
            'therapies' => 'sessions',
            'classes' => 'classes',
            'events' => 'events',
            'workshops' => 'workshops',
            'retreats' => 'retreats',
        ],
        'massage' => [
            'therapies' => 'treatments',
            'classes' => 'classes',
        ],
        'acupuncture' => [
            'therapies' => 'treatments',
        ],
        'birth-chart-reading' => [
            'readings' => 'readings',
            'events' => 'events',
            'workshops' => 'workshops',
        ],
    ];

    private const CATEGORY_ENTITY_OVERRIDES = [
        'reiki' => [
            'therapies' => 'practitioners',
            'classes' => 'instructors',
            'events' => 'facilitators',
            'workshops' => 'facilitators',
        ],
        'yoga' => [
            'therapies' => 'practitioners',
            'classes' => 'instructors',
            'events' => 'facilitators',
            'retreats' => 'hosts',
        ],
        'sound-healing' => [
            'therapies' => 'practitioners',
            'classes' => 'instructors',
            'events' => 'facilitators',
            'workshops' => 'facilitators',
        ],
        'breathwork' => [
            'therapies' => 'practitioners',
            'classes' => 'instructors',
            'events' => 'facilitators',
            'workshops' => 'facilitators',
            'retreats' => 'hosts',
        ],
        'reflexology' => [
            'therapies' => 'practitioners',
            'classes' => 'instructors',
            'events' => 'facilitators',
            'workshops' => 'facilitators',
        ],
        'meditation' => [
            'therapies' => 'practitioners',
            'classes' => 'instructors',
            'events' => 'facilitators',
            'workshops' => 'facilitators',
            'retreats' => 'hosts',
        ],
        'birth-chart-reading' => [
            'readings' => 'readers',
            'events' => 'facilitators',
            'workshops' => 'facilitators',
        ],
        'massage' => [
            'therapies' => 'therapists',
            'classes' => 'instructors',
        ],
        'acupuncture' => [
            'therapies' => 'practitioners',
        ],
    ];

    public function typeDefinitions(): array
    {
        return self::TYPE_DEFINITIONS;
    }

    public function typeDefinition(string $type): array
    {
        $type = $this->canonicalTypeKey($type);

        return self::TYPE_DEFINITIONS[$type] ?? self::TYPE_DEFINITIONS['therapies'];
    }

    public function canonicalTypeKey(string $type): string
    {
        $type = strtolower(trim($type));
        $type = str_replace(['_', ' '], '-', $type);

        return match ($type) {
            'therapy', 'therapist', 'therapists' => 'therapies',
            'class', 'classes' => 'classes',
            'event', 'events' => 'events',
            'retreat', 'retreats' => 'retreats',
            'workshop', 'workshops' => 'workshops',
            'course', 'courses' => 'courses',
            'reading', 'readings' => 'readings',
            'gift', 'gifts', 'voucher', 'vouchers' => 'gifts',
            default => $type ?: 'therapies',
        };
    }

    public function canonicalFormatKey(string $format): string
    {
        $format = $this->canonicalTypeKey($format);

        return in_array($format, self::CANONICAL_FORMATS, true) ? $format : 'therapies';
    }

    public function formatPageUrl(string $format): string
    {
        return url('/'.$this->canonicalFormatKey($format));
    }

    public function modalityPageUrl(string $format, string $modality): string
    {
        return url('/'.$this->canonicalFormatKey($format).'/'.$this->categorySlug($modality));
    }

    public function modalityLocationPageUrl(string $format, string $modality, string $country, string $county, string $town): string
    {
        return url('/'.$this->canonicalFormatKey($format).'/'.$this->categorySlug($modality).'/'.implode('/', array_map(
            fn (string $segment): string => $this->locationSlugSegment($segment),
            [$country, $county, $town]
        )));
    }

    public function onlineModalityOfferingUrl(string $modality, string $offeringSlug): string
    {
        return url('/online/'.$this->categorySlug($modality).'/'.$this->slugify($offeringSlug));
    }

    public function modalityOfferingUrl(string $format, string $modality, string $offeringSlug): string
    {
        return url('/'.$this->canonicalFormatKey($format).'/'.$this->categorySlug($modality).'/'.$this->slugify($offeringSlug));
    }

    public function modalityOfferingLocationUrl(string $format, string $modality, string $country, string $county, string $town, string $offeringSlug): string
    {
        return url('/'.$this->canonicalFormatKey($format).'/'.$this->categorySlug($modality).'/'.implode('/', array_map(
            fn (string $segment): string => $this->locationSlugSegment($segment),
            [$country, $county, $town]
        )).'/'.$this->slugify($offeringSlug));
    }

    public function canonicalProductUrl(mixed $product): string
    {
        if ($product instanceof OfferingV3) {
            return $this->canonicalOfferingUrl($product);
        }

        $format = $this->inferFormatKeyFromProduct($product);
        $modality = $this->inferModalitySlugFromProduct($product);
        $slug = $this->offeringSlugFromProduct($product);
        if (preg_match('/^\d+(?:-|$)/', $slug) && (int) data_get($product, 'id') > 0) {
            $slug = 'product-'.(int) data_get($product, 'id').'-'.$slug;
        }

        // Product and offering detail canonicals always use the public
        // format/modality path. /online/... remains a legacy redirect source.
        return $this->modalityOfferingUrl($format, $modality, $slug);
    }

    public function canonicalOfferingUrl(OfferingV3 $offering): string
    {
        $format = $this->inferFormatKeyFromOffering($offering);
        $modality = $this->inferModalitySlugFromOffering($offering);
        $slug = $this->offeringSlugFromOffering($offering);
        if (preg_match('/^\d+(?:-|$)/', $slug)) {
            $slug = 'offering-'.$offering->id.'-'.$slug;
        }

        if ($format === 'events' && $modality === 'events') {
            return url('/events/'.$slug);
        }

        return $this->modalityOfferingUrl($format, $modality, $slug);
    }

    public function guidesIndexUrl(): string
    {
        return url('/guides');
    }

    public function formatGuidesUrl(string $format): string
    {
        return url('/'.$this->canonicalFormatKey($format).'/guides');
    }

    public function modalityGuidesUrl(string $format, string $modality): string
    {
        return url('/'.$this->canonicalFormatKey($format).'/'.$this->categorySlug($modality).'/guides');
    }

    public function guideUrl(string $format, string $modality, string $guideSlug): string
    {
        return url('/'.$this->canonicalFormatKey($format).'/'.$this->categorySlug($modality).'/guides/'.$this->slugify($guideSlug));
    }

    public function typePageCopy(string $type): array
    {
        $definition = $this->typeDefinition($type);
        $title = $definition['page_label'];
        $entity = $definition['entity_label'];
        $seoLabel = $definition['seo_label'];

        return [
            'kicker' => Str::headline($definition['page_label']),
            'title' => $title,
            'description' => 'Explore '.$seoLabel.' from trusted '.$entity.'. Browse live online and in-person options across We Offer Wellness.',
            'intro' => 'Browse live '.$seoLabel.' from trusted '.$entity.'. Use the filters to find the right option by location, format or focus.',
            'points' => [
                'Live listings first',
                'Online and in-person options',
                'Trusted providers and clear discovery',
            ],
            'primary_cta' => ['label' => 'Browse '.$definition['plural'], 'href' => '#landing-products'],
            'secondary_cta' => ['label' => 'Search all results', 'href' => '/search?type='.$definition['plural']],
            'schema' => $definition['schema'],
            'entity_label' => $entity,
            'noun' => $definition['noun'],
        ];
    }

    public function categoryLabel(string $category): string
    {
        $slug = $this->categorySlug($category);

        if ($slug === '') {
            return '';
        }

        if (isset(self::CATEGORY_LABEL_OVERRIDES[$slug])) {
            return self::CATEGORY_LABEL_OVERRIDES[$slug];
        }

        $label = Str::headline(str_replace(['_', '+'], '-', $category));
        $label = preg_replace('/\bAnd\b/', '&', (string) $label) ?: (string) $label;
        $label = preg_replace('/\s+&\s+/', ' & ', (string) $label) ?: (string) $label;

        return trim((string) $label);
    }

    public function categorySlug(string $category): string
    {
        return Str::slug(str_replace(['_', '+'], '-', trim($category)));
    }

    public function slugify(string $value): string
    {
        $value = Str::slug(trim($value));

        return $value !== '' ? $value : 'item';
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<int, string>
     */
    public function keywordsForPage(array $context = []): array
    {
        $keywords = [];

        $push = function (string $value) use (&$keywords): void {
            $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');
            if ($value === '') {
                return;
            }

            $key = mb_strtolower($value);
            $keywords[$key] = $value;
        };

        foreach ([
            'We Offer Wellness',
            'WOW',
            'wellness marketplace',
            'holistic wellness',
            'book wellness experiences',
            'online and in person',
        ] as $seed) {
            $push($seed);
        }

        $type = $this->canonicalFormatKey((string) data_get($context, 'type', ''));
        if ($type !== '') {
            $definition = $this->typeDefinition($type);
            foreach ([
                (string) ($definition['page_label'] ?? ucfirst($type)),
                (string) ($definition['singular'] ?? $type),
                (string) ($definition['plural'] ?? $type),
                (string) ($definition['seo_label'] ?? ''),
                'book '.((string) ($definition['page_label'] ?? ucfirst($type))),
            ] as $seed) {
                $push($seed);
            }
        }

        $categorySeeds = [];
        foreach ([
            data_get($context, 'category'),
            data_get($context, 'landing.category'),
        ] as $candidate) {
            if (is_string($candidate)) {
                $categorySeeds[] = $candidate;

                continue;
            }

            $label = trim((string) data_get($candidate, 'name', data_get($candidate, 'title', '')));
            if ($label !== '') {
                $categorySeeds[] = $label;
            }
        }

        foreach ((array) data_get($context, 'categories', []) as $candidate) {
            $label = trim((string) data_get($candidate, 'name', data_get($candidate, 'title', '')));
            if ($label !== '') {
                $categorySeeds[] = $label;
            }
            $slug = trim((string) data_get($candidate, 'slug', ''));
            if ($slug !== '') {
                $categorySeeds[] = Str::headline(str_replace('-', ' ', $slug));
            }
        }

        foreach ((array) data_get($context, 'products', []) as $item) {
            $label = trim((string) data_get($item, 'category.name', ''));
            if ($label !== '') {
                $categorySeeds[] = $label;
            }
        }

        foreach ((array) data_get($context, 'offeringResults.items', []) as $item) {
            $label = trim((string) data_get($item, 'category.name', ''));
            if ($label !== '') {
                $categorySeeds[] = $label;
            }
        }

        foreach (array_values(array_unique($categorySeeds)) as $seed) {
            $push($seed);
        }

        $locationSeeds = [];
        foreach ([
            data_get($context, 'location'),
            data_get($context, 'locationQuery'),
            data_get($context, 'locationSearch.label'),
            data_get($context, 'locationSearch.place'),
            data_get($context, 'city'),
            data_get($context, 'county'),
            data_get($context, 'town'),
        ] as $candidate) {
            $label = $this->locationSeedLabel($candidate);
            if ($label !== '') {
                $locationSeeds[] = $label;
            }
        }

        foreach (array_values(array_unique($locationSeeds)) as $seed) {
            $push($seed);
        }

        foreach ([
            'therapies',
            'classes',
            'events',
            'workshops',
            'retreats',
            'reiki',
            'massage',
            'sound healing',
            'breathwork',
            'meditation',
            'yoga',
            'wellbeing workshops',
            'corporate wellness',
            'gift vouchers',
        ] as $seed) {
            $push($seed);
        }

        return array_values($keywords);
    }

    public function shortOgTitle(string $title, int $maxLength = 35): string
    {
        $title = trim(preg_replace('/\s+/', ' ', $title) ?? '');
        if ($title === '') {
            return 'WOW®';
        }

        $title = preg_replace('/\s*[\|\-–—]\s*We Offer Wellness®?$/u', '', $title) ?: $title;
        $title = preg_replace('/\s*[\|\-–—]\s*WOW®$/u', '', $title) ?: $title;
        $title = trim($title);

        if (mb_strlen($title) <= $maxLength) {
            return $title;
        }

        $suffix = ' | WOW®';
        $limit = max(1, $maxLength - mb_strlen($suffix));

        return rtrim((string) Str::limit($title, $limit, '')).$suffix;
    }

    public function shortOgDescription(string $description, int $maxLength = 62): string
    {
        $description = trim(preg_replace('/\s+/', ' ', $description) ?? '');
        if ($description === '') {
            return 'Trusted wellness experiences online and in person.';
        }

        return rtrim((string) Str::limit($description, $maxLength, ''));
    }

    public function canonicalUrl(?string $path = null): string
    {
        $base = rtrim((string) config('services.public_site_url', 'https://www.weofferwellness.co.uk'), '/');
        if ($base === '') {
            $base = 'https://www.weofferwellness.co.uk';
        }

        if ($path === null || trim($path) === '') {
            return $base.'/';
        }

        $path = trim($path);
        if (preg_match('~^https?://~i', $path) === 1) {
            $path = (string) parse_url($path, PHP_URL_PATH);
            $path = $path !== '' ? $path : '/';
        }

        if (! str_starts_with($path, '/')) {
            $path = '/'.ltrim($path, '/');
        }

        return $base.rtrim($path, '/') ?: '/';
    }

    public function categoryNoun(string $type, string $category): string
    {
        $type = $this->canonicalTypeKey($type);
        $slug = $this->categorySlug($category);

        return self::CATEGORY_NOUN_OVERRIDES[$slug][$type]
            ?? $this->typeDefinition($type)['noun']
            ?? 'sessions';
    }

    public function categoryEntityLabel(string $type, string $category): string
    {
        $type = $this->canonicalTypeKey($type);
        $slug = $this->categorySlug($category);

        return self::CATEGORY_ENTITY_OVERRIDES[$slug][$type]
            ?? ($this->typeDefinition($type)['entity_label'] ?? 'practitioners');
    }

    public function combinationCopy(string $type, string $category, ?string $location = null): array
    {
        $type = $this->canonicalTypeKey($type);
        $categoryLabel = $this->categoryLabel($category);
        $noun = $this->categoryNoun($type, $category);
        $entity = $this->categoryEntityLabel($type, $category);
        $locationLabel = $location !== null && trim($location) !== '' ? $this->locationLabel($location) : null;

        $baseTitle = trim($categoryLabel.' '.$noun);
        $title = $locationLabel ? $baseTitle.' in '.$locationLabel : $baseTitle;

        return [
            'title' => $title,
            'h1' => $title,
            'meta_title' => $locationLabel
                ? $title.' | Find Trusted '.$categoryLabel.' '.Str::headline($entity)
                : $title.' | Find Trusted '.$categoryLabel.' '.Str::headline($entity),
            'description' => $locationLabel
                ? 'Find '.$baseTitle.' in '.$locationLabel.' with trusted '.$entity.'. Browse online and in-person options, prices and availability.'
                : 'Explore '.$baseTitle.' with trusted '.$entity.'. Browse online and in-person options, prices and availability.',
            'intro' => $locationLabel
                ? 'Browse live '.$baseTitle.' in '.$locationLabel.'. Compare online and in-person options from trusted '.$entity.'.'
                : 'Browse live '.$baseTitle.'. Compare online and in-person options from trusted '.$entity.'.',
            'kicker' => Str::headline($this->typeDefinition($type)['page_label'] ?? ucfirst($type)),
            'entity_label' => $entity,
            'noun' => $noun,
            'type' => $type,
            'category' => $categoryLabel,
            'location' => $locationLabel,
        ];
    }

    public function locationLabel(string $location): string
    {
        $location = trim(str_replace(['_', '+'], '-', $location));
        $location = str_replace('-', ' ', $location);

        return Str::headline($location);
    }

    /**
     * Normalize mixed location inputs into a safe label string.
     */
    private function locationSeedLabel(mixed $candidate): string
    {
        if (is_string($candidate) || is_int($candidate) || is_float($candidate) || is_bool($candidate)) {
            return trim((string) $candidate);
        }

        if (is_object($candidate)) {
            $candidate = (array) $candidate;
        }

        if (! is_array($candidate)) {
            return '';
        }

        foreach (['label', 'name', 'title', 'place', 'formatted_address', 'address', 'city', 'county', 'town', 'value'] as $key) {
            $value = data_get($candidate, $key);
            if (is_string($value) || is_int($value) || is_float($value) || is_bool($value)) {
                $label = trim((string) $value);
                if ($label !== '') {
                    return $label;
                }
            }
        }

        foreach ($candidate as $value) {
            $label = $this->locationSeedLabel($value);
            if ($label !== '') {
                return $label;
            }
        }

        return '';
    }

    public function inferTypeKeyFromText(string $value): string
    {
        $value = strtolower(trim($value));

        if ($value === '') {
            return 'therapies';
        }

        if (str_contains($value, 'gift') || str_contains($value, 'voucher') || str_contains($value, 'card')) {
            return 'gifts';
        }

        if (str_contains($value, 'course')) {
            return 'courses';
        }

        if (str_contains($value, 'reading')) {
            return 'readings';
        }

        if (str_contains($value, 'retreat')) {
            return 'retreats';
        }

        if (str_contains($value, 'workshop')) {
            return 'workshops';
        }

        if (str_contains($value, 'event')) {
            return 'events';
        }

        if (str_contains($value, 'class')) {
            return 'classes';
        }

        return 'therapies';
    }

    public function inferTypeKeyFromProduct(mixed $product): string
    {
        return $this->inferTypeKeyFromText(implode(' ', array_filter([
            (string) data_get($product, 'product_type', ''),
            (string) data_get($product, 'type.name', ''),
            (string) data_get($product, 'type.slug', ''),
            (string) data_get($product, 'type_label', ''),
            (string) data_get($product, 'tags_list', ''),
            (string) data_get($product, 'category.name', ''),
            (string) data_get($product, 'meta_json.therapy_slug', ''),
        ])));
    }

    public function inferTypeKeyFromOffering(OfferingV3 $offering): string
    {
        return $this->inferTypeKeyFromText(implode(' ', array_filter([
            (string) optional($offering->type)->name,
            (string) optional($offering->category)->name,
            (string) ($offering->title ?? ''),
            (string) ($offering->summary ?? ''),
        ])));
    }

    public function inferFormatKeyFromProduct(mixed $product): string
    {
        return $this->canonicalFormatKey($this->inferTypeKeyFromProduct($product));
    }

    public function inferFormatKeyFromOffering(OfferingV3 $offering): string
    {
        return $this->canonicalFormatKey($this->inferTypeKeyFromOffering($offering));
    }

    public function inferModalitySlugFromProduct(mixed $product): string
    {
        $category = trim((string) data_get($product, 'category.slug', ''));
        if ($category !== '') {
            return $this->categorySlug($category);
        }

        $categoryName = trim((string) data_get($product, 'category.name', ''));
        if ($categoryName !== '') {
            return $this->categorySlug($categoryName);
        }

        $therapySlug = trim((string) data_get($product, 'meta_json.therapy_slug', ''));
        if ($therapySlug !== '') {
            return $this->categorySlug($therapySlug);
        }

        return $this->categorySlug((string) (data_get($product, 'handle') ?: data_get($product, 'title') ?: data_get($product, 'id')));
    }

    public function inferModalitySlugFromOffering(OfferingV3 $offering): string
    {
        $category = trim((string) optional($offering->category)->slug);
        if ($category !== '') {
            return $this->categorySlug($category);
        }

        $categoryName = trim((string) optional($offering->category)->name);
        if ($categoryName !== '') {
            return $this->categorySlug($categoryName);
        }

        return $this->categorySlug((string) ($offering->slug ?: $offering->title ?: $offering->id));
    }

    public function offeringSlugFromProduct(mixed $product): string
    {
        return $this->slugify((string) (data_get($product, 'slug') ?: data_get($product, 'handle') ?: data_get($product, 'title') ?: data_get($product, 'id')));
    }

    public function offeringSlugFromOffering(OfferingV3 $offering): string
    {
        return $this->slugify((string) ($offering->slug ?: $offering->title ?: $offering->id));
    }

    public function isOnlineOnlyProduct(mixed $product): bool
    {
        $locations = method_exists($product, 'getLocations') ? (array) $product->getLocations() : (array) data_get($product, 'locations', []);
        if ($locations === []) {
            return false;
        }

        $hasOnline = in_array('Online', $locations, true);
        $physical = array_values(array_filter($locations, fn ($location): bool => strtolower(trim((string) $location)) !== 'online' && trim((string) $location) !== ''));

        return $hasOnline && $physical === [];
    }

    public function isOnlineOnlyOffering(OfferingV3 $offering): bool
    {
        $locations = method_exists($offering, 'getLocations') ? (array) $offering->getLocations() : [];
        if ($locations === []) {
            return false;
        }

        $hasOnline = in_array('Online', $locations, true);
        $physical = array_values(array_filter($locations, fn ($location): bool => strtolower(trim((string) $location)) !== 'online' && trim((string) $location) !== ''));

        return $hasOnline && $physical === [];
    }

    private function locationSlugSegment(string $segment): string
    {
        return $this->categorySlug($segment);
    }
}
