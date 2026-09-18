<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\V3Subscriber;
use App\Services\TransactionalMail;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class V3SubscriberController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'name' => 'nullable|string|max:255',
            'first_name' => 'nullable|string|max:120',
            'last_name' => 'nullable|string|max:120',
            'business_name' => 'nullable|string|max:255',
            'team_size' => 'nullable|string|max:80',
            'interest' => 'nullable|string|max:120',
            'notes' => 'nullable|string|max:500',
            'tags' => 'nullable|array',
            'tags.*' => 'nullable|string|max:80',
            'offers_online' => 'nullable|boolean',
            'offers_in_person' => 'nullable|boolean',
            'in_person_locations' => 'nullable|string|max:255',
            'practitioner_interest' => 'nullable|boolean',
            'landing_path' => 'nullable|string|max:2048',
            'referrer' => 'nullable|string',
            'session_token' => 'nullable|string|max:100',
            'timezone' => 'nullable|string|max:100',
            'locale' => 'nullable|string|max:20',
            'languages' => 'nullable|string|max:255',
            'platform' => 'nullable|string|max:120',
            'user_agent' => 'nullable|string',
            'device_memory' => 'nullable|string|max:20',
            'hardware_concurrency' => 'nullable|string|max:20',
            'screen_width' => 'nullable|integer',
            'screen_height' => 'nullable|integer',
            'geo_lat' => 'nullable|numeric',
            'geo_lng' => 'nullable|numeric',
            'geo_accuracy' => 'nullable|numeric',
            'session_started_at' => 'nullable|date',
            'session_duration_seconds' => 'nullable|integer|min:0',
        ]);

        $validator->after(function ($validator) use ($request) {
            $isPractitioner = $this->normalizeBoolean($request->input('practitioner_interest'));
            if (!$isPractitioner) {
                return;
            }

            $online = $this->normalizeBoolean($request->input('offers_online'));
            $inPerson = $this->normalizeBoolean($request->input('offers_in_person'));
            if (!$online && !$inPerson) {
                $validator->errors()->add('offers_online', 'Select at least one availability option.');
            }

            if ($inPerson) {
                $location = trim((string) $request->input('in_person_locations'));
                if ($location === '') {
                    $validator->errors()->add('in_person_locations', 'Share a general location for in-person sessions.');
                }
            }
        });

        $data = $validator->validate();
        $hasOffersOnline = array_key_exists('offers_online', $data);
        $offersOnline = $hasOffersOnline ? $this->normalizeBoolean($data['offers_online']) : null;
        $hasOffersInPerson = array_key_exists('offers_in_person', $data);
        $offersInPerson = $hasOffersInPerson ? $this->normalizeBoolean($data['offers_in_person']) : null;
        $hasLocationField = array_key_exists('in_person_locations', $data);
        $isPractitioner = array_key_exists('practitioner_interest', $data)
            ? $this->normalizeBoolean($data['practitioner_interest'])
            : null;

        if ($hasLocationField && !$offersInPerson) {
            $data['in_person_locations'] = null;
        }

        $data['practitioner_interest'] = $isPractitioner;
        if (empty($data['name']) && (!empty($data['first_name']) || !empty($data['last_name']))) {
            $data['name'] = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
        }

        $token = $data['session_token'] ?? null;
        $landingPath = $data['landing_path'] ?? null;
        $referrer = $data['referrer'] ?? null;
        $sessionStarted = isset($data['session_started_at']) ? Carbon::parse($data['session_started_at']) : null;
        $duration = isset($data['session_duration_seconds']) ? max(0, (int) $data['session_duration_seconds']) : null;
        $meta = $this->extractMeta($data, $request);
        unset($data['practitioner_interest']);

        $subscriber = null;
        if ($token) {
            $subscriber = V3Subscriber::where('session_token', $token)->first();
        }
        if (!$subscriber) {
            $subscriber = V3Subscriber::where('email', $data['email'])->orderByDesc('id')->first();
        }

        $isNew = false;
        if (!$subscriber) {
            $subscriber = new V3Subscriber();
            $isNew = true;
        }

        $subscriber->email = $data['email'];
        if (!empty($data['name'])) {
            $subscriber->name = $data['name'];
        }
        if (!empty($data['first_name'])) {
            $subscriber->first_name = $data['first_name'];
        }
        if (!empty($data['last_name'])) {
            $subscriber->last_name = $data['last_name'];
        }
        if (!empty($data['business_name'])) {
            $subscriber->business_name = $data['business_name'];
        }
        if (!empty($data['notes'])) {
            $subscriber->notes = $data['notes'];
        } elseif (!empty($data['team_size']) || !empty($data['interest'])) {
            $notesParts = array_filter([
                !empty($data['team_size']) ? 'Team size: ' . $data['team_size'] : null,
                !empty($data['interest']) ? 'Interest: ' . $data['interest'] : null,
            ]);
            $subscriber->notes = implode(' | ', $notesParts);
        }

        $normalizedTags = $this->normalizeTags($data['tags'] ?? []);
        if (!empty($normalizedTags)) {
            $subscriber->tags = $normalizedTags;
        }

        if ($hasOffersOnline) {
            $subscriber->offers_online = $offersOnline;
        }
        if ($hasOffersInPerson) {
            $subscriber->offers_in_person = $offersInPerson;
        }
        if ($hasLocationField) {
            $subscriber->in_person_locations = $data['in_person_locations'] ?? null;
        }

        if ($landingPath && ($isNew || !$subscriber->landing_path)) {
            $subscriber->landing_path = $landingPath;
        }
        if ($referrer && ($isNew || !$subscriber->referrer)) {
            $subscriber->referrer = $referrer;
        }

        if ($sessionStarted && ($isNew || !$subscriber->session_started_at)) {
            $subscriber->session_started_at = $sessionStarted;
        }
        if (!$subscriber->session_started_at) {
            $subscriber->session_started_at = now();
        }
        if ($duration !== null) {
            $subscriber->session_duration_seconds = max($subscriber->session_duration_seconds ?? 0, $duration);
        } elseif ($isNew && $subscriber->session_duration_seconds === null) {
            $subscriber->session_duration_seconds = 0;
        }

        $subscriber->last_seen_at = now();
        if (!empty($meta)) {
            $subscriber->fill($meta);
        }

        if ($token) {
            $subscriber->session_token = $token;
        } elseif ($subscriber->session_token) {
            $token = $subscriber->session_token;
        } else {
            $token = (string) Str::uuid();
            $subscriber->session_token = $token;
        }

        if (!$subscriber->status) {
            $subscriber->status = $subscriber->confirmed_at ? 'confirmed' : 'pending';
        }
        if (!$subscriber->confirmation_token) {
            $subscriber->confirmation_token = Str::random(64);
        }
        if (!$subscriber->manage_token) {
            $subscriber->manage_token = Str::random(64);
        }

        if ($subscriber->status === 'unsubscribed') {
            $subscriber->status = 'pending';
            $subscriber->confirmed_at = null;
            $subscriber->unsubscribed_at = null;
        }

        $subscriber->save();
        $this->syncBackendSubscriber(
            $subscriber,
            $this->buildBackendSubscriberPayload($subscriber, $isPractitioner === true, $data),
            $isPractitioner === true
        );

        $requiresConfirmation = !$subscriber->confirmed_at;
        $message = 'Check your email to confirm your subscription.';

        if ($requiresConfirmation) {
            $shouldSend = !$subscriber->confirmation_sent_at || $subscriber->confirmation_sent_at->lt(now()->subMinutes(10));
            if ($shouldSend) {
                $subscriber->confirmation_sent_at = now();
                $subscriber->save();
                TransactionalMail::subscriberConfirm($subscriber);
            }
        } else {
            $message = 'You’re all set — you’re already confirmed.';
        }

        if ($isPractitioner === true) {
            TransactionalMail::subscriberPractitionerInterest($subscriber);
        }

        return response()->json([
            'ok' => true,
            'session_token' => $subscriber->session_token,
            'status' => $subscriber->status,
            'requires_confirmation' => $requiresConfirmation,
            'message' => $message,
        ]);
    }

    public function track(Request $request)
    {
        $data = $request->validate([
            'landing_path' => 'nullable|string|max:2048',
            'referrer' => 'nullable|string',
            'session_token' => 'nullable|string|max:100',
            'timezone' => 'nullable|string|max:100',
            'locale' => 'nullable|string|max:20',
            'languages' => 'nullable|string|max:255',
            'platform' => 'nullable|string|max:120',
            'user_agent' => 'nullable|string',
            'device_memory' => 'nullable|string|max:20',
            'hardware_concurrency' => 'nullable|string|max:20',
            'screen_width' => 'nullable|integer',
            'screen_height' => 'nullable|integer',
            'geo_lat' => 'nullable|numeric',
            'geo_lng' => 'nullable|numeric',
            'geo_accuracy' => 'nullable|numeric',
            'session_started_at' => 'nullable|date',
            'session_duration_seconds' => 'nullable|integer|min:0',
        ]);

        $token = $data['session_token'] ?? null;
        $landingPath = $data['landing_path'] ?? null;
        $referrer = $data['referrer'] ?? null;
        $sessionStarted = isset($data['session_started_at']) ? Carbon::parse($data['session_started_at']) : null;
        $duration = isset($data['session_duration_seconds']) ? max(0, (int) $data['session_duration_seconds']) : null;
        $meta = $this->extractMeta($data, $request);

        if ($token) {
            $existing = V3Subscriber::where('session_token', $token)->first();
            if ($existing) {
                $updated = false;
                if ($landingPath && !$existing->landing_path) {
                    $existing->landing_path = $landingPath;
                    $updated = true;
                }
                if ($referrer && !$existing->referrer) {
                    $existing->referrer = $referrer;
                    $updated = true;
                }
                if ($sessionStarted && !$existing->session_started_at) {
                    $existing->session_started_at = $sessionStarted;
                    $updated = true;
                }
                if ($duration !== null) {
                    $existing->session_duration_seconds = max($existing->session_duration_seconds ?? 0, $duration);
                    $updated = true;
                }
                $existing->last_seen_at = now();
                $existing->fill($meta);
                if ($updated || !empty($meta)) {
                    $existing->save();
                }

                return response()->json(['session_token' => $existing->session_token]);
            }
        }

        $token = (string) Str::uuid();
        V3Subscriber::create(array_merge(array_filter([
            'session_token' => $token,
            'landing_path' => $landingPath,
            'referrer' => $referrer,
            'session_started_at' => $sessionStarted ?? now(),
            'last_seen_at' => now(),
            'session_duration_seconds' => $duration ?? 0,
        ], fn($value) => !is_null($value) && $value !== ''), $meta));

        return response()->json(['session_token' => $token]);
    }

    protected function extractMeta(array $data, Request $request): array
    {
        $meta = [
            'timezone' => $data['timezone'] ?? null,
            'locale' => $data['locale'] ?? null,
            'languages' => $data['languages'] ?? null,
            'platform' => $data['platform'] ?? null,
            'user_agent' => $data['user_agent'] ?? $request->userAgent(),
            'device_memory' => $data['device_memory'] ?? null,
            'hardware_concurrency' => $data['hardware_concurrency'] ?? null,
            'screen_width' => $data['screen_width'] ?? null,
            'screen_height' => $data['screen_height'] ?? null,
            'geo_lat' => $data['geo_lat'] ?? null,
            'geo_lng' => $data['geo_lng'] ?? null,
            'geo_accuracy' => $data['geo_accuracy'] ?? null,
        ];

        return array_filter($meta, fn ($value) => !is_null($value) && $value !== '');
    }

    protected function buildBackendSubscriberPayload(V3Subscriber $subscriber, bool $isPractitioner, array $data): array
    {
        $displayName = $subscriber->name ?: trim(($subscriber->first_name ?? '') . ' ' . ($subscriber->last_name ?? ''));

        $payload = [
            'email' => $subscriber->email,
            'behaviour_visitor_public_id' => request()->cookie(env('BEHAVIOUR_VISITOR_COOKIE', 'wow_visitor_id')),
            'name' => $displayName !== '' ? $displayName : null,
            'first_name' => $subscriber->first_name,
            'last_name' => $subscriber->last_name,
            'business_name' => $subscriber->business_name,
            'notes' => $subscriber->notes,
            'tags' => $subscriber->tags ?? null,
            'source' => $data['landing_path'] ?? 'frontend:v3-subscribers',
            'status' => $subscriber->status ?: 'pending',
        ];

        if ($isPractitioner) {
            $sessionModes = [];
            if ($this->normalizeBoolean($data['offers_online'] ?? null)) {
                $sessionModes[] = 'online';
            }
            if ($this->normalizeBoolean($data['offers_in_person'] ?? null)) {
                $sessionModes[] = 'in_person';
            }

            $payload = array_merge($payload, [
                'business_name' => $subscriber->business_name ?: ($data['business_name'] ?? null),
                'session_modes' => array_values(array_unique($sessionModes)),
                'in_person_area' => $subscriber->in_person_locations ?: ($data['in_person_locations'] ?? null),
            ]);
        }

        if (!empty($data['notes']) && empty($payload['notes'])) {
            $payload['notes'] = trim((string) $data['notes']);
        } elseif (empty($payload['notes']) && (!empty($data['team_size']) || !empty($data['interest']))) {
            $notesParts = array_filter([
                !empty($data['team_size']) ? 'Team size: ' . $data['team_size'] : null,
                !empty($data['interest']) ? 'Interest: ' . $data['interest'] : null,
            ]);
            $payload['notes'] = implode(' | ', $notesParts);
        }

        if (!empty($data['tags'])) {
            $payload['tags'] = $this->normalizeTags($data['tags']);
        } elseif (!empty($subscriber->tags)) {
            $payload['tags'] = $subscriber->tags;
        }

        return array_filter($payload, fn ($value) => !is_null($value) && $value !== '');
    }

    protected function normalizeTags(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/[,\s]+/', $value) ?: [];
        }

        if (!is_array($value)) {
            return [];
        }

        return collect($value)
            ->map(fn ($tag) => trim((string) $tag))
            ->filter()
            ->map(fn ($tag) => strtolower(preg_replace('/[^a-z0-9]+/', '_', $tag)))
            ->filter()
            ->values()
            ->all();
    }

    protected function syncBackendSubscriber(V3Subscriber $subscriber, array $payload, bool $isPractitioner): void
    {
        $backendUrl = rtrim((string) env(
            'BACKEND_URL',
            'https://studio.weofferwellness.co.uk'
        ), '/');
        if ($backendUrl === '') {
            return;
        }

        $endpoint = $isPractitioner ? '/api/v3-subscribers/practitioner-interest' : '/api/v3-subscribers';

        try {
            $http = Http::timeout(5)
                ->acceptJson()
                ->asJson()
                ->withHeaders([
                    'Origin' => 'https://www.weofferwellness.co.uk',
                    'Referer' => 'https://www.weofferwellness.co.uk/',
                ]);
            if (request()->headers->has('cookie')) {
                $http = $http->withHeaders(['Cookie' => request()->headers->get('cookie')]);
            }
            $response = $http->post($backendUrl . $endpoint, $payload);
            if ($response->failed()) {
                logger()->error('subscriber.backend_sync_rejected', [
                    'email' => $subscriber->email,
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'body' => $response->json() ?: $response->body(),
                ]);

                throw new \RuntimeException('The mailing list could not be updated. Please try again.');
            }
        } catch (\Throwable $e) {
            logger()->warning('subscriber.backend_sync_failed', [
                'email' => $subscriber->email,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function normalizeBoolean($value): ?bool
    {
        if (is_null($value)) {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return null;
            }
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
}
