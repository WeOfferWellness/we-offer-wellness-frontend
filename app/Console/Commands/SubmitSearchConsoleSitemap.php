<?php

namespace App\Console\Commands;

use App\Services\IndexNowService;
use App\Services\SitemapService;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class SubmitSearchConsoleSitemap extends Command
{
    private const REQUIRED_SEGMENTS = [
        'static',
        'schedules',
        'types',
        'modalities',
        'near-me',
        'collections',
        'offerings',
        'locations',
        'online',
        'by-need',
        'practitioners',
        'guides',
    ];

    private const OPTIONAL_EMPTY_SEGMENTS = [];

    protected $signature = 'search-console:submit-sitemap
        {--property= : Search Console property URL or sc-domain property}
        {--sitemap= : Sitemap URL to submit}
        {--sitemaps= : Comma-separated sitemap URLs to submit}
        {--client-id= : Google OAuth client ID}
        {--client-secret= : Google OAuth client secret}
        {--refresh-token= : Google OAuth refresh token}';

    protected $description = 'Submit the canonical generated sitemap index to Google Search Console via the Sitemaps API using OAuth.';

    public function handle(): int
    {
        @set_time_limit(0);
        if (function_exists('ini_set')) {
            @ini_set('max_execution_time', '0');
        }

        $this->forcePublicSiteUrl();

        try {
            $sitemapService = app(SitemapService::class);
            $propertyUrl = trim((string) ($this->option('property') ?: config('services.search_console.property_url', '')));
            $sitemapUrls = $this->resolveSitemapUrls($sitemapService);
            $credentials = $this->loadOAuthCredentials();

            if ($propertyUrl === '') {
                $this->error('Missing Search Console property URL. Set GOOGLE_SEARCH_CONSOLE_PROPERTY_URL.');
                return self::FAILURE;
            }

            if ($sitemapUrls === []) {
                $this->error('Missing sitemap URL(s). Run php artisan sitemaps:generate or set GOOGLE_SEARCH_CONSOLE_SITEMAP_URLS / GOOGLE_SEARCH_CONSOLE_SITEMAP_URL.');
                return self::FAILURE;
            }

            if ($credentials === null) {
                $this->error('Missing Google OAuth credentials. Set GOOGLE_SEARCH_CONSOLE_CLIENT_ID, GOOGLE_SEARCH_CONSOLE_CLIENT_SECRET, and GOOGLE_SEARCH_CONSOLE_REFRESH_TOKEN.');
                $this->line('Use `php artisan search-console:oauth-url` and `php artisan search-console:oauth-exchange {code}` to create a refresh token.');
                return self::FAILURE;
            }

            $manifest = $this->loadManifest($sitemapService);
            if ($manifest === null) {
                return self::FAILURE;
            }

            if (!$this->validateManifest($manifest)) {
                return self::FAILURE;
            }

            [$clientId, $clientSecret, $refreshToken] = $credentials;
            $token = $this->fetchAccessToken($clientId, $clientSecret, $refreshToken);
            $success = true;

            foreach ($sitemapUrls as $sitemapUrl) {
                $this->verifySitemapReachable($sitemapUrl);

                $response = $this->submitSitemap($propertyUrl, $sitemapUrl, $token);

                if (!$response->successful()) {
                    $success = false;
                    $this->error("Search Console rejected the sitemap submission for {$sitemapUrl}.");
                    $this->error($this->describeResponse($response));
                    continue;
                }

                $this->info("Submitted {$sitemapUrl} to Search Console for {$propertyUrl}.");
            }

            if (! $this->submitIndexNowUrls($sitemapService, app(IndexNowService::class))) {
                $success = false;
            }

            return $success ? self::SUCCESS : self::FAILURE;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }

    private function forcePublicSiteUrl(): void
    {
        $baseUrl = rtrim((string) config('services.public_site_url', 'https://www.weofferwellness.co.uk'), '/');

        if ($baseUrl === '') {
            $baseUrl = 'https://www.weofferwellness.co.uk';
        }

        URL::forceRootUrl($baseUrl);
        URL::forceScheme('https');
    }

    /**
     * @return array<int, string>
     */
    private function resolveSitemapUrls(SitemapService $service): array
    {
        $raw = trim((string) $this->option('sitemaps'));

        if ($raw === '') {
            $single = trim((string) $this->option('sitemap'));

            if ($single !== '') {
                $raw = $single;
            }
        }

        if ($raw === '') {
            return $service->submissionUrls();
        }

        return collect(explode(',', $raw))
            ->map(fn (string $url): string => trim($url))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function loadManifest(SitemapService $service): ?array
    {
        $path = $service->manifestPath();

        if (!File::isFile($path)) {
            $this->error('Missing sitemap manifest. Run php artisan sitemaps:generate first.');
            return null;
        }

        $decoded = json_decode((string) File::get($path), true);
        if (!is_array($decoded)) {
            $this->error('Unable to parse the sitemap manifest. Run php artisan sitemaps:generate again.');
            return null;
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $manifest
     */
    private function validateManifest(array $manifest): bool
    {
        $sitemaps = array_values(array_filter((array) data_get($manifest, 'sitemaps', []), 'is_array'));
        $fileCount = count($sitemaps);
        $submissionUrls = array_values(array_filter(array_map(
            static fn ($url): string => trim((string) $url),
            (array) data_get($manifest, 'submission_urls', [])
        )));
        $totalUrls = (int) data_get($manifest, 'total_urls', 0);

        if ($fileCount < count(self::REQUIRED_SEGMENTS)) {
            $this->error(sprintf(
                'Critical sitemap failure: expected at least %d sitemap files but found %d.',
                count(self::REQUIRED_SEGMENTS),
                $fileCount
            ));
            return false;
        }

        $segments = [];
        foreach ($sitemaps as $entry) {
            $name = trim((string) data_get($entry, 'name', ''));
            if ($name !== '') {
                $segments[$name] = (int) data_get($entry, 'count', 0);
            }
        }

        $missing = [];
        $empty = [];
        foreach (self::REQUIRED_SEGMENTS as $segment) {
            if (!array_key_exists($segment, $segments)) {
                $missing[] = $segment;
                continue;
            }

            if ((int) ($segments[$segment] ?? 0) <= 0 && ! in_array($segment, self::OPTIONAL_EMPTY_SEGMENTS, true)) {
                $empty[] = $segment;
            }
        }

        if ($missing !== []) {
            $this->error('Critical sitemap failure: missing required segment(s): ' . implode(', ', $missing));
            return false;
        }

        if ($empty !== []) {
            $this->error('Critical sitemap failure: empty required segment(s): ' . implode(', ', $empty));
            return false;
        }

        $indexUrl = trim((string) data_get($manifest, 'index_url', ''));
        if ($indexUrl === '' || ! in_array($indexUrl, $submissionUrls, true)) {
            $this->error('Critical sitemap failure: canonical sitemap index is missing from the submission URL list.');
            return false;
        }

        if ($totalUrls <= 0) {
            $this->error('Critical sitemap failure: the sitemap manifest contains no URLs.');
            return false;
        }

        $this->info(sprintf(
            'Validated sitemap manifest: %d file(s), %d total URL(s).',
            $fileCount,
            $totalUrls
        ));

        return true;
    }

    /**
     * @return array{0:string,1:string,2:string}|null
     */
    private function loadOAuthCredentials(): ?array
    {
        $clientId = trim((string) ($this->option('client-id') ?: config('services.search_console.client_id', '')));
        $clientSecret = trim((string) ($this->option('client-secret') ?: config('services.search_console.client_secret', '')));
        $refreshToken = trim((string) ($this->option('refresh-token') ?: config('services.search_console.refresh_token', '')));
        $credentialsFile = trim((string) config('services.search_console.client_credentials_file', ''));

        if (($clientId === '' || $clientSecret === '') && $credentialsFile !== '' && File::isFile($credentialsFile)) {
            $data = json_decode((string) File::get($credentialsFile), true);

            if (is_array($data)) {
                $clientId = $clientId !== '' ? $clientId : trim((string) ($data['web']['client_id'] ?? $data['installed']['client_id'] ?? ''));
                $clientSecret = $clientSecret !== '' ? $clientSecret : trim((string) ($data['web']['client_secret'] ?? $data['installed']['client_secret'] ?? ''));
            }
        }

        if ($clientId === '' || $clientSecret === '' || $refreshToken === '') {
            return null;
        }

        return [$clientId, $clientSecret, $refreshToken];
    }

    private function submitIndexNowUrls(SitemapService $service, IndexNowService $indexNow): bool
    {
        $urls = $service->canonicalUrls();

        if ($urls === []) {
            $this->warn('IndexNow skipped: there were no canonical URLs to submit.');
            return true;
        }

        $host = trim($indexNow->host());
        $key = trim($indexNow->key());
        $keyLocation = trim($indexNow->keyLocation());
        $endpoint = trim($indexNow->endpoint());

        if ($host === '' || $key === '' || $endpoint === '') {
            $this->error('IndexNow submission skipped: missing host, key, or endpoint configuration.');
            return false;
        }

        $success = true;
        $chunks = array_chunk($urls, 10000);

        foreach ($chunks as $index => $chunk) {
            try {
                $payload = [
                    'host' => $host,
                    'key' => $key,
                    'urlList' => array_values($chunk),
                ];

                if ($keyLocation !== '') {
                    $payload['keyLocation'] = $keyLocation;
                }

                $response = Http::timeout(30)->acceptJson()->post($endpoint, $payload);

                if ($response->successful()) {
                    $this->info(sprintf(
                        'Submitted IndexNow batch %d/%d with %d URL(s) to %s.',
                        $index + 1,
                        count($chunks),
                        count($chunk),
                        $endpoint
                    ));
                    continue;
                }

                $success = false;
                $this->error(sprintf(
                    'IndexNow rejected batch %d/%d with HTTP %d.',
                    $index + 1,
                    count($chunks),
                    $response->status()
                ));
                $this->error($this->describeResponse($response));
            } catch (\Throwable $e) {
                $success = false;
                $this->error(sprintf(
                    'IndexNow failed for batch %d/%d: %s',
                    $index + 1,
                    count($chunks),
                    $e->getMessage()
                ));
            }
        }

        return $success;
    }

    private function verifySitemapReachable(string $sitemapUrl): void
    {
        $response = Http::timeout(20)->retry(2, 250)->accept('application/xml')->get($sitemapUrl);

        if (!$response->successful()) {
            throw new \RuntimeException("The sitemap URL is not reachable: {$sitemapUrl}. " . $this->describeResponse($response));
        }
    }

    private function fetchAccessToken(string $clientId, string $clientSecret, string $refreshToken): string
    {
        $response = Http::asForm()
            ->timeout(20)
            ->retry(2, 250)
            ->post('https://oauth2.googleapis.com/token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ]);

        if (!$response->successful() || empty($response->json('access_token'))) {
            throw new \RuntimeException('Unable to obtain a Google OAuth access token. ' . $this->describeResponse($response));
        }

        return (string) $response->json('access_token');
    }

    private function submitSitemap(string $propertyUrl, string $sitemapUrl, string $token): Response
    {
        $endpoint = sprintf(
            'https://www.googleapis.com/webmasters/v3/sites/%s/sitemaps/%s',
            rawurlencode($propertyUrl),
            rawurlencode($sitemapUrl),
        );

        return Http::withToken($token)
            ->acceptJson()
            ->withBody('', 'text/plain')
            ->timeout(20)
            ->retry(2, 250)
            ->send('PUT', $endpoint);
    }

    private function describeResponse(Response $response): string
    {
        $body = trim((string) $response->body());

        if ($body === '') {
            return sprintf('HTTP %d with an empty response body.', $response->status());
        }

        return sprintf('HTTP %d: %s', $response->status(), Str::limit($body, 500));
    }
}
