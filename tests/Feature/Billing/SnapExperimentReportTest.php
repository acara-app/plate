<?php

declare(strict_types=1);

use App\Contracts\Billing\TracksPhotoConversion;
use App\Models\User;
use App\Services\Billing\NullPhotoConversion;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('reports deduplicated paid conversion and net contribution without declaring an early winner', function (): void {
    if (resolve(TracksPhotoConversion::class) instanceof NullPhotoConversion) {
        $this->markTestSkipped('Requires the optional photo conversion integration.');
    }
    config(['snap.experiment.starts_at' => now()->subDays(15)->toIso8601String()]);
    $user = User::factory()->create();
    $id = (string) Str::uuid();
    DB::table('snap_experiment_assignments')->insert([
        'id' => $id, 'subject' => 'user:'.$user->id, 'experiment' => 'snap-habit-v1',
        'variant' => 'B', 'assigned_at' => now()->subDays(14),
    ]);
    foreach ([['initial_payment', 299, 39], ['renewal', 299, 39], ['refund', 100, null]] as $i => [$kind, $amount, $fee]) {
        DB::table('snap_billing_events')->insert([
            'event_key' => 'event:'.$i, 'stripe_event_id' => 'evt_'.$i, 'kind' => $kind,
            'customer_id' => 'cus_report', 'invoice_id' => $i === 1 ? 'in_renewal' : 'in_first', 'subject' => 'user:'.$user->id,
            'assignment_id' => $id, 'amount' => $amount, 'fee' => $fee, 'currency' => 'usd', 'live' => false,
            'occurred_at' => now()->subDays(10),
        ]);
    }

    Artisan::call('snap:experiment-report', ['--test' => true, '--json' => true]);
    $report = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

    expect($report['groups']['B']['enrolled'])->toBe(1)
        ->and($report['groups']['B']['paid_14d'])->toBe(1)
        ->and($report['groups']['B']['contribution_usd'])->toBe(4.2)
        ->and($report['mature'])->toBeFalse()
        ->and($report['decision'])->toBe('retain_A_inconclusive_or_negative');
});

it('does not approve a variant while a refund still needs payment reconciliation', function (): void {
    if (resolve(TracksPhotoConversion::class) instanceof NullPhotoConversion) {
        $this->markTestSkipped('Requires the optional photo conversion integration.');
    }
    config(['snap.experiment.starts_at' => now()->subDays(90)->toIso8601String()]);
    $user = User::factory()->create();
    DB::table('snap_experiment_assignments')->insert([
        'id' => (string) Str::uuid(), 'subject' => 'user:'.$user->id, 'experiment' => 'snap-habit-v1',
        'variant' => 'B', 'assigned_at' => now()->subDays(89),
    ]);
    DB::table('snap_billing_events')->insert([
        'event_key' => 'refund:unknown', 'stripe_event_id' => 'evt_refund', 'kind' => 'refund',
        'customer_id' => 'cus_report', 'subject' => 'user:'.$user->id,
        'amount' => 299, 'currency' => 'usd', 'live' => false, 'occurred_at' => now()->subDays(80),
    ]);

    Artisan::call('snap:experiment-report', ['--test' => true, '--json' => true]);
    $report = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
    expect($report['financial_data_complete'])->toBeFalse()
        ->and($report['decision'])->toBe('retain_A_inconclusive_or_negative');
});

it('adopts the habit offer only after maturation with positive conversion evidence and complete fees', function (bool $completeFees): void {
    if (resolve(TracksPhotoConversion::class) instanceof NullPhotoConversion) {
        $this->markTestSkipped('Requires the optional photo conversion integration.');
    }
    config(['snap.experiment.starts_at' => now()->subDays(90)->toIso8601String()]);
    foreach (['A', 'B'] as $variant) {
        foreach (range(1, 20) as $participant) {
            $subject = 'fixture:'.$variant.':'.$participant;
            DB::table('snap_experiment_assignments')->insert([
                'id' => (string) Str::uuid(), 'subject' => $subject, 'experiment' => 'snap-habit-v1',
                'variant' => $variant, 'assigned_at' => now()->subDays(89),
            ]);
            if ($variant === 'B' && $participant <= 12) {
                DB::table('snap_billing_events')->insert([
                    'event_key' => $subject, 'stripe_event_id' => $subject, 'kind' => 'initial_payment',
                    'customer_id' => $subject, 'invoice_id' => $subject, 'subject' => $subject,
                    'amount' => 299, 'fee' => $completeFees ? 39 : null, 'currency' => 'usd',
                    'live' => false, 'occurred_at' => now()->subDays(80),
                ]);
            }
        }
    }
    Artisan::call('snap:experiment-report', ['--test' => true, '--json' => true]);
    $report = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
    expect($report['mature'])->toBeTrue()
        ->and($report['groups']['A']['enrolled'])->toBe(20)
        ->and($report['groups']['B']['paid_14d'])->toBe(12)
        ->and($report['conversion_difference_95_ci'][0])->toBeGreaterThan(0)
        ->and($report['decision'])->toBe($completeFees ? 'adopt_B' : 'retain_A_inconclusive_or_negative');
})->with([true, false]);

it('counts first payments rather than renewals or returning customers as conversions', function (string $history, int $expected): void {
    if (resolve(TracksPhotoConversion::class) instanceof NullPhotoConversion) {
        $this->markTestSkipped('Requires the optional photo conversion integration.');
    }
    config(['snap.experiment.starts_at' => now()->subDays(15)->toIso8601String()]);
    $subject = 'user:'.User::factory()->create()->id;
    DB::table('snap_photo_accounts')->insert(['subject' => $subject, 'created_at' => now()->subDays(14)]);
    DB::table('snap_analytics_events')->insert([
        'event_key' => 'visit:fixture', 'name' => 'snap_to_track_visit',
        'properties' => json_encode(['snap_subject' => hash('sha256', $subject)]), 'created_at' => now()->subDays(14),
    ]);
    DB::table('snap_experiment_assignments')->insert([
        'id' => (string) Str::uuid(), 'subject' => $subject, 'experiment' => 'snap-habit-v1',
        'variant' => 'A', 'assigned_at' => now()->subDays(14),
    ]);
    if ($history === 'returning') {
        DB::table('snap_billing_events')->insert([
            'event_key' => 'old', 'stripe_event_id' => 'old', 'kind' => 'initial_payment',
            'customer_id' => 'fixture', 'subject' => $subject, 'amount' => 299,
            'fee' => 39, 'currency' => 'usd', 'live' => false, 'occurred_at' => now()->subDays(40),
        ]);
    }
    DB::table('snap_billing_events')->insert([
        'event_key' => 'recent', 'stripe_event_id' => 'recent', 'kind' => $history === 'renewal' ? 'renewal' : 'initial_payment',
        'customer_id' => 'fixture', 'subject' => $subject, 'amount' => 299,
        'fee' => 39, 'currency' => 'usd', 'live' => false, 'occurred_at' => now()->subDays(10),
    ]);
    Artisan::call('snap:experiment-report', ['--test' => true, '--json' => true]);
    $report = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);
    expect($report['tracked_visitors'])->toBe(1)
        ->and($report['groups']['A']['paid_14d'])->toBe($expected)
        ->and($report['paid_per_1000_visitors_30d'])->toEqual($expected * 1000);
})->with([['new', 1], ['renewal', 0], ['returning', 0]]);
