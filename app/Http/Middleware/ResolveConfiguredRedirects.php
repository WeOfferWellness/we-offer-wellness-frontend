<?php

namespace App\Http\Middleware;

use App\Models\PageRedirect;
use App\Models\Platform;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class ResolveConfiguredRedirects
{
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

    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        static $hasRedirectTable = null;
        if ($hasRedirectTable === null) {
            $hasRedirectTable = Schema::hasTable((new PageRedirect())->getTable());
        }

        if (! $hasRedirectTable) {
            return $next($request);
        }

        $platform = $this->resolvePlatformForHost((string) $request->getHost());
        if ($platform === null) {
            return $next($request);
        }

        $path = '/'.ltrim((string) $request->path(), '/');
        if ($path === '//') {
            $path = '/';
        }

        // /collections is now resolved dynamically by CollectionController.
        // That controller renders a live Backend collection when one exists,
        // then deliberately falls back to the legacy collection redirect map
        // for taxonomy/type aliases that are not collections. Skipping the
        // historical configured redirects here keeps the exact old aliases
        // working without preventing a mapped collection from becoming live.
        if ($path === '/collections' || preg_match('#^/collections/[^/]+/?$#i', $path) === 1) {
            return $next($request);
        }

        $redirects = PageRedirect::query()
            ->where('platform_id', $platform->id)
            ->when(Schema::hasColumn((new PageRedirect())->getTable(), 'is_active'), fn ($query) => $query->where('is_active', true))
            ->orderBy('id')
            ->get();

        foreach ($redirects as $redirect) {
            $target = $this->matchRedirect($path, (string) $redirect->from_path, $this->normalizeTarget((string) $redirect->to_path, $request));

            if ($target !== null && $target !== $path) {
                $target = $this->appendQueryString($target, $request, (bool) ($redirect->preserve_query ?? true));
                $status = (int) ($redirect->http_code ?? 301);
                $response = $this->isAbsoluteUrl($target)
                    ? redirect()->away($target, $status)
                    : redirect()->to($target, $status);
                $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');

                try {
                    if (Schema::hasColumn((new PageRedirect())->getTable(), 'hit_count')) {
                        $redirect->increment('hit_count');
                    }
                    if (Schema::hasColumn((new PageRedirect())->getTable(), 'last_hit_at')) {
                        $redirect->forceFill(['last_hit_at' => now()])->save();
                    }
                } catch (\Throwable $e) {
                    // Redirects must stay fast; ignore tracking errors.
                }

                return $response;
            }
        }

        return $next($request);
    }

    private function resolvePlatformForHost(string $host): ?Platform
    {
        $host = strtolower(trim($host));
        if ($host === '') {
            return null;
        }

        $platformName = match (true) {
            str_contains($host, 'studio.weofferwellness.co.uk') => 'WOW Studio',
            str_contains($host, 'times.weofferwellness.co.uk') => 'Mindful Times',
            str_contains($host, 'www.weofferwellness.co.uk'),
            str_contains($host, 'weofferwellness.co.uk') => 'WOW Store',
            default => null,
        };

        if ($platformName === null) {
            return null;
        }

        return Platform::query()->where('name', $platformName)->first();
    }

    private function matchRedirect(string $currentPath, string $fromPath, string $toPath): ?string
    {
        $fromPath = $this->normalizePath($fromPath);

        if (! str_contains($fromPath, '{')) {
            return $currentPath === $fromPath ? $toPath : null;
        }

        $pattern = preg_quote($fromPath, '#');
        $pattern = preg_replace('#\\\\\\{([A-Za-z0-9_]+)\\\\\\}#', '(?P<$1>[^/]+)', $pattern);
        if ($pattern === null) {
            return null;
        }

        if (! preg_match('#^'.$pattern.'$#', $currentPath, $matches)) {
            return null;
        }

        if (isset($matches['city']) && ! in_array(strtolower((string) $matches['city']), self::LEGACY_CITIES, true)) {
            return null;
        }

        if (isset($matches['type']) && ! in_array(strtolower((string) $matches['type']), self::LEGACY_TYPES, true)) {
            return null;
        }

        $target = $toPath;
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $target = str_replace('{'.$key.'}', $value, $target);
            }
        }

        if ($target === $currentPath) {
            return null;
        }

        return $target;
    }

    private function normalizePath(string $path): string
    {
        $path = trim(rawurldecode($path));

        if ($path === '') {
            return '/';
        }

        return str_starts_with($path, '/') ? $path : '/'.$path;
    }

    private function appendQueryString(string $target, Request $request, bool $preserveQuery): string
    {
        if (! $preserveQuery) {
            return $target;
        }

        $query = trim((string) $request->getQueryString());
        if ($query === '') {
            return $target;
        }

        return $target . (str_contains($target, '?') ? '&' : '?') . $query;
    }

    private function normalizeTarget(string $target, Request $request): string
    {
        $target = trim(rawurldecode($target));

        if ($target === '') {
            return '/';
        }

        $parts = parse_url($target);
        if (is_array($parts) && isset($parts['scheme']) && isset($parts['host'])) {
            return $target;
        }

        if (! str_starts_with($target, '/')) {
            $target = '/' . $target;
        }

        return $target;
    }

    private function isAbsoluteUrl(string $target): bool
    {
        $parts = parse_url($target);

        return is_array($parts) && isset($parts['scheme'], $parts['host']);
    }
}
