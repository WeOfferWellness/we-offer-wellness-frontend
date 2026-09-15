<?php

namespace App\Console\Commands;

use DOMDocument;
use DOMXPath;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ValidateSitemaps extends Command
{
    protected $signature = 'sitemaps:validate
        {--skip-http : Validate local XML and URL policy without fetching page URLs}
        {--base= : Override the public sitemap base URL}';

    protected $description = 'Validate the canonical sitemap index and child URLs before publication/submission.';

    private array $seenUrls = [];
    private int $invalid = 0;
    private int $checked = 0;

    public function handle(): int
    {
        $base = rtrim((string) ($this->option('base') ?: config('services.public_site_url', 'https://www.weofferwellness.co.uk')), '/');
        $indexPath = public_path('sitemap.xml');
        if (! is_file($indexPath)) {
            $this->error('Missing canonical sitemap index: ' . $indexPath);
            return self::FAILURE;
        }

        $index = $this->parseXml((string) file_get_contents($indexPath), $indexPath);
        if (! $index) return self::FAILURE;

        $xpath = new DOMXPath($index);
        $xpath->registerNamespace('sm', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $children = [];
        foreach ($xpath->query('//sm:sitemap/sm:loc') ?: [] as $node) {
            $url = trim((string) $node->textContent);
            if (! $this->validAbsoluteUrl($url)) {
                $this->recordFailure('Invalid child sitemap URL: ' . $url);
                continue;
            }
            $children[] = $url;
        }

        if ($children === []) {
            $this->recordFailure('Canonical sitemap index contains no child sitemaps.');
        }

        foreach ($children as $childUrl) {
            $path = parse_url($childUrl, PHP_URL_PATH) ?: '';
            $local = public_path(ltrim($path, '/'));
            if (! is_file($local)) {
                $this->recordFailure('Child sitemap is not present locally: ' . $childUrl);
                continue;
            }
            $document = $this->parseXml((string) file_get_contents($local), $local);
            if (! $document) continue;
            $childXPath = new DOMXPath($document);
            $childXPath->registerNamespace('sm', 'http://www.sitemaps.org/schemas/sitemap/0.9');
            foreach ($childXPath->query('//sm:url/sm:loc') ?: [] as $node) {
                $url = trim((string) $node->textContent);
                $this->validatePageUrl($url, $base);
            }
        }

        $this->line(sprintf('Validated %d URL(s), %d critical failure(s).', $this->checked, $this->invalid));
        return $this->invalid > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function validatePageUrl(string $url, string $base): void
    {
        $this->checked++;
        $path = (string) (parse_url($url, PHP_URL_PATH) ?: '');
        if (! $this->validAbsoluteUrl($url) || ! Str::startsWith($url, $base . '/')) {
            $this->recordFailure('URL is not an absolute URL on the public host: ' . $url);
            return;
        }
        if (isset($this->seenUrls[$url])) $this->recordFailure('Duplicate URL: ' . $url);
        $this->seenUrls[$url] = true;
        if (str_contains($url, '?') || str_contains($url, '#') || preg_match('~(?:^|/)(?:null|undefined)(?:/|$)~i', $path)) {
            $this->recordFailure('Malformed/query/null URL: ' . $url);
        }
        foreach (['/search', '/cart', '/checkout', '/account', '/admin', '/dashboard', '/api/'] as $blocked) {
            if ($path === $blocked || Str::startsWith($path, $blocked . '/')) $this->recordFailure('Utility URL in sitemap: ' . $url);
        }

        if ($this->option('skip-http')) return;

        try {
            $response = Http::timeout(8)->withHeaders(['Accept' => 'text/html'])->get($url);
            if ($response->redirect() || $response->failed()) {
                $this->recordFailure(sprintf('URL returned %d/redirect: %s', $response->status(), $url));
                return;
            }
            $html = (string) $response->body();
            if (preg_match('~<meta[^>]+name=["\']robots["\'][^>]+content=["\'][^"\']*noindex~i', $html)) {
                $this->recordFailure('noindex URL in sitemap: ' . $url);
            }
            if (preg_match('~<link[^>]+rel=["\'][^"\']*canonical[^"\']*["\'][^>]+href=["\']([^"\']+)["\']~i', $html, $match)
                && rtrim($match[1], '/') !== rtrim($url, '/')) {
                $this->recordFailure('Canonical mismatch: ' . $url . ' -> ' . $match[1]);
            }
        } catch (\Throwable $exception) {
            $this->recordFailure('HTTP validation failed for ' . $url . ': ' . $exception->getMessage());
        }
    }

    private function parseXml(string $contents, string $source): ?DOMDocument
    {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $ok = $document->loadXML($contents, LIBXML_NONET | LIBXML_NOBLANKS);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (! $ok) {
            $this->recordFailure('Invalid XML in ' . $source . ': ' . trim((string) ($errors[0]->message ?? 'unknown error')));
            return null;
        }
        $root = $document->documentElement?->localName;
        if (! in_array($root, ['sitemapindex', 'urlset'], true)) {
            $this->recordFailure('Unexpected sitemap root in ' . $source);
            return null;
        }
        return $document;
    }

    private function validAbsoluteUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true);
    }

    private function recordFailure(string $message): void
    {
        $this->invalid++;
        $this->error($message);
    }
}
