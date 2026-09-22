<?php

namespace App\Console\Commands;

use App\Services\GuideRegistryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExportGuidesForBackend extends Command
{
    protected $signature = 'guides:export-backend {--path= : JSON output path}';

    protected $description = 'Export published Frontend guides for the Backend guide library importer.';

    public function handle(GuideRegistryService $registry): int
    {
        $path = $this->option('path') ?: storage_path('app/guide-pages-import.json');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode([
            'generated_at' => now()->toIso8601String(),
            'guides' => $registry->backendImportRecords(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->info(sprintf('Exported %d published guides to %s.', count($registry->backendImportRecords()), $path));
        return self::SUCCESS;
    }
}
