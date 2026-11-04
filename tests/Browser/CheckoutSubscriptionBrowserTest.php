<?php

declare(strict_types=1);

use App\Contracts\Billing\ManagesPhotoAnalyses;
use App\Data\Billing\PhotoAnalysisContext;
use App\Data\Billing\PhotoEntitlement;
use App\Data\Billing\PhotoModel;
use App\Data\FoodAnalysisData;
use App\Models\User;

it('sends a paying member from the checkout page to the in-app scanner', function (): void {
    app()->instance(ManagesPhotoAnalyses::class, new class implements ManagesPhotoAnalyses
    {
        public function enabled(): bool
        {
            return true;
        }

        public function entitlement(?User $user, ?string $guestId): PhotoEntitlement
        {
            return new PhotoEntitlement(mode: 'premium');
        }

        public function analyze(PhotoAnalysisContext $context, Closure $analyze): FoodAnalysisData
        {
            return $analyze(PhotoModel::standard());
        }
    });
    $this->actingAs(User::factory()->create(['stripe_id' => null]));

    visit(route('checkout.subscription', absolute: false))
        ->assertSee('Your plan is active.')
        ->click('Continue scanning')
        ->assertPathIs(route('snap-to-track.index', absolute: false))
        ->assertSee('Choose photo')
        ->assertNoJavaScriptErrors();
});
