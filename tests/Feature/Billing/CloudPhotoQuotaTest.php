<?php

declare(strict_types=1);

use Acara\AcaraCore\Services\Billing\CloudPhotoAnalyses;
use App\Data\Billing\PhotoAnalysisContext;
use App\Data\FoodAnalysisData;
use App\Exceptions\Billing\PhotoLimitExceeded;
use App\Models\User;

beforeEach(function (): void {
    config()->set('snap', ['enabled' => true, 'model_approved' => true,
        'premium' => ['provider' => 'gemini', 'model' => 'tested-pro', 'max_tokens' => 2048, 'options' => []],
        'free_trial_scans' => 1, 'monthly_scans' => 100]);
    config()->set(['cashier.key' => 'pk_test_fixture', 'cashier.secret' => 'sk_test_fixture', 'cashier.webhook.secret' => 'whsec_fixture']);
    App\Models\SubscriptionProduct::query()->updateOrCreate(['name' => 'Snap Pro'], ['name' => 'Snap Pro', 'price' => 9, 'stripe_price_id' => 'price_ready', 'stripe_lookup_key' => 'acara-plate-snap-pro-monthly-v1', 'purchasable' => true]);
});

it('shares one lifetime trial across entry points without restoring it the next day', function (): void {
    $this->travelTo(now('UTC')->startOfDay()->addHours(12));
    $user = User::factory()->create();
    $access = resolve(CloudPhotoAnalyses::class);
    $result = FoodAnalysisData::from(['items' => [['name' => 'Rice', 'calories' => 100, 'protein' => 2, 'carbs' => 22, 'fat' => 0, 'portion' => '100 g']], 'total_calories' => 100, 'total_protein' => 2, 'total_carbs' => 22, 'total_fat' => 0, 'confidence' => 80]);
    $access->analyze(new PhotoAnalysisContext($user, null, 'first', 'web', 'image'), fn () => $result);
    expect(fn () => $access->analyze(new PhotoAnalysisContext($user, null, 'second', 'api', 'other'), fn () => $result))->toThrow(PhotoLimitExceeded::class);
    $this->travel(1)->days();
    expect($access->entitlement($user)->resetsAt)->toBeNull();
    expect(fn () => $access->analyze(new PhotoAnalysisContext($user, null, 'third', 'chat', 'other'), fn () => $result))->toThrow(PhotoLimitExceeded::class);
});

it('reuses successful requests, releases failures, and carries guest usage into login', function (): void {
    $access = resolve(CloudPhotoAnalyses::class);
    $user = User::factory()->create();
    $result = FoodAnalysisData::from(['items' => [['name' => 'Rice', 'calories' => 100, 'protein' => 2, 'carbs' => 22, 'fat' => 0, 'portion' => '100 g']], 'total_calories' => 100, 'total_protein' => 2, 'total_carbs' => 22, 'total_fat' => 0, 'confidence' => 80]);
    $context = new PhotoAnalysisContext(null, 'browser', 'retry', 'web', 'image');
    expect(fn () => $access->analyze($context, fn () => throw new RuntimeException('Provider failed')))->toThrow(RuntimeException::class);
    expect($access->entitlement(null, 'browser')->remaining())->toBe(1);
    $access->analyze($context, fn () => $result);
    expect($access->analyze($context, fn () => throw new RuntimeException('Must not call provider'))->toArray())->toBe($result->toArray());
    expect($access->entitlement($user, 'browser')->remaining())->toBe(0);
    expect($access->entitlement($user)->remaining())->toBe(0);
    expect($access->entitlement(null, 'browser')->remaining())->toBe(0);
    expect(fn () => $access->analyze(new PhotoAnalysisContext($user, null, 'new', 'chat', 'different'), fn () => $result))->toThrow(PhotoLimitExceeded::class);
});

