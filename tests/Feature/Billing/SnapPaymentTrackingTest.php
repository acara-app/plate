<?php

declare(strict_types=1);

use App\Contracts\Billing\TracksPhotoConversion;
use App\Models\User;
use App\Services\Billing\NullPhotoConversion;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Laravel\Cashier\Events\WebhookReceived;

beforeEach(function (): void {
    if (resolve(TracksPhotoConversion::class) instanceof NullPhotoConversion) {
        $this->markTestSkipped('Requires the optional photo conversion integration.');
    }
    config(['cashier.webhook.secret' => 'whsec_snap_test']);
});

it('records a confirmed invoice once even when the customer never returns from checkout', function (): void {
    $user = User::factory()->create(['stripe_id' => 'cus_snap']);
    $guest = (string) Str::uuid();
    resolve(TracksPhotoConversion::class)->track($user, $guest, 'visit', 'visit');
    $invoice = [
        'id' => 'in_snap', 'customer' => 'cus_snap', 'amount_paid' => 299, 'currency' => 'usd',
        'billing_reason' => 'subscription_create', 'status_transitions' => ['paid_at' => now()->timestamp],
        'parent' => ['subscription_details' => ['subscription' => 'sub_snap', 'metadata' => ['snap_origin' => 'snap_to_track']]],
    ];
    foreach (['invoice.payment_succeeded', 'invoice.paid'] as $type) {
        Event::dispatch(new WebhookReceived(['id' => 'evt_'.$type, 'type' => $type, 'created' => now()->timestamp, 'livemode' => false, 'data' => ['object' => $invoice]]));
    }

    $this->assertDatabaseCount('snap_billing_events', 1);
    $this->assertDatabaseHas('snap_billing_events', ['invoice_id' => 'in_snap', 'kind' => 'initial_payment', 'amount' => 299, 'live' => false]);
});

it('keeps each partial refund at its original time when Stripe retries older events', function (): void {
    $user = User::factory()->create(['stripe_id' => 'cus_refund']);
    $first = ['id' => 're_first', 'amount' => 100, 'created' => now()->subDays(20)->timestamp, 'status' => 'succeeded'];
    $second = ['id' => 're_second', 'amount' => 199, 'created' => now()->timestamp, 'status' => 'succeeded'];
    foreach ([[$first], [$first, $second], [$first]] as $refunds) {
        Event::dispatch(new WebhookReceived([
            'id' => 'evt_refund_'.count($refunds), 'type' => 'charge.refunded', 'created' => now()->timestamp,
            'data' => ['object' => ['id' => 'ch_refund', 'customer' => $user->stripe_id, 'invoice' => 'in_refund', 'amount_refunded' => array_sum(array_column($refunds, 'amount')), 'currency' => 'usd', 'refunds' => ['data' => $refunds]]],
        ]));
    }

    $this->assertDatabaseHas('snap_billing_events', ['event_key' => 'refund:re_first', 'kind' => 'refund', 'amount' => 100, 'occurred_at' => now()->subDays(20)->toDateTimeString()]);
    $this->assertDatabaseHas('snap_billing_events', ['event_key' => 'refund:re_second', 'kind' => 'refund', 'amount' => 199]);
    expect(Illuminate\Support\Facades\DB::table('snap_billing_events')->where('kind', 'refund')->sum('amount'))->toBe(299);
});

it('rejects forged Stripe callbacks before recording payment', function (): void {
    config(['cashier.webhook.secret' => 'whsec_snap_test']);
    $user = User::factory()->create(['stripe_id' => 'cus_forged']);
    $payload = json_encode(['id' => 'evt_forged', 'type' => 'invoice.paid', 'data' => ['object' => [
        'id' => 'in_forged', 'customer' => $user->stripe_id, 'amount_paid' => 299, 'currency' => 'usd',
        'parent' => ['subscription_details' => ['metadata' => ['snap_origin' => 'snap_to_track']]],
    ]]], JSON_THROW_ON_ERROR);

    $this->call('POST', route('cashier.webhook'), server: ['HTTP_STRIPE_SIGNATURE' => 't='.time().',v1=forged', 'CONTENT_TYPE' => 'application/json'], content: $payload)
        ->assertForbidden();

    $this->assertDatabaseCount('snap_billing_events', 0);
});

it('records a signed delayed payment without a checkout return and ignores retries', function (): void {
    config(['cashier.webhook.secret' => 'whsec_snap_test']);
    $user = User::factory()->create(['stripe_id' => 'cus_delayed']);
    $payload = json_encode(['id' => 'evt_delayed', 'type' => 'invoice.paid', 'created' => now()->timestamp, 'livemode' => false, 'data' => ['object' => [
        'id' => 'in_delayed', 'customer' => $user->stripe_id, 'amount_paid' => 299, 'currency' => 'usd', 'billing_reason' => 'subscription_create',
        'parent' => ['subscription_details' => ['metadata' => ['snap_origin' => 'snap_to_track']]],
    ]]], JSON_THROW_ON_ERROR);
    $time = time();
    $signature = hash_hmac('sha256', $time.'.'.$payload, 'whsec_snap_test');
    foreach (range(1, 2) as $attempt) {
        $this->call('POST', route('cashier.webhook'), server: ['HTTP_STRIPE_SIGNATURE' => 't='.$time.',v1='.$signature, 'CONTENT_TYPE' => 'application/json'], content: $payload)
            ->assertSuccessful();
    }
    $this->assertDatabaseCount('snap_billing_events', 1);
    $this->assertDatabaseHas('snap_billing_events', ['invoice_id' => 'in_delayed', 'kind' => 'initial_payment']);
});

it('does not count unverified callbacks when the webhook secret is missing', function (): void {
    config(['cashier.webhook.secret' => null]);
    $user = User::factory()->create(['stripe_id' => 'cus_unverified']);
    $this->postJson(route('cashier.webhook'), ['id' => 'evt_unverified', 'type' => 'invoice.paid', 'data' => ['object' => [
        'id' => 'in_unverified', 'customer' => $user->stripe_id, 'amount_paid' => 299, 'currency' => 'usd',
        'parent' => ['subscription_details' => ['metadata' => ['snap_origin' => 'snap_to_track']]],
    ]]])->assertSuccessful();

    $this->assertDatabaseCount('snap_billing_events', 0);
});
