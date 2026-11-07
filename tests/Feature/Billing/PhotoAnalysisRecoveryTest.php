<?php

declare(strict_types=1);

use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Contracts\Billing\ManagesPhotoAnalyses;
use App\Contracts\Billing\ResolvesFailedPhotoAnalysis;
use App\Data\Billing\PhotoAnalysisContext;
use App\Data\FoodAnalysisData;
use App\Enums\PhotoAnalysisStatus;
use App\Jobs\ProcessPhotoAnalysis;
use App\Models\AnalysisDraft;
use App\Models\PhotoAnalysisRequest;
use App\Models\SubscriptionProduct;
use App\Services\Billing\NullFailedPhotoAnalysisResolver;
use App\Services\Billing\NullPhotoAnalyses;
use Illuminate\Queue\TimeoutExceededException;
use Illuminate\Support\Str;

it('recovers the charged meal into the same request after the worker fails before saving its draft', function (): void {
    $access = resolve(ManagesPhotoAnalyses::class);
    if ($access instanceof NullPhotoAnalyses || resolve(ResolvesFailedPhotoAnalysis::class) instanceof NullFailedPhotoAnalysisResolver) {
        $this->markTestSkipped('Requires the Cloud photo analysis recovery integration.');
    }

    $this->freezeTime();
    FoodPhotoAnalyzerAgent::fake()->preventStrayPrompts();
    config([
        'plate.enable_premium_upgrades' => true,
        'cashier.key' => 'pk_test_fake',
        'cashier.secret' => 'sk_test_fake',
        'cashier.webhook.secret' => 'whsec_test',
    ]);
    SubscriptionProduct::factory()->create([
        'name' => 'Starter', 'price' => 2.99, 'stripe_price_id' => 'price_starter',
        'stripe_lookup_key' => 'acara-plate-starter-monthly-v1', 'purchasable' => true,
    ]);
    $request = PhotoAnalysisRequest::factory()->create(['status' => PhotoAnalysisStatus::Processing]);
    $result = FoodAnalysisData::from(AnalysisDraft::factory()->make()->payload);
    $context = $request->context();
    $access->analyze($context, fn (): FoodAnalysisData => $result);
    expect($access->entitlement(null, $context->guestId)->remaining)->toBe(1);

    $job = new ProcessPhotoAnalysis($request->id);
    $job->failed(new TimeoutExceededException('Worker died after analysis completed'));
    $job->failed(new TimeoutExceededException('Repeated failure callback'));

    $request->refresh();
    expect($request->status)->toBe(PhotoAnalysisStatus::Succeeded)
        ->and($request->image_base64)->toBeNull()
        ->and($request->draft_token)->not->toBeNull()
        ->and(PhotoAnalysisRequest::query()->count())->toBe(1)
        ->and(AnalysisDraft::query()->count())->toBe(1)
        ->and(AnalysisDraft::query()->sole()->token_hash)->toBe(AnalysisDraft::hashToken($request->draft_token))
        ->and($access->entitlement(null, $context->guestId)->remaining)->toBe(1);

    $cached = $access->analyze($context, fn () => throw new RuntimeException('Recovery must reuse the completed analysis'));
    expect($cached->totalCalories)->toBe($result->totalCalories);
    FoodPhotoAnalyzerAgent::assertNeverPrompted();
});

it('releases a timed-out pending scan and rejects its late result without charging the replacement twice', function (): void {
    $access = resolve(ManagesPhotoAnalyses::class);
    if ($access instanceof NullPhotoAnalyses || resolve(ResolvesFailedPhotoAnalysis::class) instanceof NullFailedPhotoAnalysisResolver) {
        $this->markTestSkipped('Requires the Cloud photo analysis recovery integration.');
    }

    $this->freezeTime();
    FoodPhotoAnalyzerAgent::fake()->preventStrayPrompts();
    config([
        'plate.enable_premium_upgrades' => true,
        'cashier.key' => 'pk_test_fake',
        'cashier.secret' => 'sk_test_fake',
        'cashier.webhook.secret' => 'whsec_test',
    ]);
    SubscriptionProduct::factory()->create([
        'name' => 'Starter', 'price' => 2.99, 'stripe_price_id' => 'price_starter',
        'stripe_lookup_key' => 'acara-plate-starter-monthly-v1', 'purchasable' => true,
    ]);
    $request = PhotoAnalysisRequest::factory()->create(['status' => PhotoAnalysisStatus::Processing]);
    $context = $request->context();
    $result = FoodAnalysisData::from(AnalysisDraft::factory()->make()->payload);

    expect(fn () => $access->analyze($context, function () use ($request, $result): FoodAnalysisData {
        new ProcessPhotoAnalysis($request->id)->failed(new TimeoutExceededException('Analysis is still finishing'));

        return $result;
    }))->toThrow(RuntimeException::class);

    $request->refresh();
    expect($request->status)->toBe(PhotoAnalysisStatus::Failed)
        ->and($request->canRetry())->toBeTrue()
        ->and($request->image_base64)->not->toBeNull()
        ->and(AnalysisDraft::query()->count())->toBe(0)
        ->and($access->entitlement(null, $context->guestId)->remaining)->toBe(2);

    $replacement = new PhotoAnalysisContext(null, $context->guestId, (string) Str::uuid(), $context->source, $context->fingerprint);
    $access->analyze($replacement, fn (): FoodAnalysisData => $result);
    expect(resolve(ResolvesFailedPhotoAnalysis::class)->handle($context))->toBeNull()
        ->and($access->entitlement(null, $context->guestId)->remaining)->toBe(1);

    $access->analyze($replacement, fn () => throw new RuntimeException('The completed retry must be reused'));
    expect($access->entitlement(null, $context->guestId)->remaining)->toBe(1);
    FoodPhotoAnalyzerAgent::assertNeverPrompted();
});
