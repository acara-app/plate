<?php

declare(strict_types=1);

use App\Contracts\Billing\ManagesPhotoPreview;
use App\Contracts\Billing\TracksPhotoConversion;
use App\Models\User;

it('keeps community installations free of preview grants and commercial attribution', function (): void {
    $user = User::factory()->create();

    expect(resolve(TracksPhotoConversion::class)->metadata($user, null))->toBe([]);
    resolve(TracksPhotoConversion::class)->track($user, null, 'visit', 'visit');

    expect(fn () => resolve(ManagesPhotoPreview::class)->activate($user, null))
        ->toThrow(Symfony\Component\HttpKernel\Exception\HttpException::class);
});

it('explains the Snap destination on signup without losing the selected plan', function (): void {
    $this->withSession([
        'snap_to_track.intent' => 'upgrade',
        'checkout.selected_product' => 7,
        'url.intended' => '/checkout/subscription',
    ])->get(route('register'))->assertInertia(fn ($page) => $page
        ->where('snapIntent', 'upgrade'))
        ->assertSessionHas('checkout.selected_product', 7)
        ->assertSessionHas('url.intended', '/checkout/subscription');
});

it('requires a verified account before activating an offered preview', function (bool $signedIn): void {
    if ($signedIn) {
        $this->actingAs(User::factory()->unverified()->create());
    }
    app()->instance(App\Contracts\Billing\ManagesPhotoAnalyses::class, new class implements App\Contracts\Billing\ManagesPhotoAnalyses
    {
        public function enabled(): bool
        {
            return true;
        }

        public function entitlement(?User $user, ?string $guestId): App\Data\Billing\PhotoEntitlement
        {
            return new App\Data\Billing\PhotoEntitlement(enabled: true, limit: 2, used: 2, preview: new App\Data\Billing\PhotoPreview('available'));
        }

        public function analyze(App\Data\Billing\PhotoAnalysisContext $context, Closure $analyze): App\Data\FoodAnalysisData
        {
            return $analyze(App\Data\Billing\PhotoModel::standard());
        }
    });

    Livewire\Livewire::test('pages::snap-to-track')->call('startPreview')
        ->assertRedirect(route($signedIn ? 'verification.notice' : 'register'));

    expect(session('url.intended'))->toBe(route('snap-to-track', absolute: false))
        ->and(session('snap_to_track.intent'))->toBe('preview');
})->with([false, true]);

it('retains a preview meal draft while a guest returns before completing signup', function (): void {
    session()->put('snap_to_track.preview_draft', 'pending-meal-token');

    Livewire\Livewire::test('pages::snap-to-track');

    expect(session('snap_to_track.preview_draft'))->toBe('pending-meal-token');
});
