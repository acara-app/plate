<?php

declare(strict_types=1);

use App\Actions\RecordHealthEntryAction;
use App\Actions\SaveHealthLogAction;
use App\DataObjects\HealthLogData;
use App\Enums\HealthEntrySource;
use App\Enums\HealthEntryType;
use App\Models\HealthEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('saves health log data with provided measurement time', function () {
    $user = User::factory()->create();
    $measuredAt = Carbon::parse('2023-10-27 10:00:00');

    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Glucose,
        glucoseValue: 120,
    );

    // We can use the real RecordHealthEntryAction since we are in a feature test
    $recordAction = new RecordHealthEntryAction();
    $action = new SaveHealthLogAction($recordAction);

    $action->handle($user, $data, $measuredAt);

    $this->assertDatabaseHas(HealthEntry::class, [
        'user_id' => $user->id,
        'glucose_value' => 120,
        'measured_at' => '2023-10-27 10:00:00',
        'source' => HealthEntrySource::Telegram->value,
    ]);
});

it('saves health log data with default measurement time', function () {
    $user = User::factory()->create();
    Carbon::setTestNow('2023-10-28 12:00:00');

    $data = new HealthLogData(
        isHealthData: true,
        logType: HealthEntryType::Glucose,
        glucoseValue: 110,
    );

    $recordAction = new RecordHealthEntryAction();
    $action = new SaveHealthLogAction($recordAction);

    $action->handle($user, $data);

    $this->assertDatabaseHas(HealthEntry::class, [
        'user_id' => $user->id,
        'measured_at' => '2023-10-28 12:00:00',
        'source' => HealthEntrySource::Telegram->value,
    ]);
});
