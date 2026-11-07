<?php

declare(strict_types=1);

use App\Contracts\Billing\ManagesPhotoAnalyses;
use App\Data\Billing\PhotoAnalysisContext;
use App\Data\FoodAnalysisData;
use App\Exceptions\Billing\PhotoLimitExceeded;
use App\Models\SubscriptionProduct;
use App\Models\User;
use Illuminate\Support\Str;

it('preserves two lifetime scans through retries, pending requests, and account registration', function (): void {
    $access = resolve(ManagesPhotoAnalyses::class);

    config([
        'cashier.key' => 'pk_test_fake',
        'cashier.secret' => 'sk_test_fake',
        'cashier.webhook.secret' => 'whsec_test',
    ]);
    SubscriptionProduct::factory()->create([
        'name' => 'Starter', 'price' => 2.99, 'stripe_price_id' => 'price_starter',
        'stripe_lookup_key' => 'acara-plate-starter-monthly-v1', 'purchasable' => true,
    ]);
    $guest = (string) Str::uuid();
    $context = new PhotoAnalysisContext(null, $guest, (string) Str::uuid(), 'public_snap_to_track', 'fixture');
    $result = FoodAnalysisData::from([
        'items' => [['name' => 'Rice', 'calories' => 130, 'protein' => 3, 'carbs' => 28, 'fat' => 1, 'portion' => '100 g']],
        'total_calories' => 130, 'total_protein' => 3, 'total_carbs' => 28, 'total_fat' => 1, 'confidence' => 80,
    ]);

    expect($access->entitlement(null, $guest)->remaining)->toBe(2);
    expect(fn () => $access->analyze($context, fn () => throw new RuntimeException('Analysis unavailable')))
        ->toThrow(RuntimeException::class, 'Analysis unavailable');
    expect($access->entitlement(null, $guest)->remaining)->toBe(2);

    $access->analyze($context, fn (): FoodAnalysisData => $result);
    $access->analyze($context, fn () => throw new RuntimeException('A retry must reuse the result'));

    expect($access->entitlement(null, $guest)->remaining)->toBe(1);

    $user = User::factory()->create();
    expect($access->entitlement($user, $guest)->remaining)->toBe(1);
    $second = new PhotoAnalysisContext($user, $guest, (string) Str::uuid(), 'public_snap_to_track', 'fixture');
    $third = new PhotoAnalysisContext($user, $guest, (string) Str::uuid(), 'public_snap_to_track', 'fixture');
    $access->analyze($second, function () use ($access, $third, $result): FoodAnalysisData {
        expect(fn () => $access->analyze($third, fn (): FoodAnalysisData => $result))->toThrow(PhotoLimitExceeded::class);

        return $result;
    });

    expect($access->entitlement($user, null)->remaining)->toBe(0);
    expect($access->entitlement(null, $guest)->remaining)->toBe(0);
    expect(fn () => $access->analyze($third, fn (): FoodAnalysisData => $result))->toThrow(PhotoLimitExceeded::class);
});
