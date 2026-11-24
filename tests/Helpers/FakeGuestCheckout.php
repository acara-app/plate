<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Contracts\Billing\ManagesGuestCheckout;
use App\Data\Billing\GuestCheckoutSession;
use Carbon\CarbonImmutable;
use Stripe\Subscription;

final class FakeGuestCheckout implements ManagesGuestCheckout
{
    /**
     * @var list<array{price: string, reference: string, success_url: string, cancel_url: string, metadata: array<string, string>}>
     */
    public array $sessions = [];

    public string $subscriptionStatus = Subscription::STATUS_ACTIVE;

    public static function install(): self
    {
        $fake = new self;
        app()->instance(ManagesGuestCheckout::class, $fake);

        return $fake;
    }

    public function open(string $priceId, string $reference, string $successUrl, string $cancelUrl, array $metadata): GuestCheckoutSession
    {
        $this->sessions[] = ['price' => $priceId, 'reference' => $reference, 'success_url' => $successUrl, 'cancel_url' => $cancelUrl, 'metadata' => $metadata];
        $id = 'cs_test_guest_'.count($this->sessions);

        return new GuestCheckoutSession($id, 'https://checkout.stripe.com/c/pay/'.$id, CarbonImmutable::now()->addDay());
    }

    public function subscription(string $subscriptionId): Subscription
    {
        return Subscription::constructFrom([
            'id' => $subscriptionId,
            'object' => 'subscription',
            'status' => $this->subscriptionStatus,
            'metadata' => ['type' => 'starter', 'name' => 'starter'],
            'trial_end' => null,
            'cancel_at' => null,
            'canceled_at' => null,
            'items' => ['object' => 'list', 'data' => [[
                'id' => 'si_'.$subscriptionId,
                'object' => 'subscription_item',
                'quantity' => 1,
                'price' => ['id' => 'price_starter', 'object' => 'price', 'product' => 'prod_starter'],
            ]]],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function subscriptionMetadata(): array
    {
        $session = end($this->sessions);

        return $session === false ? [] : $session['metadata'];
    }
}
