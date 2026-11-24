<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Contracts\Billing\ManagesGuestCheckout;
use App\Data\Billing\GuestCheckoutSession;
use Carbon\CarbonImmutable;
use Closure;
use Stripe\Subscription;

final class FakeGuestCheckout implements ManagesGuestCheckout
{
    /**
     * @var list<array{price: string, reference: string, success_url: string, cancel_url: string, metadata: array<string, string>, submit_message: string}>
     */
    public array $sessions = [];

    public string $subscriptionStatus = Subscription::STATUS_ACTIVE;

    public ?Closure $beforeSubscription = null;

    public ?Closure $beforeOpen = null;

    /**
     * @var array<string, GuestCheckoutSession>
     */
    private array $opened = [];

    public static function install(): self
    {
        $fake = new self;
        app()->instance(ManagesGuestCheckout::class, $fake);

        return $fake;
    }

    public function open(string $priceId, string $reference, string $successUrl, string $cancelUrl, array $metadata, string $submitMessage): GuestCheckoutSession
    {
        $beforeOpen = $this->beforeOpen;
        $this->beforeOpen = null;

        if ($beforeOpen instanceof Closure) {
            $beforeOpen();
        }

        $this->sessions[] = ['price' => $priceId, 'reference' => $reference, 'success_url' => $successUrl, 'cancel_url' => $cancelUrl, 'metadata' => $metadata, 'submit_message' => $submitMessage];
        $id = 'cs_test_guest_'.(count($this->opened) + 1);

        return $this->opened[$reference] ??= new GuestCheckoutSession($id, 'https://checkout.stripe.com/c/pay/'.$id, CarbonImmutable::now()->addDay());
    }

    public function subscription(string $subscriptionId): Subscription
    {
        $beforeSubscription = $this->beforeSubscription;
        $this->beforeSubscription = null;

        if ($beforeSubscription instanceof Closure) {
            $beforeSubscription();
        }

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
