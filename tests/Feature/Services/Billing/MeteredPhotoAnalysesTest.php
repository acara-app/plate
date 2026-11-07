<?php

declare(strict_types=1);

use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Contracts\Billing\ManagesPhotoAnalyses;
use App\Data\Billing\PhotoAnalysisContext;
use App\Data\Billing\PhotoModel;
use App\Data\FoodAnalysisData;
use App\Exceptions\Billing\UsageLimitExceededException;
use App\Models\AiUsage;
use App\Models\SnapPhotoAccount;
use App\Models\SnapPhotoPeriod;
use App\Models\SubscriptionProduct;
use App\Models\User;
use App\Services\Billing\MeteredPhotoAnalyses;
use Illuminate\Support\Str;
use Laravel\Cashier\Subscription;
use Symfony\Component\HttpKernel\Exception\HttpException;

covers(MeteredPhotoAnalyses::class);

beforeEach(function (): void {
    config([
        'plate.enable_premium_upgrades' => true,
        'cashier.key' => 'pk_test_fake',
        'cashier.secret' => 'sk_test_fake',
        'cashier.webhook.secret' => 'whsec_test',
        'snap.premium.max_tokens' => 2048,
        'ai.agent_profiles.'.FoodPhotoAnalyzerAgent::class => ['standard' => 'gemini-3.6-flash', 'paid' => 'gemini-3.8-flash'],
    ]);
    SubscriptionProduct::factory()->create([
        'name' => 'Starter', 'price' => 2.99, 'stripe_price_id' => 'price_starter',
        'stripe_lookup_key' => 'acara-plate-starter-monthly-v1', 'purchasable' => true,
    ]);
});

function photoScanFor(?User $user, ?string $guestId = null): PhotoAnalysisContext
{
    return new PhotoAnalysisContext($user, $guestId, (string) Str::uuid(), 'public_snap_to_track', 'photo-fingerprint');
}

function payingPhotoMember(): User
{
    $member = User::factory()->create();
    Subscription::factory()->for($member)->active()->withPrice('price_starter')->create();

    return $member;
}

function mealAnalysisWithItems(int $count): FoodAnalysisData
{
    return FoodAnalysisData::from([
        'items' => array_fill(0, $count, ['name' => 'Rice', 'calories' => 130, 'protein' => 3, 'carbs' => 28, 'fat' => 1, 'portion' => '100 g']),
        'total_calories' => 130 * $count, 'total_protein' => 3 * $count, 'total_carbs' => 28 * $count, 'total_fat' => $count, 'confidence' => 80,
    ]);
}

it("runs a paying member's scans on the paid model without a scan allowance", function (): void {
    $member = payingPhotoMember();
    $access = resolve(ManagesPhotoAnalyses::class);
    $usedModel = null;

    $access->analyze(photoScanFor($member), function (PhotoModel $model) use (&$usedModel): FoodAnalysisData {
        $usedModel = $model;

        return mealAnalysisWithItems(1);
    });

    expect($usedModel?->model)->toBe('gemini-3.8-flash')
        ->and($usedModel?->maxTokens)->toBe(2048)
        ->and($access->entitlement($member, null)->mode)->toBe('premium')
        ->and($access->entitlement($member, null)->limit)->toBeNull();
});

it("stops a paying member's scan once their plan limit is reached", function (): void {
    $member = payingPhotoMember();
    AiUsage::factory()->for($member)->create(['cost' => 0.60, 'created_at' => now()->subHour()]);

    expect(fn (): FoodAnalysisData => resolve(ManagesPhotoAnalyses::class)->analyze(
        photoScanFor($member),
        fn (): FoodAnalysisData => throw new RuntimeException('The provider must not be called.'),
    ))->toThrow(UsageLimitExceededException::class);
});

it("pauses a paying member's scans while the paid photo model is unavailable", function (): void {
    $member = payingPhotoMember();
    config(['ai.agent_profiles.'.FoodPhotoAnalyzerAgent::class.'.paid' => 'unregistered-model']);

    expect(fn (): FoodAnalysisData => resolve(ManagesPhotoAnalyses::class)->analyze(photoScanFor($member), fn (): FoodAnalysisData => mealAnalysisWithItems(1)))
        ->toThrow(function (HttpException $exception): void {
            expect($exception->getStatusCode())->toBe(503);
        });
});

it('does not use up a free scan when no food is found in the photo', function (): void {
    $guest = (string) Str::uuid();
    $access = resolve(ManagesPhotoAnalyses::class);

    $result = $access->analyze(photoScanFor(null, $guest), fn (): FoodAnalysisData => mealAnalysisWithItems(0));

    expect($result->items)->toHaveCount(0)
        ->and($access->entitlement(null, $guest)->remaining)->toBe(2);
});

it('shows a visitor their full allowance without storing anything until they scan', function (): void {
    $entitlement = resolve(ManagesPhotoAnalyses::class)->entitlement(null, (string) Str::uuid());

    expect($entitlement->remaining)->toBe(2)
        ->and($entitlement->canUpgrade)->toBeTrue()
        ->and($entitlement->offer?->name)->toBe('Starter')
        ->and(SnapPhotoAccount::query()->count())->toBe(0)
        ->and(SnapPhotoPeriod::query()->count())->toBe(0);
});
