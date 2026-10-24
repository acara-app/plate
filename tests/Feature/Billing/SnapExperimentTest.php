<?php

declare(strict_types=1);

use App\Contracts\Billing\ManagesPhotoAnalyses;
use App\Contracts\Billing\ManagesPhotoPreview;
use App\Contracts\Billing\TracksPhotoConversion;
use App\Data\Billing\PhotoAnalysisContext;
use App\Data\FoodAnalysisData;
use App\Models\SubscriptionProduct;
use App\Models\User;
use App\Services\Billing\NullPhotoAnalyses;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function (): void {
    if (resolve(ManagesPhotoAnalyses::class) instanceof NullPhotoAnalyses) {
        $this->markTestSkipped('Requires the Cloud billing integration.');
    }
    config([
        'plate.enable_premium_upgrades' => true,
        'cashier.key' => 'pk_test_fake', 'cashier.secret' => 'sk_test_fake', 'cashier.webhook.secret' => 'whsec_test',
        'snap.experiment.enabled' => true,
        'snap.two_free_scans_enabled' => true,
        'snap.experiment.starts_at' => now()->subMinute()->toIso8601String(),
    ]);
});

function prepareSnapExperiment(): string
{
    SubscriptionProduct::factory()->create([
        'name' => 'Starter',
        'price' => 2.99, 'stripe_price_id' => 'price_starter',
        'stripe_lookup_key' => 'acara-plate-starter-monthly-v1', 'purchasable' => true,
    ]);
    $guest = (string) Str::uuid();
    resolve(TracksPhotoConversion::class)->track(null, $guest, 'visit', 'visit');

    return $guest;
}

function completeSnapScan(?User $user, string $guest, ?string $requestId = null): FoodAnalysisData
{
    return resolve(ManagesPhotoAnalyses::class)->analyze(
        new PhotoAnalysisContext($user, $guest, $requestId ?? (string) Str::uuid(), 'public_snap_to_track', 'photo-fixture'),
        fn () => FoodAnalysisData::from([
            'items' => [['name' => 'Rice', 'calories' => 130, 'protein' => 3, 'carbs' => 28, 'fat' => 1, 'portion' => '100 g']],
            'total_calories' => 130, 'total_protein' => 3, 'total_carbs' => 28, 'total_fat' => 1, 'confidence' => 80,
        ]),
    );
}

it('assigns a new scanner once after two successes and preserves the assignment after signup', function (): void {
    $guest = prepareSnapExperiment();
    expect(resolve(ManagesPhotoAnalyses::class)->enabled())->toBeTrue();
    completeSnapScan(null, $guest);
    expect(DB::table('snap_experiment_assignments')->count())->toBe(0);
    $requestId = (string) Str::uuid();
    completeSnapScan(null, $guest, $requestId);
    completeSnapScan(null, $guest, $requestId);
    $assignment = DB::table('snap_experiment_assignments')->sole();
    $user = User::factory()->create();

    $allowance = resolve(ManagesPhotoAnalyses::class)->entitlement($user, $guest);

    expect($allowance->remaining)->toBe(0)
        ->and($allowance->variant)->toBe($assignment->variant)
        ->and(DB::table('snap_experiment_assignments')->sole()->id)->toBe($assignment->id)
        ->and(DB::table('snap_photo_requests')->where('status', 'succeeded')->count())->toBe(2);
});

it('grants one preview scan per 24 hours without banking scans or resetting activation', function (): void {
    $guest = prepareSnapExperiment();
    completeSnapScan(null, $guest);
    completeSnapScan(null, $guest);
    DB::table('snap_experiment_assignments')->update(['variant' => 'B']);
    $user = User::factory()->create();
    $access = resolve(ManagesPhotoAnalyses::class);
    $preview = resolve(ManagesPhotoPreview::class);

    $preview->activate($user, $guest);
    $started = DB::table('snap_experiment_assignments')->sole()->preview_started_at;
    completeSnapScan($user, $guest);
    expect($access->entitlement($user, $guest)->remaining)->toBe(0);
    expect(fn () => completeSnapScan($user, $guest))->toThrow(App\Exceptions\Billing\PhotoLimitExceeded::class);
    $this->travel(24)->hours();
    config(['snap.experiment.enabled' => false]);
    expect($access->entitlement($user, $guest)->remaining)->toBe(1);
    $this->travel(24)->hours();
    expect($access->entitlement($user, $guest)->remaining)->toBe(1);
    $preview->activate($user, $guest);
    expect(DB::table('snap_experiment_assignments')->sole()->preview_started_at)->toBe($started);
    completeSnapScan($user, $guest);
    $this->travel(24)->hours();
    expect($access->entitlement($user, $guest)->preview->status)->toBe('expired')
        ->and($access->entitlement($user, $guest)->remaining)->toBe(0);
});

