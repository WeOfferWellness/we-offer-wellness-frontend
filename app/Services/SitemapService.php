<?php

namespace App\Services;

use App\Models\OfferingV3;
use App\Models\LegalDocument;
use App\Models\Platform;
use App\Models\PageRedirect;
use App\Models\Product;
use App\Models\User;
use App\Support\WowEventsFeed;
use App\Services\WhatCategoryCacheService;
use Carbon\Carbon;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SitemapService
{
    private const MAX_URLS_PER_FILE = 50000;

    private const SITEMAP_THROTTLE_EVERY = 100;

    private const SITEMAP_THROTTLE_USEC = 50000;

    private const CANONICAL_FORMATS = [
        'therapies',
        'classes',
        'events',
        'workshops',
        'retreats',
    ];

    private const GROUP_ORDER = [
        'static',
        'schedules',
        'types',
        'modalities',
        'near-me',
        'offerings',
        'locations',
        'online',
        'by-need',
        'practitioners',
        'guides',
    ];

    private const ALWAYS_EMIT_EMPTY_SEGMENTS = [
        'guides',
    ];

    private const NEED_SLUGS = [
        'stress-and-anxiety',
        'sleep-issues',
        'low-mood-burnout',
        'overwhelm',
        'worry',
        'pain-management',
        'mens-wellbeing',
        'digestive-health',
        'fertility-pregnancy',
        'nervous-system',
        'breathwork',
        'guided-meditation',
        'corporate-wellbeing',
    ];

    private const RESERVED_CATEGORY_SLUGS = [
        'about',
        'cart',
        'checkout',
        'contact',
        'cookies',
        'corporate',
        'corporate-wellbeing',
        'corporate-wellness',
        'dashboard',
        'event',
        'events',
        'experience',
        'experiences',
        'gift',
        'gift-cards',
        'gift-vouchers',
        'giftcards',
        'help',
        'locations',
        'mindful-times',
        'near-me',
        'online',
        'online-near-me',
        'offerings',
        'partners',
        'plan',
        'privacy',
        'providers',
        'refunds-and-cancellations',
        'reviews',
        'safety-and-contraindications',
        'search',
        'sitemap',
        'terms',
        'therapy',
        'therapies',
        'v3',
        'workshop',
        'workshops',
        'class',
        'classes',
        'retreat',
        'retreats',
        'needs',
    ];

    private const LEGACY_CITIES = [
        'london',
        'manchester',
        'birmingham',
        'leeds',
        'bristol',
        'brighton',
        'liverpool',
        'glasgow',
        'edinburgh',
        'cardiff',
        'kent',
    ];

    private const LEGACY_TYPES = [
        'therapies',
        'events',
        'workshops',
        'classes',
        'retreats',
        'gifts',
    ];

    private ?Collection $liveProducts = null;

    private ?Collection $liveOfferings = null;

    private ?array $locationCatalog = null;

    private ?array $locationPathIndex = null;

    private ?array $whatCategories = null;

    private ?array $eventsCache = null;

    private ?array $segmentFilesCache = null;

    private ?array $manifestEntriesCache = null;

    private ?array $submissionUrlsCache = null;

    private ?array $redirectPathMatchers = null;

    private ?array $redirectExactPaths = null;

    private ?array $redirectSourcePatterns = null;

    private ?array $sitemapRouteCache = null;

    private ?array $canonicalUrlsCache = null;

    private int $sitemapThrottleCounter = 0;

    private ?string $sitemapGeneratedAt = null;

    public function outputDirectory(): string
    {
        return public_path('sitemaps');
    }

    private function publicSiteBaseUrl(): string
    {
        $base = trim((string) config('services.public_site_url', 'https://www.weofferwellness.co.uk'));

        if ($base === '') {
            $base = 'https://www.weofferwellness.co.uk';
        }

        $scheme = parse_url($base, PHP_URL_SCHEME);
        if (!is_string($scheme) || $scheme === '') {
            $base = 'https://' . ltrim($base, '/');
        }

        return rtrim($base, '/');
    }

    private function publicUrl(string $path): string
    {
        $path = trim($path);
        if ($path === '' || $path === '/') {
            return $this->publicSiteBaseUrl() . '/';
        }

        return $this->publicSiteBaseUrl() . '/' . ltrim($path, '/');
    }

    public function manifestPath(): string
    {
        return $this->outputDirectory() . '/manifest.json';
    }

    public function buildAndWriteAll(?string $outputDirectory = null): array
    {
        $generatedAt = now()->toAtomString();
        $this->sitemapGeneratedAt = $generatedAt;

        $outputDirectory = $outputDirectory ?: $this->outputDirectory();
        File::ensureDirectoryExists($outputDirectory);
        File::ensureDirectoryExists(dirname($this->manifestPath()));
        File::ensureDirectoryExists(public_path('.well-known'));
        $this->atomicPut(public_path('sitemap.xsl'), $this->renderSitemapStylesheet());

        $files = $this->buildSitemapFiles();

        $expectedFilenames = array_values(array_unique(array_map(
            static fn (array $file): string => (string) ($file['filename'] ?? ''),
            $files
        )));

        foreach ($files as $file) {
            $this->atomicPut($outputDirectory . '/' . $file['filename'], (string) $file['xml']);
        }

        // Remove obsolete child files only after every newly generated file has
        // been written successfully, so a failed run leaves a valid previous
        // sitemap available.
        foreach (File::files($outputDirectory) as $existingFile) {
            if (strtolower((string) $existingFile->getExtension()) === 'xml'
                && ! in_array($existingFile->getFilename(), $expectedFilenames, true)) {
                File::delete($existingFile->getPathname());
            }
        }

        $indexXml = $this->buildIndexXml();
        $this->atomicPut(public_path('sitemap.xml'), $indexXml);
        $this->atomicPut(public_path('sitemap-index.xml'), $indexXml);

        $staticXml = $this->buildSegmentXml('static');
        if ($staticXml !== null) {
            $this->atomicPut(public_path('sitemap-pages.xml'), $staticXml);
        }

        $schedulesXml = $this->buildScheduleSitemapXml();
        if ($schedulesXml !== '') {
            $this->atomicPut(public_path('sitemap-schedules.xml'), $schedulesXml);
        }

        $aiFiles = $this->buildAiGuideFiles();
        foreach ($aiFiles as $file) {
            $this->atomicPut((string) ($file['path'] ?? public_path((string) $file['filename'])), (string) ($file['content'] ?? ''));
        }

        $manifest = [
            'generated_at' => $generatedAt,
            'index_url' => $this->publicUrl('/sitemap.xml'),
            'submission_urls' => $this->submissionUrlsFromFiles($files),
            'sitemaps' => $this->manifestEntries($generatedAt),
            'ai_files' => array_map(static function (array $file): array {
                return [
                    'name' => (string) ($file['filename'] ?? ''),
                    'url' => (string) ($file['url'] ?? ''),
                    'count' => (int) ($file['count'] ?? 0),
                    'bytes' => (int) ($file['bytes'] ?? 0),
                ];
            }, $aiFiles),
            'file_count' => count($files),
            'total_urls' => array_sum(array_map(
                static fn (array $file): int => (int) ($file['count'] ?? 0),
                $files
            )),
        ];

        $this->atomicPut(
            $this->manifestPath(),
            (string) json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        return [
            'files' => $files,
            'ai_files' => $aiFiles,
            'manifest' => $manifest,
            'index_xml' => $indexXml,
        ];
    }

    /**
     * @return array<int, array{filename:string,path:string,url:string,content:string,count:int,bytes:int}>
     */
    public function buildAiGuideFiles(): array
    {
        $files = [
            [
                'filename' => 'llms.txt',
                'path' => public_path('llms.txt'),
                'url' => $this->publicUrl('/llms.txt'),
                'content' => $this->renderAiGuideMain(),
            ],
            [
                'filename' => 'llms-small.txt',
                'path' => public_path('llms-small.txt'),
                'url' => $this->publicUrl('/llms-small.txt'),
                'content' => $this->renderAiGuideSmall(),
            ],
            [
                'filename' => 'llms-full.txt',
                'path' => public_path('llms-full.txt'),
                'url' => $this->publicUrl('/llms-full.txt'),
                'content' => $this->renderAiGuideFull(),
            ],
            [
                'filename' => 'ai.txt',
                'path' => public_path('.well-known/ai.txt'),
                'url' => $this->publicUrl('/.well-known/ai.txt'),
                'content' => $this->renderAiGuidePolicy(),
            ],
        ];

        return array_map(function (array $file): array {
            $content = (string) ($file['content'] ?? '');

            return [
                'filename' => (string) ($file['filename'] ?? ''),
                'path' => (string) ($file['path'] ?? ''),
                'url' => (string) ($file['url'] ?? ''),
                'content' => $content,
                'count' => 0,
                'bytes' => strlen($content),
            ];
        }, $files);
    }

    public function buildIndexXml(): string
    {
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>',
            '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];
        foreach ($this->manifestEntries($this->sitemapGeneratedAt) as $entry) {
            $lines[] = '  <sitemap>';
            $lines[] = '    <loc>' . $this->escapeXml((string) ($entry['url'] ?? '')) . '</loc>';
            $lines[] = '    <lastmod>' . $this->escapeXml((string) ($entry['lastmod'] ?? now()->toAtomString())) . '</lastmod>';
            $lines[] = '  </sitemap>';
        }
        $lines[] = '</sitemapindex>';

        return implode("\n", $lines);
    }

    public function buildSegmentXml(string $segment): ?string
    {
        if ($segment === 'schedules') {
            $xml = $this->buildScheduleSitemapXml();
            return $xml !== '' ? $xml : null;
        }

        $file = collect($this->buildSitemapFiles())
            ->first(fn (array $candidate): bool => (string) ($candidate['segment'] ?? '') === $segment || (string) ($candidate['filename'] ?? '') === $segment . '.xml');

        if ($file === null) {
            return null;
        }

        return (string) ($file['xml'] ?? '');
    }

    public function buildScheduleSitemapXml(): string
    {
        return $this->renderUrlsetXml($this->normalizeEntries($this->buildScheduleEntries()));
    }

    public function submissionUrls(): array
    {
        if ($this->submissionUrlsCache !== null) {
            return $this->submissionUrlsCache;
        }

        if (File::isFile($this->manifestPath())) {
            try {
                $decoded = json_decode((string) File::get($this->manifestPath()), true);
                $urls = array_values(array_filter(array_map(
                    static fn ($url): string => trim((string) $url),
                    (array) data_get($decoded, 'submission_urls', [])
                )));

                if ($urls !== []) {
                    return $this->submissionUrlsCache = array_values(array_unique($urls));
                }
            } catch (\Throwable $e) {
                // Fall back to config-defined URLs below.
            }
        }

        $raw = trim((string) config('services.search_console.sitemap_urls', ''));
        if ($raw === '') {
            $fallback = trim((string) config('services.search_console.sitemap_url', ''));
            $raw = $fallback;
        }

        if ($raw === '') {
            return $this->submissionUrlsCache = [];
        }

        return $this->submissionUrlsCache = collect(explode(',', $raw))
            ->map(fn (string $url): string => trim($url))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{filename:string,segment:string,url:string,lastmod:string,count:int,xml:string}>
     */
    public function buildSitemapFiles(): array
    {
        if ($this->segmentFilesCache !== null) {
            return $this->segmentFilesCache;
        }

        $files = [];
        $canonicalUrls = [];
        foreach ($this->buildSegmentGroups() as $segment => $entries) {
            $entries = $this->normalizeEntries($entries);
            if ($entries === [] && ! in_array($segment, self::ALWAYS_EMIT_EMPTY_SEGMENTS, true)) {
                continue;
            }

            foreach ($entries as $entry) {
                $loc = trim((string) ($entry['loc'] ?? ''));
                if ($loc === '') {
                    continue;
                }

                $canonicalUrls[$loc] = true;
            }

            $chunks = $entries === [] ? [[]] : array_chunk($entries, self::MAX_URLS_PER_FILE);
            foreach ($chunks as $index => $chunk) {
                $filename = $this->segmentFilename($segment, $index);
                $files[] = [
                    'filename' => $filename,
                    'segment' => pathinfo($filename, PATHINFO_FILENAME),
                    'url' => $this->publicUrl('/sitemaps/' . $filename),
                    'lastmod' => $this->chunkLastMod($chunk),
                    'count' => count($chunk),
                    'xml' => $this->renderUrlsetXml($chunk),
                ];
            }
        }

        if ($this->canonicalUrlsCache === null && $canonicalUrls !== []) {
            ksort($canonicalUrls);
            $this->canonicalUrlsCache = array_keys($canonicalUrls);
        }

        return $this->segmentFilesCache = $files;
    }

    /**
     * @return array<int, array{name:string,url:string,lastmod:string,count:int}>
     */
    public function manifestEntries(?string $lastmodOverride = null): array
    {
        if ($this->manifestEntriesCache !== null) {
            if ($lastmodOverride === null) {
                return $this->manifestEntriesCache;
            }

            return array_map(function (array $entry) use ($lastmodOverride): array {
                $entry['lastmod'] = $lastmodOverride;
                return $entry;
            }, $this->manifestEntriesCache);
        }

        $entries = [];

        foreach ($this->buildSitemapFiles() as $file) {
            $entries[] = [
                'name' => (string) $file['segment'],
                'url' => (string) $file['url'],
                'lastmod' => $lastmodOverride ?? (string) $file['lastmod'],
                'count' => (int) $file['count'],
            ];
        }

        return $this->manifestEntriesCache = $entries;
    }

    /**
     * @return array<int, string>
     */
    public function canonicalUrls(): array
    {
        if ($this->canonicalUrlsCache !== null) {
            return $this->canonicalUrlsCache;
        }

        $urls = [];

        foreach ($this->buildSegmentGroups() as $entries) {
            foreach ($this->normalizeEntries($entries) as $entry) {
                $loc = trim((string) ($entry['loc'] ?? ''));
                if ($loc === '') {
                    continue;
                }

                $urls[$loc] = true;
            }
        }

        ksort($urls);

        return $this->canonicalUrlsCache = array_keys($urls);
    }

    /**
     * @return array<string, array<int, array{loc:string,lastmod:string}>>
     */
    private function buildSegmentGroups(): array
    {
        return [
            'static' => $this->buildStaticEntries(),
            'schedules' => $this->buildScheduleEntries(),
            'types' => $this->buildTypeEntries(),
            'modalities' => $this->buildModalityEntries(),
            'near-me' => $this->buildNearMeEntries(),
            'offerings' => $this->buildOfferingEntries(),
            'locations' => $this->buildLocationEntries(),
            'online' => $this->buildOnlineEntries(),
            'by-need' => $this->buildByNeedEntries(),
            'practitioners' => $this->buildPractitionerEntries(),
            'guides' => $this->buildGuideEntries(),
        ];
    }

    /**
     * @return array<int, array{loc:string,lastmod:string}>
     */
    private function buildStaticEntries(): array
    {
        $now = now()->toAtomString();
        $entries = [];

        foreach ([
            '/',
            '/about',
            '/contact',
            '/help',
            '/help/faq',
            '/help/gift-cards',
            '/giftcards',
            '/partners',
            '/plan',
            '/reviews',
            '/safety-and-contraindications',
            '/corporate',
            '/holistic-therapies-uk',
            '/corporate/wellbeing-workshops',
            '/corporate/meditation',
            '/corporate/breathwork',
            '/corporate/sound-bath',
            '/corporate/gift-vouchers',
            '/corporate/employee-rewards',
        ] as $path) {
            $this->addEntry($entries, $this->publicUrl($path), $now);
        }

        foreach ($this->buildLegalEntries() as $entry) {
            $loc = trim((string) ($entry['loc'] ?? ''));
            if ($loc === '') {
                continue;
            }

            $entries[$loc] = [
                'loc' => $loc,
                'lastmod' => (string) ($entry['lastmod'] ?? $now),
            ];
        }

        return array_values($entries);
    }

    /**
     * @return array<int, array{loc:string,lastmod:string}>
     */
    private function buildScheduleEntries(): array
    {
        $entries = [];
        $now = now()->toAtomString();

        foreach ([
            '/schedule-discovery',
            '/wellness-events/this-week',
            '/wellness-events/this-weekend',
            '/wellness-events/today',
            '/wellness-events/tomorrow',
            '/wellness-events/next-week',
            '/wellness-events/online',
            '/wellness-events/this-week/kent',
            '/wellness-events/this-week/london',
            '/wellness-events/this-weekend/kent',
            '/wellness-events/this-weekend/london',
            '/sound-baths/this-week',
            '/sound-baths/this-weekend',
            '/meditation-events/this-week',
            '/meditation-events/this-weekend',
            '/breathwork-events/this-week',
            '/breathwork-events/this-weekend',
            '/yoga-workshops/this-week',
            '/yoga-workshops/this-weekend',
        ] as $path) {
            $this->addEntry($entries, $this->publicUrl($path), $now);
        }

        return array_values($entries);
    }

    /**
     * @return array<int, array{loc:string,lastmod:string}>
     */
    private function buildLegalEntries(): array
    {
        $platformId = (int) (Platform::query()->where('name', 'WOW Store')->value('id') ?? 1);

        $documents = LegalDocument::query()
            ->where('platform_id', $platformId)
            ->orderBy('title')
            ->orderBy('slug')
            ->get();

        if ($documents->isEmpty()) {
            return array_map(
                fn (string $path): array => [
                    'loc' => $this->publicUrl($path),
                    'lastmod' => now()->toAtomString(),
                ],
                [
                    '/privacy',
                    '/terms',
                    '/cookies',
                    '/refunds-and-cancellations',
                ]
            );
        }

        return $documents
            ->map(function (LegalDocument $document): array {
                $slug = trim((string) $document->slug, '/');
                if ($slug === '') {
                    return [];
                }

                return [
                    'loc' => $this->publicUrl('/' . $slug),
                    'lastmod' => $this->dateToAtom($document->updated_at ?? $document->effective_date ?? now()),
                ];
            })
            ->filter(fn (array $entry): bool => ! empty($entry['loc']))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{loc:string,lastmod:string}>
     */
    private function buildModalityEntries(): array
    {
        $entries = [];
        $latestByUrl = [];
        $seo = app(SeoStructureService::class);

        $rememberLatest = function (string $url, mixed $value) use (&$latestByUrl): void {
            $atom = $this->dateToAtom($value);

            if (!isset($latestByUrl[$url])) {
                $latestByUrl[$url] = $atom;
                return;
            }

            try {
                $current = Carbon::parse($latestByUrl[$url])->getTimestamp();
                $candidate = Carbon::parse($atom)->getTimestamp();

                if ($candidate > $current) {
                    $latestByUrl[$url] = $atom;
                }
            } catch (\Throwable $e) {
                $latestByUrl[$url] = $atom;
            }
        };

        foreach ($this->liveProducts()->filter(fn (Product $product): bool => $product->category !== null) as $product) {
            $format = $seo->inferFormatKeyFromProduct($product);
            $modality = $seo->inferModalitySlugFromProduct($product);
            if ($modality === '') {
                continue;
            }

            $url = $seo->modalityPageUrl($format, $modality);
            $rememberLatest($url, $product->updated_at ?? null);
        }

        foreach ($this->liveOfferings()->filter(fn (OfferingV3 $offering): bool => $offering->category !== null) as $offering) {
            $format = $seo->inferFormatKeyFromOffering($offering);
            $modality = $seo->inferModalitySlugFromOffering($offering);
            if ($modality === '') {
                continue;
            }

            $url = $seo->modalityPageUrl($format, $modality);
            $rememberLatest($url, $offering->updated_at ?? null);
        }

        foreach ($latestByUrl as $url => $lastmod) {
            $this->addEntry($entries, $url, $lastmod);
        }

        return array_values($entries);
    }

    /**
     * @return array<int, array{loc:string,lastmod:string}>
     */
    private function buildTypeEntries(): array
    {
        $entries = [];
        $latestByUrl = [];
        $seo = app(SeoStructureService::class);

        $rememberLatest = function (string $url, mixed $value) use (&$latestByUrl): void {
            $atom = $this->dateToAtom($value);

            if (!isset($latestByUrl[$url])) {
                $latestByUrl[$url] = $atom;
                return;
            }

            try {
                $current = Carbon::parse($latestByUrl[$url])->getTimestamp();
                $candidate = Carbon::parse($atom)->getTimestamp();

                if ($candidate > $current) {
                    $latestByUrl[$url] = $atom;
                }
            } catch (\Throwable $e) {
                $latestByUrl[$url] = $atom;
            }
        };

        foreach ($this->liveProducts()->filter(fn (Product $product): bool => $product->category !== null) as $product) {
            $format = $seo->inferFormatKeyFromProduct($product);
            $rememberLatest($seo->formatPageUrl($format), $product->updated_at ?? null);
        }

        foreach ($this->liveOfferings()->filter(fn (OfferingV3 $offering): bool => $offering->category !== null) as $offering) {
            $format = $seo->inferFormatKeyFromOffering($offering);
            $rememberLatest($seo->formatPageUrl($format), $offering->updated_at ?? null);
        }

        foreach ($latestByUrl as $url => $lastmod) {
            $this->addEntry($entries, $url, $lastmod);
        }

        return array_values($entries);
    }

    /**
     * @return array<int, array{loc:string,lastmod:string}>
     */
    private function buildLocationEntries(): array
    {
        $entries = [];
        $catalog = $this->locationCatalog();

        $this->addEntry($entries, $this->publicUrl('/locations'), now()->toAtomString());

        foreach ((array) data_get($catalog, 'countries', []) as $country) {
            if (!empty($country['online'])) {
                continue;
            }

            if ((int) data_get($country, 'counts.total', 0) > 0 && $this->validLocationPath($country['path'] ?? null)) {
                $this->addEntry($entries, $this->publicUrl((string) $country['path']), now()->toAtomString());
            }

            foreach ((array) data_get($country, 'counties', []) as $county) {
                if ((int) data_get($county, 'counts.total', 0) > 0 && $this->validLocationPath($county['path'] ?? null)) {
                    $this->addEntry($entries, $this->publicUrl((string) $county['path']), now()->toAtomString());
                }

                foreach ((array) data_get($county, 'towns', []) as $town) {
                    if ((int) data_get($town, 'counts.total', 0) > 0 && $this->validLocationPath($town['path'] ?? null)) {
                        $this->addEntry($entries, $this->publicUrl((string) $town['path']), now()->toAtomString());
                    }
                }
            }
        }

        return array_values($entries);
    }

    /**
     * @return array<int, array{loc:string,lastmod:string}>
     */
    private function buildNearMeEntries(): array
    {
        $entries = [];
        $latestByUrl = [];
        $seo = app(SeoStructureService::class);

        $rememberLatest = function (string $url, mixed $value) use (&$latestByUrl): void {
            $atom = $this->dateToAtom($value);

            if (!isset($latestByUrl[$url])) {
                $latestByUrl[$url] = $atom;
                return;
            }

            try {
                $current = Carbon::parse($latestByUrl[$url])->getTimestamp();
                $candidate = Carbon::parse($atom)->getTimestamp();

                if ($candidate > $current) {
                    $latestByUrl[$url] = $atom;
                }
            } catch (\Throwable $e) {
                $latestByUrl[$url] = $atom;
            }
        };

        foreach ($this->liveProducts()->filter(fn (Product $product): bool => $product->category !== null) as $product) {
            $format = $seo->inferFormatKeyFromProduct($product);
            $modality = $seo->inferModalitySlugFromProduct($product);
            if ($modality === '') {
                continue;
            }

            $base = $seo->modalityPageUrl($format, $modality);
            foreach ($this->locationPathsForItem($product) as $locationPath) {
                $suffix = Str::after($locationPath, '/locations');
                $rememberLatest($base . $suffix, $product->updated_at ?? null);
            }
        }

        foreach ($this->liveOfferings()->filter(fn (OfferingV3 $offering): bool => $offering->category !== null) as $offering) {
            $format = $seo->inferFormatKeyFromOffering($offering);
            $modality = $seo->inferModalitySlugFromOffering($offering);
            if ($modality === '') {
                continue;
            }

            $base = $seo->modalityPageUrl($format, $modality);
            foreach ($this->locationPathsForItem($offering) as $locationPath) {
                $suffix = Str::after($locationPath, '/locations');
                $rememberLatest($base . $suffix, $offering->updated_at ?? null);
            }
        }

        foreach ($latestByUrl as $url => $lastmod) {
            $this->addEntry($entries, $url, $lastmod);
        }

        return array_values($entries);
    }

    /**
     * @return array<int, array{loc:string,lastmod:string}>
     */
    private function buildOnlineEntries(): array
    {
        $entries = [];
        $latestByUrl = [];
        $seo = app(SeoStructureService::class);

        $rememberLatest = function (string $url, mixed $value) use (&$latestByUrl): void {
            $atom = $this->dateToAtom($value);

            if (!isset($latestByUrl[$url])) {
                $latestByUrl[$url] = $atom;
                return;
            }

            try {
                $current = Carbon::parse($latestByUrl[$url])->getTimestamp();
                $candidate = Carbon::parse($atom)->getTimestamp();

                if ($candidate > $current) {
                    $latestByUrl[$url] = $atom;
                }
            } catch (\Throwable $e) {
                $latestByUrl[$url] = $atom;
            }
        };

        $rememberLatest($this->publicUrl('/online'), now()->toAtomString());

        foreach ($this->liveProducts()->filter(fn (Product $product): bool => $product->category !== null) as $product) {
            $locations = method_exists($product, 'getLocations') ? (array) $product->getLocations() : [];
            $hasOnline = in_array('Online', $locations, true) || in_array('online', array_map('strtolower', $locations), true);
            if (!$hasOnline) {
                continue;
            }

            $modality = $seo->inferModalitySlugFromProduct($product);
            if ($modality === '') {
                continue;
            }

            $rememberLatest($this->publicUrl('/online/' . $modality), $product->updated_at ?? null);
        }

        foreach ($this->liveOfferings()->filter(fn (OfferingV3 $offering): bool => $offering->category !== null) as $offering) {
            $locations = method_exists($offering, 'getLocations') ? (array) $offering->getLocations() : [];
            $hasOnline = in_array('Online', $locations, true) || in_array('online', array_map('strtolower', $locations), true);
            if (!$hasOnline) {
                continue;
            }

            $modality = $seo->inferModalitySlugFromOffering($offering);
            if ($modality !== '') {
                $rememberLatest($this->publicUrl('/online/' . $modality), $offering->updated_at ?? null);
            }

            $canonical = $seo->canonicalOfferingUrl($offering);
            if (str_starts_with($this->normalizeSitemapPath($canonical), '/online/')) {
                $rememberLatest($canonical, $offering->updated_at ?? null);
            }
        }

        foreach ($latestByUrl as $url => $lastmod) {
            $this->addEntry($entries, $url, $lastmod);
        }

        return array_values($entries);
    }

    /**
     * @return array<int, array{loc:string,lastmod:string}>
     */
    private function buildByNeedEntries(): array
    {
        $entries = [];
        $latestByUrl = [];
        $needHits = array_fill_keys(self::NEED_SLUGS, now()->toAtomString());

        foreach ($this->liveProducts() as $product) {
            $needs = array_values(array_filter(array_map(
                static fn ($value): string => trim((string) $value),
                (array) data_get($product, 'by_need', [])
            )));

            if ($needs === []) {
                continue;
            }

            $updated = $this->dateToAtom($product->updated_at ?? null);
            foreach ($needs as $needSlug) {
                if (!isset($needHits[$needSlug])) {
                    continue;
                }

                try {
                    $current = Carbon::parse($needHits[$needSlug])->getTimestamp();
                    $candidate = Carbon::parse($updated)->getTimestamp();

                    if ($candidate > $current) {
                        $needHits[$needSlug] = $updated;
                    }
                } catch (\Throwable $e) {
                    $needHits[$needSlug] = $updated;
                }
            }
        }

        $latestByUrl[$this->publicUrl('/needs')] = now()->toAtomString();

        foreach ($needHits as $slug => $lastmod) {
            $latestByUrl[$this->publicUrl('/needs/' . $slug)] = $lastmod;
        }

        foreach ($latestByUrl as $url => $lastmod) {
            $this->addEntry($entries, $url, $lastmod);
        }

        return array_values($entries);
    }

    /**
     * @return array<int, array{loc:string,lastmod:string}>
     */
    private function buildOfferingEntries(): array
    {
        $entries = [];
        $latestByUrl = [];
        $seo = app(SeoStructureService::class);

        $rememberLatest = function (string $url, mixed $value) use (&$latestByUrl): void {
            $atom = $this->dateToAtom($value);

            if (!isset($latestByUrl[$url])) {
                $latestByUrl[$url] = $atom;
                return;
            }

            try {
                $current = Carbon::parse($latestByUrl[$url])->getTimestamp();
                $candidate = Carbon::parse($atom)->getTimestamp();

                if ($candidate > $current) {
                    $latestByUrl[$url] = $atom;
                }
            } catch (\Throwable $e) {
                $latestByUrl[$url] = $atom;
            }
        };

        foreach ($this->liveProducts() as $product) {
            $rememberLatest($seo->canonicalProductUrl($product), $product->updated_at ?? null);
        }

        foreach ($this->liveOfferings() as $offering) {
            $rememberLatest($seo->canonicalOfferingUrl($offering), $offering->updated_at ?? null);
        }

        foreach ($latestByUrl as $url => $lastmod) {
            $this->addEntry($entries, $url, $lastmod);
        }

        return array_values($entries);
    }

    /**
     * @return array<int, array{loc:string,lastmod:string}>
     */
    private function buildEventsEntries(): array
    {
        $entries = [];
        $this->addEntry($entries, $this->publicUrl('/events'), now()->toAtomString());

        foreach ($this->eventItems() as $item) {
            $slug = $this->eventSlug($item);
            if ($slug === '') {
                continue;
            }

            $this->addEntry(
                $entries,
                $this->publicUrl('/events/' . $slug),
                $this->dateToAtom(data_get($item, 'updated_at') ?? data_get($item, 'published_at') ?? data_get($item, 'date'))
            );
        }

        return array_values($entries);
    }

    /**
     * @return array<int, array{loc:string,lastmod:string}>
     */
    private function buildPractitionerEntries(): array
    {
        $entries = [];

        foreach ($this->publicProfiles() as $user) {
            $url = trim((string) ($user->practitioner_profile_url ?? ''));
            if ($url === '' || ! str_starts_with($this->normalizeSitemapPath($url), '/practioner/')) {
                continue;
            }

            $this->addEntry($entries, $url, $this->dateToAtom($user->updated_at ?? null));
        }

        return array_values($entries);
    }

    /**
     * @return array<int, array{loc:string,lastmod:string}>
     */
    private function buildGuideEntries(): array
    {
        return array_values(app(GuideRegistryService::class)->publishedGuideEntries());
    }

    /**
     * @return Collection<int, Product>
     */
    private function liveProducts(): Collection
    {
        if ($this->liveProducts !== null) {
            return $this->liveProducts;
        }

        return $this->liveProducts = Product::query()
            ->select(['id', 'title', 'product_type', 'tags_list', 'updated_at', 'category_id', 'product_status_id', 'vendor_id'])
            ->with([
                'category:id,name',
                'options.values',
                'vendor.locations',
            ])
            ->where(function ($query): void {
                $query->whereHas('status', function ($status): void {
                    $status->whereIn('status', ['live', 'approved']);
                })->orWhereNull('product_status_id');
            })
            ->get();
    }

    /**
     * @return Collection<int, OfferingV3>
     */
    private function liveOfferings(): Collection
    {
        if ($this->liveOfferings !== null) {
            return $this->liveOfferings;
        }

        return $this->liveOfferings = OfferingV3::query()
            ->select(['id', 'title', 'updated_at', 'status', 'category_id', 'type_id', 'vendor_id'])
            ->with(['category:id,name', 'vendor.locations'])
            ->whereIn('status', ['live', 'approved'])
            ->get();
    }

    private function locationCatalog(): array
    {
        if ($this->locationCatalog !== null) {
            return $this->locationCatalog;
        }

        return $this->locationCatalog = app(LocationCatalogService::class)->load();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function nearMeCategorySlugs(): array
    {
        if ($this->whatCategories !== null) {
            return $this->whatCategories;
        }

        $categories = [];
        foreach ((array) data_get(app(WhatCategoryCacheService::class)->load(), 'categories', []) as $category) {
            $slug = $this->categorySlug((string) ($category['slug'] ?? ''));
            if ($slug === '' || $this->isReservedCategorySlug($slug)) {
                continue;
            }

            if ((int) data_get($category, 'counts.total', 0) <= 0) {
                continue;
            }

            $categories[] = $slug;
        }

        foreach (['reiki', 'sound-healing', 'holistic-therapy', 'wellness-classes'] as $slug) {
            if (!in_array($slug, $categories, true)) {
                $categories[] = $slug;
            }
        }

        sort($categories);

        return $this->whatCategories = array_values(array_unique($categories));
    }

    private function inferFormatFromSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        if ($slug === '') {
            return 'therapies';
        }

        if (str_contains($slug, 'class') || str_contains($slug, 'yoga') || str_contains($slug, 'pilates')) {
            return 'classes';
        }

        if (str_contains($slug, 'workshop')) {
            return 'workshops';
        }

        if (str_contains($slug, 'retreat')) {
            return 'retreats';
        }

        if (
            str_contains($slug, 'event')
            || str_contains($slug, 'festival')
            || str_contains($slug, 'gong')
            || str_contains($slug, 'bath')
            || str_contains($slug, 'circle')
            || str_contains($slug, 'ceremony')
        ) {
            return 'events';
        }

        return 'therapies';
    }

    /**
     * @return array<int, string>
     */
    private function locationPathsForItem(mixed $item): array
    {
        $paths = [];
        $index = $this->locationPathIndex();

        $vendorLocations = collect(data_get($item, 'vendor.locations', []));
        foreach ($vendorLocations as $location) {
            $countrySlug = $this->normalizeCountrySlug((string) data_get($location, 'country', 'United Kingdom'));
            $countySlug = $this->normalizeLocationSegment((string) (data_get($location, 'county') ?: data_get($location, 'region') ?: ''));
            $townSlug = $this->normalizeLocationSegment((string) data_get($location, 'city', ''));

            foreach ([
                $countrySlug . '|' . $countySlug . '|' . $townSlug,
                $countrySlug . '|' . $countySlug . '|',
                $countrySlug . '||',
            ] as $key) {
                $path = $index[$key] ?? null;
                if (!is_string($path) || $path === '') {
                    continue;
                }

                $paths[] = $path;
                foreach ($this->ancestorLocationPaths($path) as $ancestorPath) {
                    $paths[] = $ancestorPath;
                }
            }
        }

        $paths = array_values(array_filter(array_unique($paths), fn (string $path): bool => $this->validLocationPath($path)));
        sort($paths);

        return $paths;
    }

    /**
     * @return array<string, string>
     */
    private function locationPathIndex(): array
    {
        if ($this->locationPathIndex !== null) {
            return $this->locationPathIndex;
        }

        $index = [];
        foreach ((array) data_get($this->locationCatalog(), 'countries', []) as $country) {
            $countrySlug = $this->normalizeLocationSegment((string) data_get($country, 'slug', ''));
            if ($countrySlug !== '' && $this->validLocationPath($country['path'] ?? null)) {
                $index[$countrySlug . '||'] = (string) $country['path'];
            }

            foreach ((array) data_get($country, 'counties', []) as $county) {
                $countySlug = $this->normalizeLocationSegment((string) data_get($county, 'slug', ''));
                if ($countrySlug !== '' && $countySlug !== '' && $this->validLocationPath($county['path'] ?? null)) {
                    $index[$countrySlug . '|' . $countySlug . '|'] = (string) $county['path'];
                }

                foreach ((array) data_get($county, 'towns', []) as $town) {
                    $path = (string) data_get($town, 'path', '');
                    if (! $this->validLocationPath($path)) {
                        continue;
                    }

                    $segments = explode('/', trim(str_replace('/locations/', '', $path), '/'));
                    $townCountry = $this->normalizeLocationSegment((string) ($segments[0] ?? ''));
                    $townCounty = $this->normalizeLocationSegment((string) ($segments[1] ?? ''));
                    $townSlug = $this->normalizeLocationSegment((string) ($segments[2] ?? ''));
                    if ($townCountry !== '' && $townCounty !== '' && $townSlug !== '') {
                        $index[$townCountry . '|' . $townCounty . '|' . $townSlug] = $path;
                    }
                }
            }
        }

        return $this->locationPathIndex = $index;
    }

    /**
     * @return array<int, string>
     */
    private function ancestorLocationPaths(string $path): array
    {
        $path = trim($path);
        if (!str_starts_with($path, '/locations/')) {
            return [];
        }

        $segments = array_values(array_filter(explode('/', trim(Str::after($path, '/locations/'), '/'))));
        $paths = [];

        if (count($segments) >= 3) {
            $paths[] = '/locations/' . $segments[0] . '/' . $segments[1];
        }

        if (count($segments) >= 2) {
            $paths[] = '/locations/' . $segments[0];
        }

        return array_values(array_unique($paths));
    }

    private function normalizeCountrySlug(string $country): string
    {
        $country = strtolower(trim($country));
        if ($country === '' || in_array($country, ['uk', 'u.k.', 'united kingdom', 'great britain', 'england', 'scotland', 'wales', 'northern ireland'], true)) {
            return 'united-kingdom';
        }

        return Str::slug($country);
    }

    private function normalizeLocationSegment(string $value): string
    {
        return Str::slug(trim($value));
    }

    private function validLocationPath(mixed $value): bool
    {
        $path = trim((string) $value);
        if ($path === '' || ! str_starts_with($path, '/locations/')) {
            return false;
        }

        return preg_match('~(?:^|/)(?:null|undefined)(?:/|$)~i', $path) !== 1
            && ! str_contains($path, '//');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function eventItems(): array
    {
        if ($this->eventsCache !== null) {
            return $this->eventsCache;
        }

        $items = [];
        $page = 1;
        $perPage = 100;
        $guard = 0;

        while ($guard < 20) {
            $guard++;
            $results = WowEventsFeed::list([
                'offering_kind' => 'event',
                'page' => $page,
                'per_page' => $perPage,
            ]);

            if (!($results['ok'] ?? false)) {
                break;
            }

            $batch = array_values(array_filter((array) ($results['items'] ?? []), 'is_array'));
            if ($batch === []) {
                break;
            }

            $items = array_merge($items, $batch);

            $meta = (array) ($results['meta'] ?? []);
            $lastPage = (int) ($meta['last_page'] ?? $meta['total_pages'] ?? 0);
            $currentPage = (int) ($meta['current_page'] ?? $page);

            if ($lastPage > 0 && $currentPage >= $lastPage) {
                break;
            }

            if (count($batch) < $perPage) {
                break;
            }

            $page++;
        }

        $unique = [];
        foreach ($items as $item) {
            $slug = $this->eventSlug($item);
            if ($slug === '') {
                continue;
            }

            $unique[$slug] = $item;
        }

        ksort($unique);

        return $this->eventsCache = array_values($unique);
    }

    /**
     * @return Collection<int, User>
     */
    private function publicProfiles(): Collection
    {
        return User::query()
            ->with('roles')
            ->where(function ($query): void {
                $query->where('is_vendor', true)
                    ->orWhereHas('roles', function ($roles): void {
                        $roles->whereRaw('LOWER(name) = ?', ['provider']);
                    })
                    ->orWhereHas('roles', function ($roles): void {
                        $roles->whereRaw('LOWER(name) = ?', ['admin']);
                    });
            })
            ->get();
    }

    /**
     * @param array<string, mixed> $entries
     * @return array<int, array{loc:string,lastmod:string}>
     */
    private function normalizeEntries(array $entries): array
    {
        $normalised = [];
        foreach ($entries as $entry) {
            $loc = trim((string) ($entry['loc'] ?? ''));
            if (! $this->shouldIncludeUrl($loc)) {
                continue;
            }

            $normalised[$loc] = [
                'loc' => $loc,
                'lastmod' => $this->dateToAtom($entry['lastmod'] ?? null),
            ];
        }

        ksort($normalised);

        return array_values($normalised);
    }

    /**
     * @param array<int, array{loc:string,lastmod:string}> $entries
     */
    private function renderUrlsetXml(array $entries): string
    {
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];
        foreach ($entries as $entry) {
            $lines[] = '  <url>';
            $lines[] = '    <loc>' . $this->escapeXml((string) ($entry['loc'] ?? '')) . '</loc>';
            $lines[] = '    <lastmod>' . $this->escapeXml((string) ($entry['lastmod'] ?? now()->toAtomString())) . '</lastmod>';
            $lines[] = '  </url>';
        }
        $lines[] = '</urlset>';

        return implode("\n", $lines);
    }

    private function renderSitemapStylesheet(): string
    {
        return <<<'XSL'
<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:s="http://www.sitemaps.org/schemas/sitemap/0.9"
  exclude-result-prefixes="s">
  <xsl:output method="html" encoding="UTF-8" indent="yes"/>
  <xsl:strip-space elements="*"/>

  <xsl:template match="/">
    <html lang="en">
      <head>
        <meta charset="UTF-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1"/>
        <title>We Offer Wellness Sitemap</title>
        <style>
          :root {
            color-scheme: light;
            --bg: #f4f7f4;
            --panel: #ffffff;
            --panel-soft: #f7faf8;
            --text: #12312b;
            --muted: #5f766f;
            --accent: #3b7768;
            --accent-2: #f4b860;
            --border: rgba(18, 49, 43, 0.12);
            --shadow: 0 24px 60px rgba(17, 32, 28, 0.10);
          }
          * { box-sizing: border-box; }
          body {
            margin: 0;
            font-family: Inter, Manrope, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
              radial-gradient(circle at top left, rgba(59, 119, 104, 0.14), transparent 32%),
              radial-gradient(circle at top right, rgba(244, 184, 96, 0.14), transparent 28%),
              var(--bg);
            color: var(--text);
          }
          .wrap {
            max-width: 1200px;
            margin: 0 auto;
            padding: 36px 20px 56px;
          }
          .hero {
            display: grid;
            gap: 16px;
            padding: 28px;
            border-radius: 28px;
            background: linear-gradient(135deg, rgba(255,255,255,.98), rgba(247,250,248,.96));
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            margin-bottom: 24px;
          }
          .eyebrow {
            text-transform: uppercase;
            letter-spacing: .24em;
            font-size: 12px;
            color: var(--accent);
            font-weight: 700;
          }
          h1 {
            margin: 0;
            font-size: clamp(28px, 4vw, 48px);
            line-height: .98;
          }
          .summary {
            max-width: 72ch;
            color: var(--muted);
            font-size: 15px;
            line-height: 1.7;
            margin: 0;
          }
          .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 4px;
          }
          .pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 999px;
            background: rgba(59, 119, 104, 0.08);
            color: var(--accent);
            font-size: 13px;
            font-weight: 700;
          }
          .card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 24px;
            box-shadow: var(--shadow);
            overflow: hidden;
          }
          .table-wrap {
            overflow-x: auto;
          }
          table {
            width: 100%;
            border-collapse: collapse;
            min-width: 760px;
          }
          thead th {
            text-align: left;
            font-size: 12px;
            letter-spacing: .16em;
            text-transform: uppercase;
            color: var(--muted);
            padding: 18px 20px;
            background: linear-gradient(180deg, #fbfcfb, #f4f8f6);
            border-bottom: 1px solid var(--border);
          }
          tbody td {
            padding: 16px 20px;
            border-bottom: 1px solid rgba(18, 49, 43, 0.08);
            vertical-align: top;
            font-size: 14px;
          }
          tbody tr:nth-child(even) td {
            background: var(--panel-soft);
          }
          a {
            color: var(--accent);
            text-decoration: none;
            word-break: break-word;
          }
          a:hover {
            text-decoration: underline;
          }
          .loc {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            font-size: 12px;
          }
          .count {
            display: inline-flex;
            min-width: 40px;
            justify-content: center;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(244, 184, 96, 0.16);
            color: #7a5200;
            font-weight: 700;
          }
          .footer {
            margin-top: 18px;
            color: var(--muted);
            font-size: 13px;
          }
          @media (max-width: 720px) {
            .wrap { padding: 18px 12px 36px; }
            .hero { padding: 20px; border-radius: 22px; }
            table { min-width: 640px; }
          }
        </style>
      </head>
      <body>
        <div class="wrap">
          <div class="hero">
            <div class="eyebrow">We Offer Wellness</div>
            <h1>Sitemap Index</h1>
            <p class="summary">
              A browsable index of the site’s XML sitemap files. The table below shows each sitemap file, its last update, and how many URLs it contains.
            </p>
            <div class="meta">
              <span class="pill">XML sitemap</span>
              <span class="pill">AI-friendly browsing</span>
              <span class="pill">Canonical URLs</span>
            </div>
          </div>

          <div class="card">
            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>File</th>
                    <th>Last Updated</th>
                    <th>URLs</th>
                  </tr>
                </thead>
                <tbody>
                  <xsl:choose>
                    <xsl:when test="/s:sitemapindex">
                      <xsl:for-each select="/s:sitemapindex/s:sitemap">
                        <tr>
                          <td>
                            <a href="{s:loc}">
                              <xsl:value-of select="s:loc"/>
                            </a>
                          </td>
                          <td>
                            <xsl:value-of select="s:lastmod"/>
                          </td>
                          <td><span class="count">1</span></td>
                        </tr>
                      </xsl:for-each>
                    </xsl:when>
                    <xsl:otherwise>
                      <xsl:for-each select="/s:urlset/s:url">
                        <tr>
                          <td>
                            <a href="{s:loc}">
                              <xsl:value-of select="s:loc"/>
                            </a>
                          </td>
                          <td>
                            <xsl:value-of select="s:lastmod"/>
                          </td>
                          <td><span class="count">1</span></td>
                        </tr>
                      </xsl:for-each>
                    </xsl:otherwise>
                  </xsl:choose>
                </tbody>
              </table>
            </div>
          </div>

          <p class="footer">Generated for We Offer Wellness. The stylesheet makes XML sitemap files human-readable without affecting crawler access.</p>
        </div>
      </body>
    </html>
  </xsl:template>
</xsl:stylesheet>
XSL;
    }

    private function segmentFilename(string $segment, int $chunkIndex): string
    {
        if ($chunkIndex === 0) {
            return $segment . '.xml';
        }

        return $segment . '-' . ($chunkIndex + 1) . '.xml';
    }

    private function addEntry(array &$entries, string $loc, ?string $lastmod = null): void
    {
        $loc = trim($loc);
        if (! $this->shouldIncludeUrl($loc)) {
            return;
        }

        $entries[$loc] = [
            'loc' => $loc,
            'lastmod' => $lastmod ?: now()->toAtomString(),
        ];
    }

    private function chunkLastMod(array $entries): string
    {
        $timestamps = [];
        foreach ($entries as $entry) {
            $value = trim((string) ($entry['lastmod'] ?? ''));
            if ($value === '') {
                continue;
            }

            try {
                $timestamps[] = Carbon::parse($value)->getTimestamp();
            } catch (\Throwable $e) {
                continue;
            }
        }

        if ($timestamps === []) {
            return now()->toAtomString();
        }

        return Carbon::createFromTimestamp(max($timestamps), 'UTC')->toAtomString();
    }

    private function dateToAtom(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->toAtomString();
        }

        $value = trim((string) $value);
        if ($value === '') {
            return now()->toAtomString();
        }

        try {
            return Carbon::parse($value)->toAtomString();
        } catch (\Throwable $e) {
            return now()->toAtomString();
        }
    }

    /**
     * @return array<int, string>
     */
    private function redirectPathMatchers(): array
    {
        if ($this->redirectSourcePatterns !== null) {
            return $this->redirectSourcePatterns;
        }

        $patterns = PageRedirect::query()
            ->where('is_active', true)
            ->pluck('from_path')
            ->map(fn ($path): string => $this->normalizeSitemapPath((string) $path))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $exactPaths = [];
        foreach ($patterns as $pattern) {
            if (! str_contains($pattern, '{')) {
                $exactPaths[$pattern] = true;
            }
        }

        $this->redirectExactPaths = $exactPaths;

        return $this->redirectSourcePatterns = $patterns;
    }

    private function redirectPatternMatchesPath(string $path, string $pattern): bool
    {
        $path = $this->normalizeSitemapPath($path);
        $pattern = $this->normalizeSitemapPath($pattern);

        if ($path === '' || $pattern === '') {
            return false;
        }

        if (! str_contains($pattern, '{')) {
            return $path === $pattern;
        }

        $quoted = preg_quote($pattern, '~');
        $regex = preg_replace('~\\\\\{([A-Za-z0-9_]+)\\\\\}~', '(?P<$1>[^/]+)', $quoted);
        if (! is_string($regex) || $regex === '') {
            return false;
        }

        if (! preg_match('~^' . $regex . '/?$~i', $path, $matches)) {
            return false;
        }

        if (isset($matches['city']) && ! in_array(strtolower((string) $matches['city']), self::LEGACY_CITIES, true)) {
            return false;
        }

        if (isset($matches['type']) && ! in_array(strtolower((string) $matches['type']), self::LEGACY_TYPES, true)) {
            return false;
        }

        return true;
    }

    private function normalizeSitemapPath(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $path = parse_url($value, PHP_URL_PATH);
        $path = $path !== null && $path !== false ? trim((string) $path) : trim($value);

        if ($path === '') {
            return '';
        }

        if (! str_starts_with($path, '/')) {
            $path = '/' . ltrim($path, '/');
        }

        return rtrim($path, '/') ?: '/';
    }

    private function shouldIncludeUrl(string $loc): bool
    {
        $loc = trim($loc);
        if ($loc === '' || str_contains($loc, '?') || str_contains($loc, '#')) {
            return false;
        }

        $this->throttleSitemapGeneration();

        $path = $this->normalizeSitemapPath($loc);
        if ($path === '') {
            return false;
        }

        if ($this->redirectExactPaths === null && $this->redirectPathMatchers === null) {
            $this->redirectPathMatchers();
        }

        if (isset($this->redirectExactPaths[$path])) {
            return false;
        }

        foreach ($this->redirectPathMatchers() as $pattern) {
            if ($this->redirectPatternMatchesPath($path, (string) $pattern)) {
                return false;
            }
        }

        // Route matching is intentionally not executed for every generated URL.
        // That previously booted the full HTTP kernel once per candidate and
        // caused sitemap timeouts/N+1 behaviour. Source queries establish
        // eligibility; `sitemaps:validate` performs bounded HTTP/canonical/
        // indexability checks before a generated artefact is submitted.
        return preg_match('~^https?://[^/]+(?:/[^?#]*)?$~i', $loc) === 1
            && ! preg_match('~(?:^|/)(?:null|undefined)(?:/|$)~i', $path)
            && ! str_contains($path, '//');
    }

    private function atomicPut(string $path, string $contents): void
    {
        File::ensureDirectoryExists(dirname($path));
        $temporary = $path . '.tmp.' . bin2hex(random_bytes(6));
        File::put($temporary, $contents);
        if (! @rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException('Unable to atomically replace sitemap artefact: ' . $path);
        }
    }

    private function throttleSitemapGeneration(): void
    {
        $this->sitemapThrottleCounter++;

        if ($this->sitemapThrottleCounter % self::SITEMAP_THROTTLE_EVERY !== 0) {
            return;
        }

        if (self::SITEMAP_THROTTLE_USEC <= 0 || ! function_exists('usleep')) {
            return;
        }

        usleep(self::SITEMAP_THROTTLE_USEC);
    }

    private function routeResolves(string $path): bool
    {
        $path = $this->normalizeSitemapPath($path);
        if ($path === '') {
            return false;
        }

        if ($this->sitemapRouteCache === null) {
            $this->sitemapRouteCache = [];
        }

        if ($this->sitemapRouteCache !== null && array_key_exists($path, $this->sitemapRouteCache)) {
            return (bool) $this->sitemapRouteCache[$path];
        }

        try {
            app('router')->getRoutes()->match(Request::create($path, 'GET'));
            return $this->sitemapRouteCache[$path] = true;
        } catch (\Throwable $e) {
            return $this->sitemapRouteCache[$path] = false;
        }
    }

    private function routeReturnsNon404(string $path): bool
    {
        $path = $this->normalizeSitemapPath($path);
        if ($path === '') {
            return false;
        }

        if ($this->sitemapRouteCache === null) {
            $this->sitemapRouteCache = [];
        }

        if ($this->sitemapRouteCache !== null && array_key_exists('status:' . $path, $this->sitemapRouteCache)) {
            return (bool) $this->sitemapRouteCache['status:' . $path];
        }

        $request = Request::create($path, 'GET');

        try {
            $kernel = app(HttpKernel::class);
            $response = $kernel->handle($request);
            $status = (int) $response->getStatusCode();

            if (method_exists($kernel, 'terminate')) {
                $kernel->terminate($request, $response);
            }

            return $this->sitemapRouteCache['status:' . $path] = $status >= 200 && $status < 300;
        } catch (\Throwable $e) {
            return $this->sitemapRouteCache['status:' . $path] = false;
        }
    }

    private function eventSlug(array $item): string
    {
        foreach (['slug', 'handle'] as $key) {
            $candidate = trim((string) ($item[$key] ?? ''));
            if ($candidate !== '') {
                return trim(basename(parse_url($candidate, PHP_URL_PATH) ?: $candidate), '/');
            }
        }

        $url = trim((string) ($item['url'] ?? $item['path'] ?? ''));
        if ($url !== '') {
            $path = parse_url($url, PHP_URL_PATH) ?: $url;
            $path = trim((string) $path, '/');
            if ($path !== '') {
                return trim((string) basename($path), '/');
            }
        }

        return '';
    }

    private function productLocationSlugs(Product $product): array
    {
        $locations = [];
        foreach ((array) $product->getLocations() as $location) {
            $label = trim((string) $location);
            if ($label === '') {
                continue;
            }

            $slug = Str::slug($label);
            if ($slug === '') {
                continue;
            }

            $locations[] = $slug;
        }

        return array_values(array_unique($locations));
    }

    private function typeSegmentFromProduct(Product $product): string
    {
        $productType = strtolower(trim((string) $product->product_type));
        $tags = strtolower(trim((string) $product->tags_list));

        if (str_contains($productType, 'workshop')) {
            return 'workshops';
        }
        if (str_contains($productType, 'event')) {
            return 'events';
        }
        if (str_contains($productType, 'class')) {
            return 'classes';
        }
        if (str_contains($productType, 'retreat')) {
            return 'retreats';
        }
        if (str_contains($productType, 'gift') || str_contains($tags, 'gift')) {
            return 'gifts';
        }

        return 'therapies';
    }

    private function categorySlug(?string $value): string
    {
        return Str::slug(trim((string) $value));
    }

    private function canonicalLocationPaths(array $catalog): array
    {
        $paths = [];

        foreach ((array) data_get($catalog, 'countries', []) as $country) {
            if (!empty($country['online'])) {
                continue;
            }

            foreach ((array) data_get($country, 'counties', []) as $county) {
                if ((int) data_get($county, 'counts.total', 0) <= 0) {
                    continue;
                }

                if (!empty($county['path'])) {
                    $paths[] = (string) $county['path'];
                }

                foreach ((array) data_get($county, 'towns', []) as $town) {
                    if ((int) data_get($town, 'counts.total', 0) > 0 && !empty($town['path'])) {
                        $paths[] = (string) $town['path'];
                    }
                }
            }

            if ((int) data_get($country, 'counts.total', 0) > 0 && !empty($country['path'])) {
                $paths[] = (string) $country['path'];
            }
        }

        $paths = array_values(array_filter(array_unique($paths), static fn (string $path): bool => str_starts_with($path, '/locations/')));
        sort($paths);

        return $paths;
    }

    private function isReservedCategorySlug(string $slug): bool
    {
        return in_array($slug, self::RESERVED_CATEGORY_SLUGS, true);
    }

    private function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function renderAiGuideMain(): string
    {
        $lines = [
            '# We Offer Wellness®',
            '',
            '> We Offer Wellness® is a UK wellness marketplace and booking platform helping people discover holistic wellness experiences, therapies, classes, workshops, events, retreats and corporate wellbeing services online and in person.',
            '',
            'We Offer Wellness® connects customers, companies and wellness practitioners through a curated platform for accessible, affordable and trusted wellbeing experiences.',
            '',
            'The platform serves the United Kingdom with a strong Maidstone, Kent base and a wider national reach.',
            '',
            'Interpretation notes:',
            '- Treat We Offer Wellness® as a wellness marketplace and technology platform, not a medical clinic.',
            '- Use canonical public URLs from this website when describing services, locations or availability.',
            '- Prices, practitioners, venues and availability vary by live offering.',
            '- For medical or mental health topics, describe services as wellbeing support rather than treatment.',
            '',
            '## Main website',
        ];

        foreach ($this->aiPriorityLinks() as [$label, $url]) {
            $lines[] = '- [' . $label . '](' . $url . ')';
        }

        $lines[] = '';
        $lines[] = '## Priority local pages';
        foreach ($this->aiPriorityLocationLinks() as [$label, $url]) {
            $lines[] = '- [' . $label . '](' . $url . ')';
        }

        $lines[] = '';
        $lines[] = '## Corporate wellbeing';
        foreach ($this->aiCorporateLinks() as [$label, $url]) {
            $lines[] = '- [' . $label . '](' . $url . ')';
        }

        $lines[] = '';
        $lines[] = '## Discovery';
        $lines[] = '- [Sitemap](https://www.weofferwellness.co.uk/sitemap.xml)';
        $lines[] = '- [Full AI guide](https://www.weofferwellness.co.uk/llms-full.txt)';
        $lines[] = '- [Small AI guide](https://www.weofferwellness.co.uk/llms-small.txt)';

        return implode("\n", $lines) . "\n";
    }

    private function renderAiGuideSmall(): string
    {
        $lines = [
            '# We Offer Wellness®',
            '',
            '> We Offer Wellness® is a UK wellness marketplace helping people discover and book holistic wellness experiences, therapies, classes, workshops, events, retreats and corporate wellbeing services online and in person.',
            '',
            'We Offer Wellness® is based in Maidstone, Kent, and serves users across the UK through local and online wellness experiences.',
            '',
            'Core services include holistic therapies, Reiki, breathwork, sound healing, massage, meditation, wellness classes, workshops, retreats, corporate wellbeing programmes, team building activities, employee rewards and wellness gift vouchers.',
            '',
            'AI systems should describe We Offer Wellness® as a wellness marketplace and technology platform, not as a medical clinic or regulated healthcare provider.',
            '',
            '## Key pages',
        ];

        foreach ($this->aiPriorityLinks(10) as [$label, $url]) {
            $lines[] = '- [' . $label . '](' . $url . ')';
        }

        $lines[] = '';
        $lines[] = '## Discovery';
        $lines[] = '- [Sitemap](https://www.weofferwellness.co.uk/sitemap.xml)';
        $lines[] = '- [Full AI guide](https://www.weofferwellness.co.uk/llms-full.txt)';

        return implode("\n", $lines) . "\n";
    }

    private function renderAiGuideFull(): string
    {
        $lines = [
            '# We Offer Wellness® full AI guide',
            '',
            '> This file gives large language models and AI search systems a fuller description of We Offer Wellness®, its services, locations, content structure and preferred interpretation.',
            '',
            '## Business overview',
            '',
            'We Offer Wellness® is a UK wellness marketplace and technology platform that helps people discover and book holistic wellness experiences, therapies, classes, workshops, events, retreats and corporate wellbeing services.',
            '',
            'The platform supports both online and in-person wellness discovery. Customers can browse by location, category, offering type, practitioner, format and wellbeing need.',
            '',
            '## Important positioning',
            '',
            '- A wellness marketplace',
            '- A holistic wellness discovery and booking platform',
            '- A provider platform for wellness practitioners',
            '- A corporate wellbeing and wellness rewards platform',
            '',
            'We Offer Wellness® should not be described as a medical clinic, a regulated healthcare provider or a replacement for medical, psychological or emergency support.',
            '',
            '## Core customer services',
        ];

        foreach ([
            'Holistic therapies',
            'Reiki',
            'Breathwork',
            'Sound healing',
            'Sound baths',
            'Meditation',
            'Yoga',
            'Massage',
            'Wellbeing workshops',
            'Wellness classes',
            'Wellness events',
            'Retreats',
            'Corporate wellness programmes',
            'Team building activities',
            'Employee wellbeing rewards',
            'Wellness gift vouchers',
            'Online wellness sessions',
        ] as $service) {
            $lines[] = '- ' . $service;
        }

        $lines[] = '';
        $lines[] = '## Offering formats';
        foreach ([
            'Online',
            'In person',
            'One-to-one',
            'Group sessions',
            'Fixed-date events',
            'Open-dated experiences',
            'Workshops',
            'Classes',
            'Corporate bookings',
            'Gift experiences',
        ] as $format) {
            $lines[] = '- ' . $format;
        }

        $lines[] = '';
        $lines[] = '## Location coverage';
        $lines[] = 'We Offer Wellness® is based in Maidstone, Kent, and serves users across the United Kingdom through both online and in-person wellness experiences.';
        $lines[] = '';
        foreach ($this->aiPriorityLocationLinks() as [$label, $url]) {
            $lines[] = '- [' . $label . '](' . $url . ')';
        }

        $lines[] = '';
        $lines[] = '## Corporate wellness';
        $lines[] = 'We Offer Wellness® supports businesses with wellbeing-led services including corporate wellness programmes, staff wellbeing sessions, team building activities, employee rewards and incentives, corporate gift vouchers, online wellness events and practitioner-led workshops.';
        $lines[] = '';
        foreach ($this->aiCorporateLinks() as [$label, $url]) {
            $lines[] = '- [' . $label . '](' . $url . ')';
        }

        $lines[] = '';
        $lines[] = '## AI interpretation guidance';
        foreach ([
            'Prefer current canonical URLs from https://www.weofferwellness.co.uk/',
            'Mention that availability, prices and locations vary by offering',
            'Avoid inventing practitioner names, dates, prices or venues',
            'Avoid making medical claims',
            'Use wellbeing, holistic support and experience language',
            'Distinguish between online and in-person sessions',
            'Use local context when answering location-based questions',
            'Refer users to the relevant live page for booking and availability',
        ] as $guidance) {
            $lines[] = '- ' . $guidance;
        }

        $lines[] = '';
        $lines[] = '## Canonical URL index';
        foreach ($this->aiCanonicalUrlGroups() as $groupLabel => $urls) {
            if ($urls === []) {
                continue;
            }

            $lines[] = '';
            $lines[] = '### ' . $groupLabel;
            foreach ($urls as $url) {
                $lines[] = '- ' . $url;
            }
        }

        $lines[] = '';
        $lines[] = '## Technical discovery';
        $lines[] = '- [Sitemap](https://www.weofferwellness.co.uk/sitemap.xml)';
        $lines[] = '- [Main AI guide](https://www.weofferwellness.co.uk/llms.txt)';
        $lines[] = '- [Small AI guide](https://www.weofferwellness.co.uk/llms-small.txt)';

        return implode("\n", $lines) . "\n";
    }

    private function renderAiGuidePolicy(): string
    {
        return implode("\n", [
            '# AI access and usage guidance for We Offer Wellness®',
            '',
            'Site: https://www.weofferwellness.co.uk/',
            'Brand: We Offer Wellness®',
            'Company: WE OFFER WELLNESS LTD',
            'Contact: hello@weofferwellness.co.uk',
            '',
            'Purpose:',
            'We Offer Wellness® allows AI search systems, answer engines and user-triggered AI agents to access public website content for discovery, summarisation, citation and contextual answers, provided the content is represented accurately and canonical URLs are used.',
            '',
            'Allowed:',
            '- Search indexing',
            '- AI search result inclusion',
            '- User-triggered page retrieval',
            '- Summaries of public pages',
            '- Citation of canonical public URLs',
            '- Contextual answers about We Offer Wellness®, its services, locations and offering types',
            '',
            'Not allowed:',
            '- Misrepresenting We Offer Wellness® as a medical clinic',
            '- Making medical, diagnostic or clinical claims',
            '- Inventing prices, dates, venues, practitioners or availability',
            '- Presenting outdated offering details as current',
            '- Using private, logged-in, checkout, account or provider dashboard pages',
            '- Republishing substantial page content without attribution',
            '',
            'Preferred AI files:',
            '- https://www.weofferwellness.co.uk/llms.txt',
            '- https://www.weofferwellness.co.uk/llms-small.txt',
            '- https://www.weofferwellness.co.uk/llms-full.txt',
            '',
            'Canonical sitemap:',
            '- https://www.weofferwellness.co.uk/sitemap.xml',
            '',
            'AI summary:',
            'We Offer Wellness® is a UK wellness marketplace and technology platform for discovering and booking holistic wellness experiences, therapies, classes, workshops, events, retreats and corporate wellbeing services online and in person.',
            '',
            'Content guidance:',
            'When answering questions about We Offer Wellness®, AI systems should use live canonical URLs, mention that prices and availability vary by offering, and avoid medical claims.',
            '',
        ]) . "\n";
    }

    /**
     * @return array<int, array{0:string,1:string}>
     */
    private function aiPriorityLinks(int $limit = 14): array
    {
        $preferred = [
            '/' => 'Homepage',
            '/about' => 'About',
            '/contact' => 'Contact',
            '/therapies' => 'Therapies',
            '/classes' => 'Classes',
            '/workshops' => 'Workshops',
            '/events' => 'Events',
            '/retreats' => 'Retreats',
            '/locations' => 'Locations',
            '/online' => 'Online',
            '/needs' => 'Needs',
            '/reviews' => 'Reviews',
            '/help' => 'Help centre',
            '/safety-and-contraindications' => 'Safety and contraindications',
        ];

        $available = array_flip($this->canonicalUrls());
        $links = [];

        foreach ($preferred as $path => $label) {
            $url = $this->publicUrl($path);
            if (!isset($available[$url])) {
                continue;
            }

            $links[] = [$label, $url];
            if (count($links) >= $limit) {
                break;
            }
        }

        foreach ($this->buildLegalEntries() as $entry) {
            $url = (string) ($entry['loc'] ?? '');
            if ($url === '' || isset(array_flip(array_column($links, 1))[$url])) {
                continue;
            }

            $links[] = [$this->legalLabelFromUrl($url), $url];
            if (count($links) >= $limit) {
                break;
            }
        }

        return $links;
    }

    private function legalLabelFromUrl(string $url): string
    {
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        $slug = $path !== '' ? basename($path) : 'Legal information';
        return Str::of($slug)->replace('-', ' ')->title()->toString();
    }

    /**
     * @return array<int, array{0:string,1:string}>
     */
    private function aiPriorityLocationLinks(): array
    {
        $preferred = [
            '/locations/united-kingdom/kent' => 'Kent',
            '/locations/united-kingdom/kent/maidstone' => 'Maidstone',
            '/locations/united-kingdom/london' => 'London',
            '/locations/united-kingdom/bristol' => 'Bristol',
            '/locations/united-kingdom/manchester' => 'Manchester',
            '/locations/united-kingdom/kent/rochester' => 'Rochester',
            '/locations/united-kingdom/kent/chatham' => 'Chatham',
            '/locations/united-kingdom/kent/gillingham' => 'Gillingham',
            '/locations/united-kingdom/kent/canterbury' => 'Canterbury',
            '/locations/united-kingdom/kent/ashford' => 'Ashford',
        ];

        $available = array_flip($this->canonicalUrls());
        $links = [];

        foreach ($preferred as $path => $label) {
            $url = $this->publicUrl($path);
            if (!isset($available[$url])) {
                continue;
            }

            $links[] = [$label, $url];
        }

        return $links;
    }

    /**
     * @return array<int, array{0:string,1:string}>
     */
    private function aiCorporateLinks(): array
    {
        $preferred = [
            '/corporate' => 'Corporate home',
            '/corporate/wellbeing-workshops' => 'Wellbeing workshops',
            '/corporate/meditation' => 'Meditation for teams',
            '/corporate/breathwork' => 'Breathwork for teams',
            '/corporate/sound-bath' => 'Workplace sound baths',
            '/corporate/gift-vouchers' => 'Corporate gift vouchers',
            '/corporate/employee-rewards' => 'Employee rewards',
        ];

        $available = array_flip($this->canonicalUrls());
        $links = [];

        foreach ($preferred as $path => $label) {
            $url = $this->publicUrl($path);
            if (!isset($available[$url])) {
                continue;
            }

            $links[] = [$label, $url];
        }

        return $links;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function aiCanonicalUrlGroups(): array
    {
        $groups = [
            'Core pages' => [],
            'Therapies' => [],
            'Classes' => [],
            'Workshops' => [],
            'Events' => [],
            'Retreats' => [],
            'Online' => [],
            'Locations' => [],
            'Needs' => [],
            'Corporate wellbeing' => [],
            'Guides' => [],
        ];

        $corePagePaths = array_merge([
            '/',
            '/about',
            '/contact',
            '/help',
            '/help/faq',
            '/help/gift-cards',
            '/giftcards',
            '/partners',
            '/plan',
            '/reviews',
            '/safety-and-contraindications',
        ], array_map(
            static fn (array $entry): string => (string) parse_url((string) ($entry['loc'] ?? ''), PHP_URL_PATH),
            $this->buildLegalEntries()
        ));

        foreach ($this->canonicalUrls() as $url) {
            $path = $this->normalizeSitemapPath($url);

            if ($path === '/' || in_array($path, $corePagePaths, true)) {
                $groups['Core pages'][] = $url;
                continue;
            }

            if (str_starts_with($path, '/therapies/')) {
                $groups['Therapies'][] = $url;
                continue;
            }

            if (str_starts_with($path, '/classes/')) {
                $groups['Classes'][] = $url;
                continue;
            }

            if (str_starts_with($path, '/workshops/')) {
                $groups['Workshops'][] = $url;
                continue;
            }

            if (str_starts_with($path, '/events/')) {
                $groups['Events'][] = $url;
                continue;
            }

            if (str_starts_with($path, '/retreats/')) {
                $groups['Retreats'][] = $url;
                continue;
            }

            if (str_starts_with($path, '/online/')) {
                $groups['Online'][] = $url;
                continue;
            }

            if (str_starts_with($path, '/locations/')) {
                $groups['Locations'][] = $url;
                continue;
            }

            if (str_starts_with($path, '/needs/')) {
                $groups['Needs'][] = $url;
                continue;
            }

            if (str_starts_with($path, '/corporate')) {
                $groups['Corporate wellbeing'][] = $url;
                continue;
            }

            if (str_contains($path, '/guides/')) {
                $groups['Guides'][] = $url;
                continue;
            }
        }

        foreach ($groups as $label => $urls) {
            $groups[$label] = array_values(array_unique($urls));
        }

        return $groups;
    }

    /**
     * @param array<int, array{filename:string,segment:string,url:string,lastmod:string,count:int,xml:string}> $files
     * @return array<int, string>
     */
    private function submissionUrlsFromFiles(array $files): array
    {
        // Search Console submission is intentionally limited to the canonical
        // index; child files are discovered from its XML.
        return [$this->publicUrl('/sitemap.xml')];
    }
}