it('grants exactly 100 premium scans from paid invoice periods and preserves cancellation grace', function (): void {
    $user = User::factory()->create(['stripe_id' => 'cus_snap']);
    App\Models\SubscriptionProduct::query()->updateOrCreate(['name' => 'Snap Pro'], ['name' => 'Snap Pro', 'stripe_price_id' => 'price_snap']);
    $subscription = $user->subscriptions()->create(['type' => 'snap-pro', 'stripe_id' => 'sub_snap', 'stripe_status' => 'active', 'stripe_price' => 'price_snap', 'quantity' => 1]);
    $invoice = ['id' => 'in_snap', 'customer' => 'cus_snap', 'subscription' => 'sub_snap', 'paid' => true, 'billing_reason' => 'subscription_cycle',
        'lines' => ['data' => [['price' => ['id' => 'price_snap'], 'proration' => false, 'period' => ['start' => now()->timestamp, 'end' => now()->addMonth()->timestamp]]]]];
    $listener = new Acara\AcaraCore\Listeners\SyncSnapBillingPeriod;
    $event = new Laravel\Cashier\Events\WebhookReceived(['type' => 'invoice.payment_succeeded', 'data' => ['object' => $invoice]]);
    $listener->handle($event);
    $listener->handle($event);
    expect(Illuminate\Support\Facades\DB::table('snap_paid_periods')->count())->toBe(1);
    $access = resolve(CloudPhotoAnalyses::class);
    expect($access->entitlement($user)->limit)->toBe(100);
    $result = FoodAnalysisData::from(['items' => [['name' => 'Rice', 'calories' => 100, 'protein' => 2, 'carbs' => 22, 'fat' => 0, 'portion' => '100 g']], 'total_calories' => 100, 'total_protein' => 2, 'total_carbs' => 22, 'total_fat' => 0, 'confidence' => 80]);
    for ($i = 0; $i < 100; $i++) {
        $access->analyze(new PhotoAnalysisContext($user, null, 'scan-'.$i, 'api', 'image'), function ($model) use ($result) {
            expect($model->model)->toBe('tested-pro');

            return $result;
        });
    }
    expect(fn () => $access->analyze(new PhotoAnalysisContext($user, null, 'over', 'web', 'image'), fn () => $result))->toThrow(PhotoLimitExceeded::class);
    config()->set('snap.enabled', false);
    $subscription->update(['ends_at' => now()->addDays(3)]);
    expect($access->entitlement($user)->used)->toBe(100);
    $this->travel(4)->days();
    expect($access->entitlement($user)->mode)->toBe('standard');
});

it('reserves in-flight requests and rejects replaying an ID for a different photo', function (): void {
    $access = resolve(CloudPhotoAnalyses::class);
    $user = User::factory()->create();
    $context = new PhotoAnalysisContext($user, null, 'pending', 'web', 'image');
    expect(fn () => $access->analyze($context, function () use ($access, $context): never {
        expect($access->entitlement($context->user)->remaining())->toBe(0);
        expect(fn () => $access->analyze($context, fn () => null))->toThrow(Symfony\Component\HttpKernel\Exception\HttpException::class);
        expect(fn () => $access->analyze(new PhotoAnalysisContext($context->user, null, 'pending', 'api', 'other-image'), fn () => null))->toThrow(Symfony\Component\HttpKernel\Exception\HttpException::class);
        throw new RuntimeException('Provider unavailable');
    }))->toThrow(RuntimeException::class);
    expect($access->entitlement($user)->remaining())->toBe(1);
});

