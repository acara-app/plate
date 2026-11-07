<?php

declare(strict_types=1);

use App\Actions\Billing\ResolvePhotoAnalysisReservation;
use App\Data\Billing\PhotoAnalysisContext;
use App\Data\FoodAnalysisData;
use App\Models\AnalysisDraft;
use App\Models\SnapPhotoAccount;
use App\Models\SnapPhotoPeriod;
use App\Models\SnapPhotoRequest;
use App\Models\User;
use App\Services\Billing\DatabaseFailedPhotoAnalysisResolver;

covers(DatabaseFailedPhotoAnalysisResolver::class, ResolvePhotoAnalysisReservation::class, SnapPhotoPeriod::class);

it('recovers completed results or releases only the failed logical request reservation', function (array $changes, bool $released): void {
    SnapPhotoAccount::factory()->create(['subject' => 'guest:original', 'owner' => $changes['owner'] ?? null]);
    $analysis = FoodAnalysisData::from(AnalysisDraft::factory()->make()->payload);
    $status = $changes['status'] ?? 'pending';
    $reservations = SnapPhotoRequest::factory()->state([
        'subject' => $changes['subject'] ?? 'guest:original',
        'fingerprint' => 'original-photo',
        'guest_id' => 'original',
        'status' => $status,
        'created_at' => isset($changes['expired']) ? now()->subDays(2) : now(),
    ]);
    if ($status === 'succeeded') {
        $reservations = $reservations->succeeded($analysis);
    }

    $reservation = $reservations->create();
    $replacement = $reservations->create();
    $replacementBefore = $replacement->fresh()?->getAttributes();
    $context = new PhotoAnalysisContext(
        isset($changes['user_id']) ? User::factory()->make(['id' => $changes['user_id']]) : null,
        array_key_exists('guest_id', $changes) ? $changes['guest_id'] : 'original',
        $changes['request_id'] ?? $reservation->request_key,
        $changes['source'] ?? 'public_snap_to_track',
        $changes['fingerprint'] ?? 'original-photo',
    );

    foreach (range(1, 2) as $delivery) {
        $result = resolve(DatabaseFailedPhotoAnalysisResolver::class)->handle($context);

        expect($result?->toArray())->toBe(($changes['recover'] ?? false) ? $analysis->toArray() : null);
    }

    $reservation->refresh();
    expect($reservation->status)->toBe($released ? 'failed' : $status)
        ->and($reservation->result)->toBe($status === 'succeeded' ? $analysis->toJson() : null)
        ->and($replacement->fresh()?->getAttributes())->toBe($replacementBefore);
})->with([
    'guest request' => [[], true],
    'guest request after account registration' => [['owner' => 'user:7', 'subject' => 'user:7'], true],
    'account request' => [['user_id' => 7, 'subject' => 'user:7'], true],
    'account owns original guest request' => [['user_id' => 7, 'owner' => 'user:7'], true],
    'different account' => [['user_id' => 8, 'subject' => 'user:7', 'owner' => 'user:7'], false],
    'unowned guest request' => [['user_id' => 7], false],
    'different guest' => [['guest_id' => 'another'], false],
    'missing identity' => [['guest_id' => null], false],
    'different photo' => [['fingerprint' => 'another-photo'], false],
    'different request' => [['request_id' => 'another-request'], false],
    'different source' => [['source' => 'another-source'], false],
    'completed result' => [['status' => 'succeeded', 'recover' => true], false],
    'completed result belongs to another account' => [['status' => 'succeeded', 'user_id' => 8, 'subject' => 'user:7', 'owner' => 'user:7'], false],
    'completed result belongs to another photo' => [['status' => 'succeeded', 'fingerprint' => 'another-photo'], false],
    'expired completed result' => [['status' => 'succeeded', 'expired' => true], false],
    'already failed request' => [['status' => 'failed'], false],
]);
