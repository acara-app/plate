<?php

declare(strict_types=1);

use App\Actions\RecordHealthSampleAction;
use App\DataObjects\HealthLogData;
use App\Enums\HealthEntrySource;
use App\Enums\HealthEntryType;
use App\Models\User;

covers(RecordHealthSampleAction::class);

it('creates health sync samples from vitals data', function (): void {
    $user = User::factory()->create();
    $action = new RecordHealthSampleAction();

    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Vitals,
        bpSystolic: 120,
        bpDiastolic: 80,
    );

    $primary = $action->handle($data, $user, HealthEntrySource::Web);

    expect($primary->type_identifier)->toBe('bloodPressureSystolic')
        ->and($primary->value)->toBe(120.0)
        ->and($primary->group_id)->not->toBeNull();

    $this->assertDatabaseHas('health_sync_samples', [
        'user_id' => $user->id,
        'type_identifier' => 'bloodPressureDiastolic',
        'value' => 80,
        'group_id' => $primary->group_id,
    ]);
});

it('throws exception when no samples to record', function (): void {
    $user = User::factory()->create();
    $action = new RecordHealthSampleAction();

    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Vitals,
    );

    $action->handle($data, $user, HealthEntrySource::Web);
})->throws(InvalidArgumentException::class, 'No health samples to record.');
