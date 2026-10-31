<?php

namespace App\Console\Commands;

use App\Services\MailService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CheckAnalyticsHealth extends Command
{
    protected $signature = 'analytics:health-check
        {--date= : GA4 reporting date in YYYY-MM-DD; defaults to two days ago}
        {--skip-ga4 : Check the production tag only}
        {--no-alert : Report failures without sending an alert email}';

    protected $description = 'Check the production GA4 runtime and alert on zero-session or zero-pageview days.';

    public function handle(): int
    {
        $measurementId = trim((string) config('analytics.measurement_id'));
        $issues = $this->checkProductionPages($measurementId);
        $metrics = null;
        $date = $this->reportingDate();

        if (! $this->option('skip-ga4')) {
            try {
                $metrics = $this->fetchDailyMetrics($date);
                if ($metrics['sessions'] === 0 || $metrics['screenPageViews'] === 0) {
                    $issues[] = sprintf(
                        'GA4 reported %d sessions and %d page views for %s.',
                        $metrics['sessions'],
                        $metrics['screenPageViews'],
                        $date
                    );
                }
            } catch (\Throwable $exception) {
                $issues[] = 'GA4 Data API check failed: '.$exception->getMessage();
            }
        }

        if ($issues === []) {
            $summary = $metrics
                ? sprintf('%s: %d sessions, %d page views.', $date, $metrics['sessions'], $metrics['screenPageViews'])
                : 'Production GA4 runtime is present on all core pages.';
            $this->info('Analytics health check passed. '.$summary);

            return self::SUCCESS;
        }

        foreach ($issues as $issue) {
            $this->error($issue);
        }

        $context = ['date' => $date, 'issues' => $issues, 'metrics' => $metrics];
        Log::critical('analytics.health_check_failed', $context);
        $this->sendAlert($context);

        return self::FAILURE;
    }

    /** @return array<int, string> */
    private function checkProductionPages(string $measurementId): array
    {
        $issues = [];
        if ($measurementId === '') {
            return ['GA4 measurement ID is not configured.'];
        }

        foreach ((array) config('analytics.health.core_urls', []) as $url) {
            $url = trim((string) $url);
            if ($url === '') {
                continue;
            }

            try {
                $response = Http::timeout(15)->retry(1, 250)->get($url);
            } catch (\Throwable $exception) {
                $issues[] = "{$url} could not be loaded: {$exception->getMessage()}";
                continue;
            }

            if (! $response->successful()) {
                $issues[] = "{$url} returned HTTP {$response->status()}.";
                continue;
            }

            $html = $response->body();
            $loader = 'www.googletagmanager.com/gtag/js?id='.$measurementId;
            $loaderCount = substr_count($html, $loader);
            $configCount = substr_count($html, "gtag('config',");
            $consentPosition = strpos($html, "gtag('consent', 'default'");
            $configPosition = strpos($html, "gtag('config',");

            if ($loaderCount !== 1) {
                $issues[] = "{$url} contains {$loaderCount} GA4 loader tags; expected exactly one.";
            }
            if ($configCount !== 1) {
                $issues[] = "{$url} contains {$configCount} GA4 config calls; expected exactly one.";
            }
            if ($consentPosition === false || $configPosition === false || $consentPosition > $configPosition) {
                $issues[] = "{$url} does not initialise consent before the GA4 config call.";
            }
        }

        return $issues;
    }

    /** @return array{sessions:int,screenPageViews:int} */
    private function fetchDailyMetrics(string $date): array
    {
        $propertyId = preg_replace('/\D+/', '', (string) config('analytics.health.property_id'));
        if ($propertyId === '') {
            throw new \RuntimeException('GOOGLE_ANALYTICS_PROPERTY_ID is not configured.');
        }

        [$clientId, $clientSecret, $refreshToken] = $this->oauthCredentials();
        if ($clientId === '' || $clientSecret === '' || $refreshToken === '') {
            throw new \RuntimeException('Google Analytics OAuth credentials are incomplete.');
        }

        $tokenResponse = Http::asForm()->timeout(20)->retry(1, 250)->post('https://oauth2.googleapis.com/token', [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);
        $this->assertSuccessful($tokenResponse, 'Google OAuth token refresh');

        $accessToken = trim((string) $tokenResponse->json('access_token'));
        if ($accessToken === '') {
            throw new \RuntimeException('Google OAuth token refresh returned no access token.');
        }

        $report = Http::withToken($accessToken)->timeout(25)->retry(1, 250)->post(
            "https://analyticsdata.googleapis.com/v1beta/properties/{$propertyId}:runReport",
            [
                'dateRanges' => [['startDate' => $date, 'endDate' => $date]],
                'metrics' => [['name' => 'sessions'], ['name' => 'screenPageViews']],
            ]
        );
        $this->assertSuccessful($report, 'GA4 daily report');

        return [
            'sessions' => (int) data_get($report->json(), 'rows.0.metricValues.0.value', 0),
            'screenPageViews' => (int) data_get($report->json(), 'rows.0.metricValues.1.value', 0),
        ];
    }

    /** @return array{0:string,1:string,2:string} */
    private function oauthCredentials(): array
    {
        $clientId = trim((string) config('analytics.health.client_id'));
        $clientSecret = trim((string) config('analytics.health.client_secret'));
        $refreshToken = trim((string) config('analytics.health.refresh_token'));
        $credentialsFile = trim((string) config('analytics.health.client_credentials_file'));

        if (($clientId === '' || $clientSecret === '') && $credentialsFile !== '' && File::isFile($credentialsFile)) {
            $data = json_decode((string) File::get($credentialsFile), true);
            $credentials = is_array($data) ? ($data['web'] ?? $data['installed'] ?? []) : [];
            $clientId = $clientId ?: trim((string) ($credentials['client_id'] ?? ''));
            $clientSecret = $clientSecret ?: trim((string) ($credentials['client_secret'] ?? ''));
        }

        return [$clientId, $clientSecret, $refreshToken];
    }

    private function reportingDate(): string
    {
        $timezone = (string) config('analytics.health.timezone', 'Europe/London');
        $date = trim((string) $this->option('date'));

        if ($date === '') {
            return CarbonImmutable::now($timezone)->subDays(2)->toDateString();
        }

        $parsed = CarbonImmutable::createFromFormat('!Y-m-d', $date, $timezone);
        if (! $parsed || $parsed->format('Y-m-d') !== $date) {
            throw new \InvalidArgumentException('The --date value must use YYYY-MM-DD.');
        }

        return $date;
    }

    private function assertSuccessful(Response $response, string $operation): void
    {
        if ($response->successful()) {
            return;
        }

        $message = trim((string) ($response->json('error.message') ?: $response->body()));
        throw new \RuntimeException("{$operation} failed with HTTP {$response->status()}: ".Str::limit($message, 300));
    }

    /** @param array<string, mixed> $context */
    private function sendAlert(array $context): void
    {
        $recipient = trim((string) config('analytics.health.alert_email'));
        if ($recipient === '' || $this->option('no-alert')) {
            return;
        }

        $fingerprint = sha1(json_encode($context['issues'] ?? []));
        if (! Cache::add("analytics-health-alert:{$fingerprint}", true, now()->addHours(20))) {
            return;
        }

        try {
            MailService::send(
                $recipient,
                'We Offer Wellness analytics health alert',
                'emails.analytics-health-alert',
                $context
            );
        } catch (\Throwable $exception) {
            Log::error('analytics.health_alert_delivery_failed', ['error' => $exception->getMessage()]);
        }
    }
}
