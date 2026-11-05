<?php

declare(strict_types=1);

use App\Contracts\Billing\ManagesPhotoAnalyses;
use App\Contracts\Billing\ManagesPhotoPreview;
use App\Contracts\Billing\TracksPhotoConversion;
use App\Data\Billing\PhotoAnalysisContext;
use App\Data\Billing\PhotoEntitlement;
use App\Data\Billing\PhotoModel;
use App\Data\Billing\PhotoPreview;
use App\Data\FoodAnalysisData;
use App\Models\User;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('keeps community installations free of preview grants and commercial attribution', function (): void {
    $user = User::factory()->create();

    expect(resolve(TracksPhotoConversion::class)->metadata($user, null))->toBe([]);
    resolve(TracksPhotoConversion::class)->track($user, null, 'visit', 'visit');

    expect(fn () => resolve(ManagesPhotoPreview::class)->activate($user, null))
        ->toThrow(HttpException::class);
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

    app()->instance(ManagesPhotoAnalyses::class, new class implements ManagesPhotoAnalyses
    {
        public function enabled(): bool
        {
            return true;
        }

        public function entitlement(?User $user, ?string $guestId): PhotoEntitlement
        {
            return new PhotoEntitlement(enabled: true, limit: 2, used: 2, preview: new PhotoPreview('available'));
        }

        public function analyze(PhotoAnalysisContext $context, Closure $analyze): FoodAnalysisData
        {
            return $analyze(PhotoModel::standard());
        }
    });

    Livewire::test('pages::snap-to-track')->call('startPreview')
        ->assertRedirect(route($signedIn ? 'verification.notice' : 'register'));

    expect(session('url.intended'))->toBe(route('snap-to-track', absolute: false))
        ->and(session('snap_to_track.intent'))->toBe('preview');
})->with([false, true]);

it('retains a preview meal draft while a guest returns before completing signup', function (): void {
    session()->put('snap_to_track.preview_draft', 'pending-meal-token');

    Livewire::test('pages::snap-to-track');

    expect(session('snap_to_track.preview_draft'))->toBe('pending-meal-token');
});
