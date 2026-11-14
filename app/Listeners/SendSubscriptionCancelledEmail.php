<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use App\Notifications\SubscriptionCancelledNotification;
use App\Utilities\LanguageUtil;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Laravel\Cashier\Events\WebhookHandled;
use Laravel\Cashier\Subscription;

final readonly class SendSubscriptionCancelledEmail
{
    private const string SCHEDULED = 'customer.subscription.updated';

    private const string ENDED = 'customer.subscription.deleted';

    /**
     * @var list<string>
     */
    private const array UNREQUESTED_REASONS = ['payment_failed', 'payment_disputed'];

    public function handle(WebhookHandled $event): void
    {
        /** @var array<string, mixed> $payload */
        $payload = $event->payload;
        $eventId = $payload['id'] ?? null;
        $type = $payload['type'] ?? null;
        $stripeSubscription = data_get($payload, 'data.object');

        if (blank(config('cashier.webhook.secret')) || ! is_string($eventId) || ! in_array($type, [self::SCHEDULED, self::ENDED], true) || ! is_array($stripeSubscription)) {
            return;
        }

        if (in_array(data_get($stripeSubscription, 'cancellation_details.reason'), self::UNREQUESTED_REASONS, true)) {
            return;
        }

        $endsAt = $type === self::ENDED
            ? $this->endedAt($stripeSubscription)
            : $this->newlyScheduledEnd($stripeSubscription, data_get($payload, 'data.previous_attributes'));

        $customer = $stripeSubscription['customer'] ?? null;
        $user = is_string($customer) ? User::query()->where('stripe_id', $customer)->first() : null;

        if (! $endsAt instanceof CarbonImmutable || ! $user instanceof User) {
            return;
        }

        $subscription = $user->subscriptions()->where('stripe_id', $stripeSubscription['id'] ?? null)->first();

        if (! $subscription instanceof Subscription || ! Cache::add("stripe-webhook:{$eventId}:subscription-cancelled", true, now()->addDays(3))) {
            return;
        }

        $user->notify(
            new SubscriptionCancelledNotification(
                plan: (string) $user->subscriptionDisplayName($subscription),
                endsAt: $endsAt,
                endedNow: $type === self::ENDED,
            )->locale(LanguageUtil::resolve($user->locale)['code']),
        );
    }

    /**
     * @param  array<mixed>  $stripeSubscription
     */
    private function endedAt(array $stripeSubscription): ?CarbonImmutable
    {
        if ($this->isScheduled($stripeSubscription)) {
            return null;
        }

        return $this->timestamp($stripeSubscription['ended_at'] ?? $stripeSubscription['canceled_at'] ?? null);
    }

    /**
     * @param  array<mixed>  $stripeSubscription
     */
    private function newlyScheduledEnd(array $stripeSubscription, mixed $previousAttributes): ?CarbonImmutable
    {
        $previous = is_array($previousAttributes) ? [...$stripeSubscription, ...$previousAttributes] : $stripeSubscription;

        if (! $this->isScheduled($stripeSubscription) || $this->isScheduled($previous)) {
            return null;
        }

        return $this->timestamp($stripeSubscription['cancel_at'] ?? data_get($stripeSubscription, 'items.data.0.current_period_end'));
    }

    /**
     * @param  array<mixed>  $stripeSubscription
     */
    private function isScheduled(array $stripeSubscription): bool
    {
        return ($stripeSubscription['cancel_at_period_end'] ?? false) === true || isset($stripeSubscription['cancel_at']);
    }

    private function timestamp(mixed $timestamp): ?CarbonImmutable
    {
        return is_int($timestamp) ? CarbonImmutable::createFromTimestamp($timestamp) : null;
    }
}