it('hides legacy offers while preserving their prices and keeps photo costs outside the monthly AI budget', function (): void {
    $user = User::factory()->create();
    $legacy = App\Models\SubscriptionProduct::factory()->create(['name' => 'Supporter', 'price' => 9, 'yearly_price' => 89, 'stripe_price_id' => 'price_legacy']);
    $free = App\Models\SubscriptionProduct::factory()->create(['name' => 'Free']);
    $snap = App\Models\SubscriptionProduct::query()->updateOrCreate(['name' => 'Snap Pro'], ['name' => 'Snap Pro', 'price' => 9, 'stripe_price_id' => 'price_snap']);
    $offers = resolve(Acara\AcaraCore\Services\Billing\SnapSubscriptionOffers::class);
    expect($offers->available($legacy))->toBeFalse()
        ->and($offers->available($snap))->toBeTrue()
        ->and($offers->present($free)->features)->toContain('1 free trial photo scan, with no daily reset')
        ->and($legacy->fresh()->yearly_price)->toBe(89.0);
    App\Models\AiUsage::factory()->create(['user_id' => $user->id, 'agent' => App\Ai\Agents\FoodPhotoAnalyzerAgent::class, 'cost' => 3.00]);
    App\Models\AiUsage::factory()->create(['user_id' => $user->id, 'agent' => App\Ai\Agents\AgentRunner::class, 'cost' => 0.49]);
    $budget = resolve(Acara\AcaraCore\Services\Billing\CloudAiBudget::class)->forUser($user);
    expect($budget->used)->toBe(0.49)->and($budget->limit)->toBe(0.5);
    $user->subscriptions()->create(['type' => 'supporter', 'stripe_id' => 'sub_legacy', 'stripe_status' => 'active', 'stripe_price' => 'price_legacy', 'quantity' => 1]);
    expect(resolve(Acara\AcaraCore\Services\Billing\CloudAiBudget::class)->forUser($user))->toBeNull();
});

it('enforces the same free quota at the mobile API and replays a retry without another model call', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('mobile', ['chat:converse'])->plainTextToken;
    $photo = Illuminate\Http\UploadedFile::fake()->image('meal.jpg', 64, 64);
    $payload = ['photo' => 'data:image/jpeg;base64,'.base64_encode(file_get_contents($photo->getRealPath()))];
    $id = (string) Illuminate\Support\Str::uuid();
    App\Ai\Agents\FoodPhotoAnalyzerAgent::fake([['items' => [['name' => 'Rice', 'calories' => 100, 'protein' => 2, 'carbs' => 22, 'fat' => 0, 'portion' => '100 g']], 'total_calories' => 100, 'total_protein' => 2, 'total_carbs' => 22, 'total_fat' => 0, 'confidence' => 80]]);
    $this->withHeader('Authorization', 'Bearer '.$token)->withHeader('Idempotency-Key', $id)
        ->postJson(route('api.v2.snap-to-track.analyze'), $payload)->assertOk()->assertJsonPath('photoAllowance.used', 1);
    $this->postJson(route('api.v2.snap-to-track.analyze'), $payload)->assertOk()->assertJsonPath('photoAllowance.used', 1);
    $this->withHeader('Idempotency-Key', (string) Illuminate\Support\Str::uuid())
        ->postJson(route('api.v2.snap-to-track.analyze'), $payload)->assertStatus(402)->assertJsonPath('error', 'photo_limit_exceeded');
    expect(App\Models\AiUsage::query()->where('agent', App\Ai\Agents\FoodPhotoAnalyzerAgent::class)->count())->toBe(1);
});

