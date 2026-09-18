<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\ArticleController as ApiArticleController;
use App\Http\Controllers\Api\ProductController as ApiProductController;
use App\Models\Product;
use App\Models\V3Subscriber;
use App\Services\TransactionalMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class SubscriberController extends Controller
{
    public function confirm(string $token): View
    {
        $subscriber = V3Subscriber::where('confirmation_token', $token)->first();
        if (!$subscriber) {
            return $this->messageView('Link expired', 'That confirmation link is no longer valid. Tap subscribe on the site to get a fresh one.');
        }

        $wasConfirmed = (bool) $subscriber->confirmed_at;
        $subscriber->status = 'confirmed';
        $subscriber->confirmed_at = now();
        $subscriber->unsubscribed_at = null;
        $subscriber->confirmation_token = Str::random(64);
        if (!$subscriber->manage_token) {
            $subscriber->manage_token = Str::random(64);
        }
        $subscriber->save();

        $this->syncBackendSubscriber($subscriber);

        if (!$wasConfirmed) {
            TransactionalMail::subscriberWelcome($subscriber);
            TransactionalMail::subscriberPreferencePrompt($subscriber);
        }

        return $this->messageView(
            'You’re confirmed',
            'Look out for curated rituals, invites, and new launches. Want to fine-tune recommendations?',
            'Update preferences',
            route('subscribe.preferences', ['token' => $subscriber->manage_token])
        );
    }

    public function preferences(string $token): View
    {
        $subscriber = V3Subscriber::where('manage_token', $token)->first();
        if (!$subscriber) {
            return $this->messageView('Link expired', 'That manage link is no longer valid. Opt in again from any on-site form.');
        }

        if ($subscriber->status === 'unsubscribed') {
            return $this->messageView(
                'You’re unsubscribed',
                'Want to come back? Tap below and we’ll restore your updates.',
                'Resubscribe',
                route('subscribe.resubscribe', ['token' => $subscriber->manage_token])
            );
        }

        return view('subscribers.preferences', [
            'subscriber' => $subscriber,
            'preferences' => $subscriber->preferences ?? [],
            'token' => $subscriber->manage_token,
            'upsellProducts' => $this->preferenceUpsells(),
            'mindfulArticles' => $this->mindfulTimesArticles(),
        ]);
    }

    public function updatePreferences(Request $request, string $token): RedirectResponse
    {
        $subscriber = V3Subscriber::where('manage_token', $token)->firstOrFail();
        if ($subscriber->status === 'unsubscribed') {
            return redirect()->route('subscribe.preferences', ['token' => $token])->with('status', 'You’ll need to resubscribe first.');
        }

        $data = $request->validate([
            'interests' => ['nullable','array'],
            'interests.*' => ['in:online,in_person'],
            'location' => ['nullable','string','max:255'],
            'radius' => ['nullable','integer','min:5','max:250'],
            'goals' => ['nullable','array'],
            'goals.*' => ['string','max:120'],
        ]);

        $interests = $data['interests'] ?? [];
        $preferences = [
            'online' => in_array('online', $interests, true),
            'in_person' => in_array('in_person', $interests, true),
            'location' => $data['location'] ?? null,
            'radius' => $data['radius'] ?? null,
            'goals' => array_values(array_unique(array_filter($data['goals'] ?? []))),
        ];

        $subscriber->preferences = $preferences;
        $subscriber->save();

        TransactionalMail::subscriberPreferencesUpdated($subscriber);

        return redirect()->route('subscribe.preferences', ['token' => $token])->with('status', 'Preferences saved.');
    }

    public function unsubscribe(string $token): View
    {
        $subscriber = V3Subscriber::where('manage_token', $token)->first();
        if (!$subscriber) {
            return $this->messageView('Link expired', 'That unsubscribe link is no longer valid.');
        }

        if ($subscriber->status === 'unsubscribed') {
            return $this->messageView('Already unsubscribed', 'You’ll only hear from us again if you opt back in.');
        }

        $subscriber->status = 'unsubscribed';
        $subscriber->unsubscribed_at = now();
        $subscriber->confirmed_at = null;
        $subscriber->save();
        $this->syncBackendSubscriber($subscriber);

        TransactionalMail::subscriberUnsubscribed($subscriber);

        return $this->messageView(
            'You’re unsubscribed',
            'Thanks for being with us. If you ever want rituals, offers, or invites again you can resubscribe below.',
            'Resubscribe',
            route('subscribe.resubscribe', ['token' => $subscriber->manage_token])
        );
    }

    public function resubscribe(string $token): View
    {
        $subscriber = V3Subscriber::where('manage_token', $token)->first();
        if (!$subscriber) {
            return $this->messageView('Link expired', 'That resubscribe link is no longer valid.');
        }

        $subscriber->status = 'confirmed';
        $subscriber->confirmed_at = now();
        $subscriber->unsubscribed_at = null;
        $subscriber->save();
        $this->syncBackendSubscriber($subscriber);

        TransactionalMail::subscriberResubscribed($subscriber);

        return $this->messageView(
            'You’re back in',
            'Updates, drops, and launch news will resume. Fine-tune what lands in your inbox any time.',
            'Update preferences',
            route('subscribe.preferences', ['token' => $subscriber->manage_token])
        );
    }

    protected function mindfulTimesArticles(int $limit = 4): array
    {
        try {
            $controller = app(ApiArticleController::class);
            $request = Request::create('/api/articles', 'GET', ['limit' => $limit]);
            $response = $controller->index($request);
            $data = $response->getData(true);
            if (is_array($data)) {
                return array_slice($data, 0, $limit);
            }
        } catch (\Throwable $e) {
            logger()->warning('preferences.articles_failed', ['error' => $e->getMessage()]);
        }

        return [];
    }

    protected function preferenceUpsells(int $limit = 4)
    {
        try {
            $query = Product::query()
                ->with(['media', 'options.values', 'category', 'status'])
                ->withCount('reviews')
                ->withAvg('reviews', 'rating')
                ->where(function ($q) {
                    $q->whereHas('status', function ($qs) {
                        $qs->whereIn('status', ['live', 'approved']);
                    });
                })
                ->orderByRaw('COALESCE(reviews_avg_rating, 0) * LOG(1 + COALESCE(reviews_count, 0)) DESC')
                ->orderByRaw('COALESCE(reviews_avg_rating, 0) DESC')
                ->orderByRaw('COALESCE(reviews_count, 0) DESC')
                ->limit(max($limit, 4));

            return $query->get();
        } catch (\Throwable $e) {
            logger()->warning('preferences.upsells_failed', ['error' => $e->getMessage()]);
        }

        return collect();
    }

    protected function messageView(string $title, string $body, ?string $cta = null, ?string $ctaUrl = null): View
    {
        return view('subscribers.message', [
            'title' => $title,
            'body' => $body,
            'cta' => $cta,
            'ctaUrl' => $ctaUrl,
        ]);
    }

    protected function syncBackendSubscriber(V3Subscriber $subscriber): void
    {
        $backendUrl = rtrim((string) env('BACKEND_URL', env('BACKEND_ASSET_URL', '')), '/');
        if ($backendUrl === '') {
            return;
        }

        $displayName = $subscriber->name ?: trim(($subscriber->first_name ?? '') . ' ' . ($subscriber->last_name ?? ''));
        $payload = array_filter([
            'email' => $subscriber->email,
            'behaviour_visitor_public_id' => request()->cookie(env('BEHAVIOUR_VISITOR_COOKIE', 'wow_visitor_id')),
            'name' => $displayName !== '' ? $displayName : null,
            'first_name' => $subscriber->first_name,
            'last_name' => $subscriber->last_name,
            'business_name' => $subscriber->business_name,
            'notes' => $subscriber->notes,
            'tags' => $subscriber->tags ?? null,
            'source' => 'frontend:v3-subscribers',
            'status' => $subscriber->status ?: 'pending',
        ], fn ($value) => !is_null($value) && $value !== '');

        try {
            $http = Http::timeout(5)
                ->acceptJson()
                ->asJson();
            if (request()->headers->has('cookie')) {
                $http = $http->withHeaders(['Cookie' => request()->headers->get('cookie')]);
            }
            $http->post($backendUrl . '/api/v3-subscribers', $payload);
        } catch (\Throwable $e) {
            logger()->warning('subscriber.backend_sync_failed', [
                'email' => $subscriber->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
