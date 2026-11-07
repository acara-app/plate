<?php

declare(strict_types=1);

use App\Contracts\Billing\OffersSubscriptions;
use App\Models\SubscriptionProduct;
use App\Services\Billing\PhotoPlanCatalog;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->beforeEach(function (): void {
        Http::preventStrayRequests();
        Sleep::fake();

        $this->freezeTime();
    })
    ->in('Feature', 'Unit');

expect()->extend('toBeOne', fn () => $this->toBe(1));

function something(): void {}

function revisitAfterSessionExpires(TestResponse $response): TestResponse
{
    $rememberCookie = $response->assertCookie(Auth::getRecallerName())->getCookie(Auth::getRecallerName());

    test()->flushSession();
    Auth::forgetGuards();

    return test()
        ->withCookie(Auth::getRecallerName(), $rememberCookie->getValue())
        ->get(route('user-profile.edit'));
}

function offerAllSubscriptions(): void
{
    app()->instance(OffersSubscriptions::class, new readonly class implements OffersSubscriptions
    {
        public function present(SubscriptionProduct $product): SubscriptionProduct
        {
            return $product;
        }

        public function available(SubscriptionProduct $product): bool
        {
            return true;
        }
    });
}

function enableBilling(): void
{
    config([
        'cashier.key' => 'pk_test_fake',
        'cashier.secret' => 'sk_test_fake',
        'cashier.webhook.secret' => 'whsec_test',
    ]);
    app()->forgetInstance(PhotoPlanCatalog::class);
}

function sellPhotoPlans(): SubscriptionProduct
{
    enableBilling();

    return SubscriptionProduct::factory()->create([
        'name' => 'Starter',
        'price' => 2.99,
        'stripe_price_id' => 'price_starter',
        'stripe_lookup_key' => 'acara-plate-starter-monthly-v1',
        'yearly_stripe_lookup_key' => null,
        'product_group' => 'subscription',
        'purchasable' => true,
        'coming_soon' => false,
    ]);
}

function sendSignedStripeWebhook(array $payload): TestResponse
{
    $body = json_encode($payload, JSON_THROW_ON_ERROR);
    $timestamp = time();
    $signature = hash_hmac('sha256', "{$timestamp}.{$body}", (string) config('cashier.webhook.secret'));

    return test()->call('POST', route('cashier.webhook'), server: [
        'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
        'CONTENT_TYPE' => 'application/json',
    ], content: $body);
}

function disableBilling(): void
{
    config(['cashier.secret' => null]);
    app()->forgetInstance(PhotoPlanCatalog::class);
}