it('selects only benchmark candidates that pass the measured release gates', function (string $failure): void {
    $baseline = App\Models\BenchmarkRun::factory()->create();
    $report = $baseline->toHarnessReport();
    $report->datasetHash = 'same-ground-truth-photos';
    $report->provider = 'gemini';
    $report->maxTokens = 4096;
    foreach ($report->paths as $path) {
        $path->metrics->mealCount = 30;
        $path->metrics->runCount = 90;
        $path->metrics->carbs->mae = 2.0;
        $path->metrics->calories->mae = 20.0;
        $path->metrics->itemization->recall = 0.9;
        $path->p95LatencyMs = 20_000;
        $path->costUsd = 2.16;
    }
    $baseline->update(['report' => $report->toArray()]);
    $report->analyzerVersion = 'claude-sonnet-5/p3';
    $report->provider = 'anthropic';
    match ($failure) {
        'dataset' => $report->datasetHash = 'different-photos',
        'cost' => $report->paths[0]->costUsd = 3.0,
        'latency' => $report->paths[0]->p95LatencyMs = 30_001,
        'recall' => $report->paths[0]->metrics->itemization->recall = 0.8,
        'carbs' => $report->paths[0]->metrics->carbs->mae = 3.0,
        'usage' => $report->paths[0]->unmeteredRuns = 1,
        default => null,
    };
    $candidate = App\Models\BenchmarkRun::factory()->create(['report' => $report->toArray()]);
    $command = $this->artisan('snap:select-model', ['baseline' => $baseline->id, 'candidates' => [$candidate->id]]);
    $failure === 'none' ? $command->assertSuccessful() : $command->assertFailed();
})->with(['none', 'dataset', 'cost', 'latency', 'recall', 'carbs', 'usage']);

it('keeps paid photo scans available during a sales rollback when other AI is exhausted', function (): void {
    $user = User::factory()->create(['stripe_id' => 'cus_rollback']);
    App\Models\SubscriptionProduct::query()->updateOrCreate(['name' => 'Snap Pro'], ['name' => 'Snap Pro', 'stripe_price_id' => 'price_rollback']);
    $user->subscriptions()->create(['type' => 'snap-pro', 'stripe_id' => 'sub_rollback', 'stripe_status' => 'active', 'stripe_price' => 'price_rollback', 'quantity' => 1]);
    Illuminate\Support\Facades\DB::table('snap_paid_periods')->insert(['subscription_id' => 'sub_rollback', 'customer_id' => 'cus_rollback', 'invoice_id' => 'in_rollback', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDays(29)]);
    App\Models\AiUsage::factory()->create(['user_id' => $user->id, 'agent' => App\Ai\Agents\AgentRunner::class, 'cost' => 1.0]);
    config()->set('snap.enabled', false);
    expect(fn () => resolve(App\Actions\Billing\EnforceAiUsageLimit::class)->handle($user))->toThrow(App\Exceptions\Billing\UsageLimitExceededException::class);
    $token = $user->createToken('mobile', ['chat:converse'])->plainTextToken;
    $photo = Illuminate\Http\UploadedFile::fake()->image('meal.jpg', 64, 64);
    App\Ai\Agents\FoodPhotoAnalyzerAgent::fake([['items' => [['name' => 'Rice', 'calories' => 100, 'protein' => 2, 'carbs' => 22, 'fat' => 0, 'portion' => '100 g']], 'total_calories' => 100, 'total_protein' => 2, 'total_carbs' => 22, 'total_fat' => 0, 'confidence' => 80]]);
    $this->withHeader('Authorization', 'Bearer '.$token)->postJson(route('api.v2.snap-to-track.analyze'), ['photo' => 'data:image/jpeg;base64,'.base64_encode(file_get_contents($photo->getRealPath()))])
        ->assertOk()->assertJsonPath('photoAllowance.mode', 'premium')->assertJsonPath('photoAllowance.used', 1);
});

it('does not activate a trial paywall without a working purchase configuration', function (string $missing): void {
    match ($missing) {
        'model' => config()->set('snap.model_approved', false),
        'secret' => config()->set('cashier.secret', null),
        'webhook' => config()->set('cashier.webhook.secret', null),
        'price' => App\Models\SubscriptionProduct::query()->where('name', 'Snap Pro')->update(['stripe_price_id' => '']),
        'sales' => App\Models\SubscriptionProduct::query()->where('name', 'Snap Pro')->update(['purchasable' => false]),
    };
    $access = resolve(CloudPhotoAnalyses::class);
    expect($access->enabled())->toBeFalse()
        ->and($access->entitlement(User::factory()->create())->limit)->toBeNull();
})->with(['model', 'secret', 'webhook', 'price', 'sales']);