it('does not enroll existing or internal visitors', function (string $reason): void {
    $guest = prepareSnapExperiment();
    $user = null;
    if ($reason === 'existing_guest') {
        DB::table('snap_photo_accounts')->where('subject', 'guest:'.$guest)->update(['created_at' => null]);
        $user = User::factory()->create();
    } elseif ($reason === 'existing_account') {
        $user = User::factory()->create(['created_at' => now()->subMonth()]);
    } elseif ($reason === 'internal') {
        $user = User::factory()->create();
        config(['snap.experiment.internal_user_ids' => [(string) $user->id]]);
    } else {
        config(['snap.experiment.enabled' => false]);
    }

    completeSnapScan($user, $guest);
    completeSnapScan($user, $guest);

    expect(DB::table('snap_experiment_assignments')->count())->toBe(0);
})->with(['existing_guest', 'existing_account', 'internal', 'disabled']);

it('does not spend another scan on failures or duplicate requests', function (): void {
    $guest = prepareSnapExperiment();
    $access = resolve(ManagesPhotoAnalyses::class);
    $context = new PhotoAnalysisContext(null, $guest, (string) Str::uuid(), 'public_snap_to_track', 'photo-fixture');
    expect(fn () => $access->analyze($context, fn () => throw new RuntimeException('Unavailable')))->toThrow(RuntimeException::class);
    expect($access->entitlement(null, $guest)->remaining)->toBe(2);
    completeSnapScan(null, $guest, $context->requestId);
    completeSnapScan(null, $guest, $context->requestId);

    expect($access->entitlement(null, $guest)->remaining)->toBe(1);
    expect(fn () => $access->analyze(new PhotoAnalysisContext(null, $guest, $context->requestId, 'public_snap_to_track', 'different-photo'), fn () => null))
        ->toThrow(Symfony\Component\HttpKernel\Exception\HttpException::class);
});

it('reserves in-flight scans so overlapping requests cannot exceed two', function (): void {
    $guest = prepareSnapExperiment();
    $access = resolve(ManagesPhotoAnalyses::class);
    $context = new PhotoAnalysisContext(null, $guest, (string) Str::uuid(), 'public_snap_to_track', 'first');

    $access->analyze($context, function () use ($guest): FoodAnalysisData {
        $second = completeSnapScan(null, $guest);
        expect(fn () => completeSnapScan(null, $guest))->toThrow(App\Exceptions\Billing\PhotoLimitExceeded::class);

        return $second;
    });

    expect($access->entitlement(null, $guest)->remaining)->toBe(0)
        ->and(DB::table('snap_photo_requests')->where('status', 'succeeded')->count())->toBe(2);
});

it('preserves used scans when the two-scan baseline is enabled', function (): void {
    $guest = prepareSnapExperiment();
    config(['snap.two_free_scans_enabled' => false]);
    completeSnapScan(null, $guest);
    $access = resolve(ManagesPhotoAnalyses::class);
    expect($access->entitlement(null, $guest)->remaining)->toBe(0);
    expect(DB::table('snap_experiment_assignments')->count())->toBe(0);

    config(['snap.two_free_scans_enabled' => true]);
    expect($access->entitlement(null, $guest)->remaining)->toBe(1);
    completeSnapScan(null, $guest);
    expect($access->entitlement(null, $guest)->remaining)->toBe(0);
});

it('uses the account assignment when a returning visitor brings a different guest assignment', function (): void {
    $first = prepareSnapExperiment();
    completeSnapScan(null, $first);
    completeSnapScan(null, $first);
    DB::table('snap_experiment_assignments')->update(['variant' => 'A']);
    $user = User::factory()->create();
    $access = resolve(ManagesPhotoAnalyses::class);
    $access->entitlement($user, $first);
    $accountAssignment = DB::table('snap_experiment_assignments')->sole()->id;
    $second = (string) Str::uuid();
    resolve(TracksPhotoConversion::class)->track(null, $second, 'visit', 'visit');
    completeSnapScan(null, $second);
    completeSnapScan(null, $second);
    DB::table('snap_experiment_assignments')->where('subject', 'guest:'.$second)->update(['variant' => 'B']);

    $allowance = $access->entitlement($user, $second);

    expect($allowance->variant)->toBe('A')->and($allowance->remaining)->toBe(0);
    $this->assertDatabaseHas('snap_experiment_assignments', ['id' => $accountAssignment, 'subject' => 'user:'.$user->id, 'excluded_at' => null]);
    $this->assertDatabaseHas('snap_experiment_assignments', ['subject' => 'guest:'.$second, 'excluded_reason' => 'merged']);
});

it('excludes a previous payer identified by reconciled invoices after guest sign-in', function (): void {
    $guest = prepareSnapExperiment();
    completeSnapScan(null, $guest);
    completeSnapScan(null, $guest);
    $user = User::factory()->create();
    DB::table('snap_billing_events')->insert([
        'event_key' => 'invoice:prior', 'stripe_event_id' => 'evt_prior', 'kind' => 'initial_payment',
        'customer_id' => 'cus_prior', 'subject' => 'user:'.$user->id, 'amount' => 299,
        'occurred_at' => now()->subDay(),
    ]);

    expect(resolve(ManagesPhotoAnalyses::class)->entitlement($user, $guest)->variant)->toBeNull();
    $this->assertDatabaseHas('snap_experiment_assignments', ['subject' => 'user:'.$user->id, 'excluded_reason' => 'existing_or_internal_account']);
});
