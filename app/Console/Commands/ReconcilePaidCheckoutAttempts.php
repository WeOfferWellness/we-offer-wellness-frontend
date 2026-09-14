<?php

namespace App\Console\Commands;

use App\Models\CheckoutAttempt;
use App\Services\CheckoutOrderService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;

class ReconcilePaidCheckoutAttempts extends Command
{
    protected $signature = 'store:reconcile-paid-checkouts
                            {--since-days=30 : Look back this many days for paid checkout sessions}
                            {--limit=100 : Maximum Stripe sessions to inspect per run}
                            {--dry-run : Report recoverable orders without creating them}';

    protected $description = 'Recover paid Stripe Checkout sessions whose webhook did not create a local order.';

    public function handle(CheckoutOrderService $orderService): int
    {
        if (! Schema::hasTable('checkout_attempts')) {
            $this->warn('Skip: checkout_attempts table does not exist.');

            return self::SUCCESS;
        }

        $secret = (string) config('services.stripe.secret');
        if ($secret === '') {
            $this->error('Stripe secret is not configured.');

            return self::FAILURE;
        }

        $sinceDays = max(1, (int) $this->option('since-days'));
        $limit = min(250, max(1, (int) $this->option('limit')));
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = Carbon::now()->subDays($sinceDays);
        $processedAttemptIds = [];
        $inspected = 0;
        $recovered = 0;
        $unmatched = 0;
        $startingAfter = null;

        Stripe::setApiKey($secret);

        do {
            $parameters = [
                'created' => ['gte' => $cutoff->timestamp],
                'limit' => min(100, $limit - $inspected),
            ];
            if ($startingAfter) {
                $parameters['starting_after'] = $startingAfter;
            }

            try {
                $sessions = StripeSession::all($parameters);
            } catch (ApiErrorException $exception) {
                $this->error('Stripe checkout session listing failed: '.$exception->getMessage());

                return self::FAILURE;
            }

            foreach ($sessions->data as $session) {
                $inspected++;
                $startingAfter = $session->id;

                if (($session->payment_status ?? null) !== 'paid') {
                    continue;
                }

                $attempt = $this->findAttemptForSession($session);
                if (! $attempt) {
                    $unmatched++;
                    $this->warn("Paid Stripe session {$session->id} has no local checkout attempt.");
                    continue;
                }

                if ($attempt->order_id) {
                    continue;
                }

                $processedAttemptIds[$attempt->id] = true;
                if ($this->reconcileAttempt($attempt, $session->id, $session->payment_intent ?? null, $orderService, $dryRun)) {
                    $recovered++;
                }
            }
        } while ($sessions->has_more && $inspected < $limit);

        $remainingAttempts = CheckoutAttempt::query()
            ->whereNull('order_id')
            ->whereNotNull('stripe_session_id')
            ->where('created_at', '>=', $cutoff)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($remainingAttempts as $attempt) {
            if (isset($processedAttemptIds[$attempt->id])) {
                continue;
            }

            try {
                $session = StripeSession::retrieve($attempt->stripe_session_id);
            } catch (ApiErrorException $exception) {
                $this->warn("Unable to retrieve Stripe session {$attempt->stripe_session_id}: {$exception->getMessage()}");
                continue;
            }

            if (($session->payment_status ?? null) !== 'paid') {
                continue;
            }

            if ($this->reconcileAttempt($attempt, $session->id, $session->payment_intent ?? null, $orderService, $dryRun)) {
                $recovered++;
            }
        }

        $mode = $dryRun ? 'would recover' : 'recovered';
        $this->info("Inspected {$inspected} Stripe sessions; {$mode} {$recovered} order(s); {$unmatched} paid session(s) lacked a local attempt.");

        return self::SUCCESS;
    }

    protected function findAttemptForSession(object $session): ?CheckoutAttempt
    {
        $attemptId = (int) ($session->metadata->attempt_id ?? 0);
        if ($attemptId > 0) {
            return CheckoutAttempt::find($attemptId);
        }

        $sessionId = trim((string) ($session->id ?? ''));

        return $sessionId === ''
            ? null
            : CheckoutAttempt::query()->where('stripe_session_id', $sessionId)->first();
    }

    protected function reconcileAttempt(
        CheckoutAttempt $attempt,
        string $sessionId,
        ?string $paymentIntentId,
        CheckoutOrderService $orderService,
        bool $dryRun
    ): bool {
        if ($dryRun) {
            $this->line("Paid checkout attempt {$attempt->id} ({$sessionId}) needs an order.");

            return true;
        }

        $order = $orderService->finalizePaidAttempt($attempt, [
            'stripe_session_id' => $sessionId,
            'stripe_payment_intent_id' => $paymentIntentId,
        ]);

        if (! $order) {
            $this->warn("Checkout attempt {$attempt->id} disappeared before it could be reconciled.");

            return false;
        }

        $this->info("Recovered order {$order->id} from checkout attempt {$attempt->id}.");

        return true;
    }
}