it('gives existing free users a fresh trial without carrying old daily usage forward', function (): void {
    $user = User::factory()->create();
    $period = Illuminate\Support\Facades\DB::table('snap_photo_periods')->insertGetId([
        'subject' => 'user:'.$user->id, 'starts_at' => now()->startOfDay(), 'ends_at' => now()->endOfDay(), 'allowance' => 1, 'mode' => 'standard',
    ]);
    Illuminate\Support\Facades\DB::table('snap_photo_requests')->insert([
        'period_id' => $period, 'subject' => 'user:'.$user->id, 'request_key' => 'old-scan', 'fingerprint' => 'image',
        'status' => 'succeeded', 'attempt' => (string) Illuminate\Support\Str::uuid(), 'source' => 'web', 'created_at' => now(), 'expires_at' => now(),
    ]);
    $access = resolve(CloudPhotoAnalyses::class);
    expect($access->entitlement($user)->remaining())->toBe(1)
        ->and($access->entitlement($user)->mode)->toBe('trial');
});

it('does not restore a used trial when paid access ends and grants renewal scans only after payment', function (): void {
    $user = User::factory()->create(['stripe_id' => 'cus_lifecycle']);
    $access = resolve(CloudPhotoAnalyses::class);
    $result = FoodAnalysisData::from(['items' => [['name' => 'Rice', 'calories' => 100, 'protein' => 2, 'carbs' => 22, 'fat' => 0, 'portion' => '100 g']], 'total_calories' => 100, 'total_protein' => 2, 'total_carbs' => 22, 'total_fat' => 0, 'confidence' => 80]);
    $access->analyze(new PhotoAnalysisContext($user, null, 'trial', 'web', 'image'), fn () => $result);
    $subscription = $user->subscriptions()->create(['type' => 'snap-pro', 'stripe_id' => 'sub_lifecycle', 'stripe_status' => 'active', 'stripe_price' => 'price_ready', 'quantity' => 1]);
    $invoice = ['id' => 'in_lifecycle', 'customer' => 'cus_lifecycle', 'subscription' => 'sub_lifecycle', 'paid' => false, 'billing_reason' => 'subscription_create',
        'lines' => ['data' => [['price' => ['id' => 'price_ready'], 'proration' => false, 'period' => ['start' => now()->timestamp, 'end' => now()->addMonth()->timestamp]]]]];
    $listener = new Acara\AcaraCore\Listeners\SyncSnapBillingPeriod;
    $listener->handle(new Laravel\Cashier\Events\WebhookReceived(['type' => 'invoice.payment_succeeded', 'data' => ['object' => $invoice]]));
    expect($access->entitlement($user)->remaining())->toBe(0);
    $invoice['paid'] = true;
    $listener->handle(new Laravel\Cashier\Events\WebhookReceived(['type' => 'invoice.payment_succeeded', 'data' => ['object' => $invoice]]));
    expect($access->entitlement($user)->remaining())->toBe(100);
    $access->analyze(new PhotoAnalysisContext($user, null, 'paid', 'web', 'image'), fn () => $result);
    expect($access->entitlement($user)->remaining())->toBe(99);
    $this->travel(2)->months();
    expect($access->entitlement($user)->remaining())->toBe(0);
    $invoice['id'] = 'in_renewal';
    $invoice['billing_reason'] = 'subscription_cycle';
    $invoice['lines']['data'][0]['period'] = ['start' => now()->timestamp, 'end' => now()->addMonth()->timestamp];
    $listener->handle(new Laravel\Cashier\Events\WebhookReceived(['type' => 'invoice.payment_succeeded', 'data' => ['object' => $invoice]]));
    expect($access->entitlement($user)->remaining())->toBe(100);
    $subscription->update(['stripe_status' => 'canceled', 'ends_at' => now()->subSecond()]);
    expect($access->entitlement($user)->mode)->toBe('trial')->and($access->entitlement($user)->remaining())->toBe(0);
});
