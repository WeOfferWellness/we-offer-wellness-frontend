<?php

namespace App\Console\Commands;

use App\Services\SitemapService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;

class GenerateSitemaps extends Command
{
    protected $signature = 'sitemaps:generate
        {--output-dir= : Optional output directory override}';

    protected $description = 'Generate the sitemap index, child sitemap files, and Search Console manifest.';

    public function handle(SitemapService $service): int
    {
        @set_time_limit(0);
        if (function_exists('ini_set')) {
            @ini_set('max_execution_time', '0');
        }

        $this->forcePublicSiteUrl();

        $previousTotal = 0;
        $previousFileCount = 0;
        $manifestPath = $service->manifestPath();
        if (File::isFile($manifestPath)) {
            $decoded = json_decode((string) File::get($manifestPath), true);
            if (is_array($decoded)) {
                $previousTotal = (int) ($decoded['total_urls'] ?? 0);
                $previousFileCount = count((array) ($decoded['sitemaps'] ?? []));
            }
        }

        $outputDir = trim((string) $this->option('output-dir'));
        $result = $service->buildAndWriteAll($outputDir !== '' ? $outputDir : null);

        $files = (array) ($result['files'] ?? []);
        $aiFiles = (array) ($result['ai_files'] ?? []);
        $manifest = (array) ($result['manifest'] ?? []);
        $submissionUrls = (array) data_get($manifest, 'submission_urls', []);
        $totalUrls = (int) data_get($manifest, 'total_urls', 0);
        $fileCount = (int) data_get($manifest, 'file_count', count($files));
        $aiFileCount = count($aiFiles);

        $this->info(sprintf('Generated %d sitemap segment file(s).', count($files)));
        $this->info(sprintf('Generated %d AI guide file(s).', $aiFileCount));
        $this->line(sprintf('Total sitemap URLs: %d', $totalUrls));
        $this->line(sprintf('Search Console submission URLs: %d', count($submissionUrls)));

        foreach ($files as $file) {
            $this->line(sprintf(
                '- %s (%d URLs) -> %s',
                (string) ($file['filename'] ?? ''),
                (int) ($file['count'] ?? 0),
                (string) ($file['url'] ?? '')
            ));
        }

        foreach ($aiFiles as $file) {
            $this->line(sprintf(
                '- %s (%d bytes) -> %s',
                (string) ($file['filename'] ?? ''),
                (int) ($file['bytes'] ?? 0),
                (string) ($file['url'] ?? '')
            ));
        }

        $validation = $this->call('sitemaps:validate', ['--skip-http' => true]);
        if ($validation !== self::SUCCESS) {
            $this->error('Generated sitemap failed structural validation; Search Console submission must not proceed.');
            return self::FAILURE;
        }

        if ($fileCount < 10) {
            $this->warn(sprintf('Critical: only %d sitemap file(s) were generated; expected at least 10.', $fileCount));
        }

        if ($previousTotal > 0 && $totalUrls > 0 && $totalUrls < (int) floor($previousTotal * 0.7)) {
            $this->warn(sprintf(
                'Critical: sitemap URL count dropped by more than 30%% from %d to %d.',
                $previousTotal,
                $totalUrls
            ));
        }

        if ($previousFileCount > 0 && $fileCount < (int) floor($previousFileCount * 0.7)) {
            $this->warn(sprintf(
                'Critical: sitemap file count dropped by more than 30%% from %d to %d.',
                $previousFileCount,
                $fileCount
            ));
        }

        return self::SUCCESS;
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
}
