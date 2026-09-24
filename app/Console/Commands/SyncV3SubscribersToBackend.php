<?php

namespace App\Console\Commands;

use App\Models\V3Subscriber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SyncV3SubscribersToBackend extends Command
{
    protected $signature = 'subscribers:sync-backend {--dry-run : Preview the records that would be sent}';

    protected $description = 'Mirror frontend V3 subscriber records into the backend subscriber list.';

    public function handle(): int
    {
        $backendUrl = rtrim((string) env('BACKEND_URL', env('VITE_BACKEND_URL', env('BACKEND_ASSET_URL', ''))), '/');
        if ($backendUrl === '') {
            $this->error('BACKEND_URL is not configured.');
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $sent = 0;

        V3Subscriber::query()
            ->orderBy('id')
            ->chunkById(200, function ($chunk) use ($backendUrl, $dryRun, &$sent) {
                foreach ($chunk as $subscriber) {
                    if (blank($subscriber->email)) {
                        $this->warn(sprintf('[SKIP] #%d missing email', $subscriber->id));
                        continue;
                    }

                    $isPractitioner = $this->isPractitioner($subscriber);
                    $payload = $this->buildPayload($subscriber, $isPractitioner);
                    $endpoint = $isPractitioner ? '/api/v3-subscribers/practitioner-interest' : '/api/v3-subscribers';

                    if ($dryRun) {
                        $this->line(sprintf(
                            '[DRY-RUN] #%d %s -> %s',
                            $subscriber->id,
                            $subscriber->email,
                            $endpoint
                        ));
                        $sent++;
                        continue;
                    }

                    Http::timeout(10)
                        ->acceptJson()
                        ->asJson()
                        ->post($backendUrl . $endpoint, $payload)
                        ->throw();

                    $this->info(sprintf('#%d synced: %s', $subscriber->id, $subscriber->email));
                    $sent++;
                }
            });

        $this->info("Synced {$sent} subscriber records.");

        return self::SUCCESS;
    }

    private function isPractitioner(V3Subscriber $subscriber): bool
    {
        return (bool) ($subscriber->offers_online
            || $subscriber->offers_in_person
            || $subscriber->in_person_locations);
    }

    private function buildPayload(V3Subscriber $subscriber, bool $isPractitioner): array
    {
        $displayName = $subscriber->name ?: trim(($subscriber->first_name ?? '') . ' ' . ($subscriber->last_name ?? ''));

        $payload = [
            'email' => $subscriber->email,
            'name' => $displayName !== '' ? $displayName : null,
            'first_name' => $subscriber->first_name,
            'last_name' => $subscriber->last_name,
            'business_name' => $subscriber->business_name,
            'source' => 'frontend:v3-subscribers',
            'status' => $subscriber->status ?: 'pending',
        ];

        if ($isPractitioner) {
            $sessionModes = [];
            if ($subscriber->offers_online) {
                $sessionModes[] = 'online';
            }
            if ($subscriber->offers_in_person) {
                $sessionModes[] = 'in_person';
            }

            $payload['session_modes'] = array_values(array_unique($sessionModes));
            $payload['in_person_area'] = $subscriber->in_person_locations ?: null;
        }

        return array_filter($payload, fn ($value) => !is_null($value) && $value !== '');
    }
}
