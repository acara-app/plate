<?php

declare(strict_types=1);

namespace Tests\Helpers;

use App\Contracts\Billing\ManagesGuestCheckout;
use App\Data\Billing\GuestCheckoutSession;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Closure;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\IdempotencyException;
use Stripe\Exception\UnknownApiErrorException;
use Stripe\Subscription;

final class FakeGuestCheckout implements ManagesGuestCheckout
{
    /**
     * @var list<array{key: string, price: string, reference: string, success_url: string, cancel_url: string, metadata: array<string, string>, submit_message: string}>
     */
    public array $sessions = [];

    public string $subscriptionStatus = Subscription::STATUS_ACTIVE;

    public ?Closure $beforeSubscription = null;

    public ?Closure $beforeOpen = null;

    public ?int $failNextWith = null;

    public bool $failureCreatesSession = false;

    /**
     * @var array<string, array{request: array<string, mixed>, response: GuestCheckoutSession|ApiErrorException}>
     */
    private array $saved = [];

    /**
     * @var array<string, GuestCheckoutSession>
     */
    private array $created = [];

    /**
     * @var array<string, GuestCheckoutSession>
     */
    private array $createdLater = [];

    public static function install(): self
    {
        $fake = new self;
        app()->instance(ManagesGuestCheckout::class, $fake);

        return $fake;
    }

    public function open(string $idempotencyKey, string $priceId, string $reference, string $successUrl, string $cancelUrl, array $metadata, string $submitMessage): GuestCheckoutSession
    {
        $beforeOpen = $this->beforeOpen;
        $this->beforeOpen = null;

        if ($beforeOpen instanceof Closure) {
            $beforeOpen();
        }

        $request = ['key' => $idempotencyKey, 'price' => $priceId, 'reference' => $reference, 'success_url' => $successUrl, 'cancel_url' => $cancelUrl, 'metadata' => $metadata, 'submit_message' => $submitMessage];
        $this->sessions[] = $request;

        if ($this->failNextWith === 409) {
            $this->failNextWith = null;

            throw UnknownApiErrorException::factory('Another request with this idempotency key is still in progress.', 409);
        }

        $saved = $this->saved[$idempotencyKey] ??= ['request' => $request, 'response' => $this->respond($reference)];

        throw_if($saved['request'] !== $request, IdempotencyException::factory('Keys for idempotent requests can only be used with the same parameters they were first used with.', 400));

        if ($saved['response'] instanceof ApiErrorException) {
            throw $saved['response'];
        }

        return $saved['response'];
    }

    public function findOpenSession(string $reference, CarbonInterface $createdSince): ?GuestCheckoutSession
    {
        return $this->created[$reference] ?? null;
    }

    public function finishFailedRequests(): void
    {
        $this->created = [...$this->created, ...$this->createdLater];
        $this->createdLater = [];
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

    private function respond(string $reference): GuestCheckoutSession|ApiErrorException
    {
        $status = $this->failNextWith;
        $this->failNextWith = null;
        $id = 'cs_test_guest_'.(count($this->saved) + 1);
        $session = new GuestCheckoutSession($id, 'https://checkout.stripe.com/c/pay/'.$id, CarbonImmutable::now()->addDay());

        if ($status === null || $this->failureCreatesSession) {
            $this->created[$reference] = $session;
        } else {
            $this->createdLater[$reference] = $session;
        }

        return $status === null ? $session : UnknownApiErrorException::factory('Something went wrong on our end.', $status);
    }
}
