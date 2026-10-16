<?php

declare(strict_types=1);

use App\Contracts\Billing\OffersSubscriptions;
use App\Models\SubscriptionProduct;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->beforeEach(function (): void {
        Http::preventStrayRequests();
        Sleep::fake();

        $this->freezeTime();
    })
    ->in('Browser', 'Feature', 'Unit');

expect()->extend('toBeOne', fn () => $this->toBe(1));

function something(): void {}

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
