<?php

declare(strict_types=1);

use App\Ai\Tools\LogHealthEntry;
use App\Enums\HealthEntrySource;
use App\Enums\HealthSyncType;
use App\Models\HealthDailyAggregate;
use App\Models\HealthSyncSample;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
use Laravel\Ai\Approvals\Approval;
use Laravel\Ai\Tools\Request;
use Tests\Helpers\TestJsonSchema;

covers(LogHealthEntry::class);

it('has correct name and description', function (): void {
    $tool = resolve(LogHealthEntry::class);

    expect($tool->name())->toBe('log_health_entry')
        ->and($tool->description())->toContain('Log a health entry');
});

it('has valid schema with enum constraints', function (): void {
    $tool = resolve(LogHealthEntry::class);
    $schema = $tool->schema(new TestJsonSchema);

    expect($schema)
        ->toHaveKey('log_type')
        ->toHaveKey('glucose_value')
        ->toHaveKey('glucose_unit')
        ->toHaveKey('carbs_grams')
        ->toHaveKey('notes')
        ->toHaveKey('weight')
        ->toHaveKey('weight_unit')
        ->toHaveKey('bp_systolic')
        ->toHaveKey('bp_diastolic')
        ->toHaveKey('exercise_type')
        ->toHaveKey('exercise_duration_minutes')
        ->toHaveKey('measured_at');
});

it('returns error if user is not authenticated', function (): void {
    Auth::shouldReceive('user')->andReturn(null);

    $tool = resolve(LogHealthEntry::class);
    $request = new Request(['log_type' => 'glucose', 'glucose_value' => 120]);

    $result = json_decode($tool->handle($request), true);

    expect($result)->toHaveKey('error', 'User not authenticated');
});

it('logs a glucose entry successfully', function (): void {
    $user = User::factory()->create();
    Auth::login($user);

    $tool = resolve(LogHealthEntry::class);
    $request = new Request([
        'log_type' => 'glucose',
        'glucose_value' => 140,
        'glucose_reading_type' => 'fasting',
    ]);

    $result = json_decode($tool->handle($request), true);

    expect($result)
        ->toHaveKey('success', true)
        ->toHaveKey('entry_id')
        ->toHaveKey('message');

    $sample = HealthSyncSample::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', HealthSyncType::BloodGlucose->value)
        ->first();

    expect($sample->value)->toBe(140.0)
        ->and($sample->entry_source)->toBe(HealthEntrySource::Chat);

    $aggregate = HealthDailyAggregate::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', HealthSyncType::BloodGlucose->value)
        ->where('local_date', now()->toDateString())
        ->first();

    expect($aggregate)->not->toBeNull();
});

it('logs a food entry with notes', function (): void {
    $user = User::factory()->create();
    Auth::login($user);

    $tool = resolve(LogHealthEntry::class);
    $request = new Request([
        'log_type' => 'food',
        'carbs_grams' => 45,
        'notes' => 'tsuivan',
    ]);

    $result = json_decode($tool->handle($request), true);

    expect($result)->toHaveKey('success', true);

    $sample = HealthSyncSample::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', HealthSyncType::Carbohydrates->value)
        ->first();

    expect((float) $sample->value)->toBe(45.0)
        ->and($sample->notes)->toBe('tsuivan')
        ->and($sample->entry_source)->toBe(HealthEntrySource::Chat);
});

it('logs a vitals entry with weight', function (): void {
    $user = User::factory()->create();
    Auth::login($user);

    $tool = resolve(LogHealthEntry::class);
    $request = new Request([
        'log_type' => 'vitals',
        'weight' => 81.65,
    ]);

    $result = json_decode($tool->handle($request), true);

    expect($result)->toHaveKey('success', true);

    $sample = HealthSyncSample::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', HealthSyncType::Weight->value)
        ->first();

    expect($sample->value)->toBe(81.65)
        ->and($sample->entry_source)->toBe(HealthEntrySource::Chat);
});

it('logs an exercise entry', function (): void {
    $user = User::factory()->create();
    Auth::login($user);

    $tool = resolve(LogHealthEntry::class);
    $request = new Request([
        'log_type' => 'exercise',
        'exercise_type' => 'walking',
        'exercise_duration_minutes' => 30,
    ]);

    $result = json_decode($tool->handle($request), true);

    expect($result)->toHaveKey('success', true);

    $sample = HealthSyncSample::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', HealthSyncType::ExerciseMinutes->value)
        ->first();

    expect($sample->value)->toBe(30.0)
        ->and($sample->entry_source)->toBe(HealthEntrySource::Chat)
        ->and($sample->metadata['exercise_type'])->toBe('walking');
});

it('logs plain water as water instead of as food', function (): void {
    $user = User::factory()->create();
    Auth::login($user);

    $result = json_decode(resolve(LogHealthEntry::class)->handle(new Request([
        'log_type' => 'water',
        'water_ml' => 200,
        'calories' => 0,
        'notes' => '200 ml plain water',
    ])), true);

    $sample = HealthSyncSample::query()->where('user_id', $user->id)->sole();

    expect($result)->toHaveKey('success', true)
        ->and([$sample->type_identifier, $sample->value, $sample->unit, $sample->entry_source])->toBe(['water', 200.0, 'mL', HealthEntrySource::Chat]);
});

it('asks the model for the water amount instead of asking the user to approve', function (?int $waterMl): void {
    $user = User::factory()->create();
    Auth::login($user);
    Context::add('chat.channel', 'web');

    $tool = resolve(LogHealthEntry::class);
    $request = new Request(['log_type' => 'water', 'water_ml' => $waterMl, 'notes' => '2 glasses of water']);

    expect($tool->shouldRequestApproval($request))->toBeNull()
        ->and(json_decode($tool->handle($request), true))->toMatchArray(['requires_clarification' => true, 'field' => 'water_ml'])
        ->and(HealthSyncSample::query()->where('user_id', $user->id)->exists())->toBeFalse();
})->with([
    'no amount' => [null],
    'nothing drunk' => [0],
    'more than five litres' => [5001],
]);

it('saves how the user slept and how stressed they feel to the day’s check-in', function (?string $measuredAt, string $dayStart): void {
    $this->travelTo(CarbonImmutable::parse('2026-11-09 08:00:00', 'Asia/Ulaanbaatar'));
    $user = User::factory()->create(['timezone' => 'Asia/Ulaanbaatar']);
    Auth::login($user);

    $result = json_decode(resolve(LogHealthEntry::class)->handle(new Request([
        'log_type' => 'check_in',
        'sleep_hours' => 6.5,
        'sleep_quality' => 1,
        'stress_level' => 3,
        'measured_at' => $measuredAt,
    ])), true);

    $answers = HealthSyncSample::query()
        ->where('user_id', $user->id)
        ->orderBy('type_identifier')
        ->get()
        ->map(fn (HealthSyncSample $answer): array => [$answer->type_identifier, $answer->value, $answer->entry_source?->value, $answer->measured_at->utc()->toDateTimeString()])
        ->all();

    expect($result)->toHaveKey('success', true)
        ->and($answers)->toBe([
            ['selfReportedSleep', 6.5, 'web', $dayStart],
            ['sleepQuality', 1.0, 'web', $dayStart],
            ['stressLevel', 3.0, 'web', $dayStart],
        ]);
})->with([
    'today' => [null, '2026-11-08 16:00:00'],
    'an earlier day' => ['2026-11-07T21:00:00+08:00', '2026-11-06 16:00:00'],
    'a day that has not started yet' => ['2026-11-10T09:00:00+08:00', '2026-11-08 16:00:00'],
]);

it('asks the model for a check-in answer instead of asking the user to approve', function (array $answers): void {
    $user = User::factory()->create();
    Auth::login($user);
    Context::add('chat.channel', 'web');

    $tool = resolve(LogHealthEntry::class);
    $request = new Request(['log_type' => 'check_in', ...$answers]);

    expect($tool->shouldRequestApproval($request))->toBeNull()
        ->and(json_decode($tool->handle($request), true))->toHaveKey('requires_clarification', true)
        ->and(HealthSyncSample::query()->where('user_id', $user->id)->exists())->toBeFalse();
})->with([
    'no answers' => [[]],
    'an unknown stress level' => [['stress_level' => 4]],
    'more sleep than a day has' => [['sleep_hours' => 25, 'stress_level' => 2]],
]);

it('asks for a nutrition estimate when a food entry has no calories or macros', function (): void {
    $user = User::factory()->create();
    Auth::login($user);

    $tool = resolve(LogHealthEntry::class);
    $result = json_decode($tool->handle(new Request([
        'log_type' => 'food',
        'notes' => 'apple',
    ])), true);

    expect($result)
        ->toHaveKey('requires_estimate', true)
        ->toHaveKey('field', 'calories')
        ->and(HealthSyncSample::query()->where('user_id', $user->id)->count())->toBe(0);
});

it('logs a food entry with an estimated calorie count', function (): void {
    $user = User::factory()->create();
    Auth::login($user);

    $tool = resolve(LogHealthEntry::class);
    $result = json_decode($tool->handle(new Request([
        'log_type' => 'food',
        'calories' => 175,
        'carbs_grams' => 30,
        'notes' => 'Two pancakes',
    ])), true);

    expect($result)->toHaveKey('success', true);

    $sample = HealthSyncSample::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', HealthSyncType::DietaryEnergy->value)
        ->first();

    expect($sample->value)->toBe(175.0)
        ->and($sample->notes)->toBe('Two pancakes');
});

it('returns error when vitals entry has no data', function (): void {
    $user = User::factory()->create();
    Auth::login($user);

    $tool = resolve(LogHealthEntry::class);
    $request = new Request([
        'log_type' => 'vitals',
    ]);

    $result = json_decode($tool->handle($request), true);

    expect($result)
        ->toHaveKey('error')
        ->toHaveKey('hint');
});

it('logs a medication entry', function (): void {
    $user = User::factory()->create();
    Auth::login($user);

    $tool = resolve(LogHealthEntry::class);
    $request = new Request([
        'log_type' => 'meds',
        'medication_name' => 'metformin',
        'medication_dosage' => '500mg',
    ]);

    $result = json_decode($tool->handle($request), true);

    expect($result)->toHaveKey('success', true);

    $sample = HealthSyncSample::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', HealthSyncType::Medication->value)
        ->first();

    expect($sample->metadata['medication_name'])->toBe('metformin')
        ->and($sample->metadata['medication_dosage'])->toBe('500mg')
        ->and($sample->entry_source)->toBe(HealthEntrySource::Chat);
});

it('converts mmol/L glucose values to mg/dL when unit is provided', function (): void {
    $user = User::factory()->create();
    Auth::login($user);

    $tool = resolve(LogHealthEntry::class);
    $request = new Request([
        'log_type' => 'glucose',
        'glucose_value' => 6.7,
        'glucose_unit' => 'mmol/L',
        'glucose_reading_type' => 'fasting',
    ]);

    $result = json_decode($tool->handle($request), true);

    expect($result)->toHaveKey('success', true);

    $sample = HealthSyncSample::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', HealthSyncType::BloodGlucose->value)
        ->first();

    expect($sample)->not->toBeNull()
        ->and($sample->unit)->toBe('mg/dL')
        ->and((float) $sample->value)->toBe(121.0);
});

it('asks for glucose unit when value looks like mmol/L but unit is missing', function (): void {
    $user = User::factory()->create();
    Auth::login($user);

    $tool = resolve(LogHealthEntry::class);
    $request = new Request([
        'log_type' => 'glucose',
        'glucose_value' => 6.7,
        'glucose_reading_type' => 'fasting',
    ]);

    $result = json_decode($tool->handle($request), true);

    expect($result)
        ->toHaveKey('requires_clarification', true)
        ->toHaveKey('field', 'glucose_unit');

    $sample = HealthSyncSample::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', HealthSyncType::BloodGlucose->value)
        ->first();

    expect($sample)->toBeNull();
});

it('converts weight from pounds to kilograms when unit is provided', function (): void {
    $user = User::factory()->create();
    Auth::login($user);

    $tool = resolve(LogHealthEntry::class);
    $request = new Request([
        'log_type' => 'vitals',
        'weight' => 176.37,
        'weight_unit' => 'lb',
    ]);

    $result = json_decode($tool->handle($request), true);

    expect($result)->toHaveKey('success', true);

    $sample = HealthSyncSample::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', HealthSyncType::Weight->value)
        ->first();

    expect($sample)->not->toBeNull()
        ->and($sample->unit)->toBe('kg')
        ->and(round((float) $sample->value, 1))->toBe(80.0);
});

it('requires approval before writing a health entry on conversational channels', function (string $channel): void {
    $user = User::factory()->create();
    Auth::login($user);

    Context::add('chat.channel', $channel);

    $approval = resolve(LogHealthEntry::class)->shouldRequestApproval(new Request([
        'log_type' => 'glucose',
        'glucose_value' => 140,
        'glucose_reading_type' => 'fasting',
        'summary' => 'Glucose 140 mg/dL, fasting',
    ]));

    expect($approval)->toBeInstanceOf(Approval::class)
        ->and($approval->reason)->toBe('Glucose 140 mg/dL, fasting')
        ->and(HealthSyncSample::query()->where('user_id', $user->id)->count())->toBe(0);
})->with(['web', 'telegram', 'mobile']);

it('falls back to a formatted summary when the model provides none', function (): void {
    $user = User::factory()->create();
    Auth::login($user);

    Context::add('chat.channel', 'web');

    $approval = resolve(LogHealthEntry::class)->shouldRequestApproval(new Request([
        'log_type' => 'glucose',
        'glucose_value' => 140,
        'glucose_reading_type' => 'fasting',
    ]));

    expect($approval->reason)->toContain('Glucose');
});

it('writes directly without approval outside conversational channels', function (): void {
    $user = User::factory()->create();
    Auth::login($user);

    $tool = resolve(LogHealthEntry::class);
    $request = new Request([
        'log_type' => 'glucose',
        'glucose_value' => 140,
        'glucose_reading_type' => 'fasting',
    ]);

    expect($tool->shouldRequestApproval($request))->toBeNull();

    $result = json_decode($tool->handle($request), true);

    expect($result)->toHaveKey('success', true)
        ->and(HealthSyncSample::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('asks the model for the glucose unit instead of asking the user to approve', function (): void {
    $user = User::factory()->create();
    Auth::login($user);

    Context::add('chat.channel', 'web');

    $tool = resolve(LogHealthEntry::class);
    $request = new Request([
        'log_type' => 'glucose',
        'glucose_value' => 6.7,
        'glucose_reading_type' => 'fasting',
    ]);

    expect($tool->shouldRequestApproval($request))->toBeNull();

    $result = json_decode($tool->handle($request), true);

    expect($result)->toHaveKey('requires_clarification', true)
        ->and(HealthSyncSample::query()->count())->toBe(0);
});

it('asks the model for a nutrition estimate instead of asking the user to approve', function (): void {
    $user = User::factory()->create();
    Auth::login($user);

    Context::add('chat.channel', 'web');

    $tool = resolve(LogHealthEntry::class);
    $request = new Request([
        'log_type' => 'food',
        'notes' => 'two pancakes',
    ]);

    expect($tool->shouldRequestApproval($request))->toBeNull();

    $result = json_decode($tool->handle($request), true);

    expect($result)->toHaveKey('requires_estimate', true)
        ->and(HealthSyncSample::query()->count())->toBe(0);
});

it('converts a weight given in pounds exactly once', function (): void {
    $user = User::factory()->create();
    Auth::login($user);

    $result = json_decode(resolve(LogHealthEntry::class)->handle(new Request([
        'log_type' => 'vitals',
        'weight' => 200,
        'weight_unit' => 'lb',
    ])), true);

    $sample = HealthSyncSample::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', HealthSyncType::Weight->value)
        ->firstOrFail();

    expect($result)->toHaveKey('success', true)
        ->and(round((float) $sample->value, 2))->toBe(90.72);
});

it('accepts common spellings of the weight unit', function (): void {
    $user = User::factory()->create();
    Auth::login($user);

    resolve(LogHealthEntry::class)->handle(new Request([
        'log_type' => 'vitals',
        'weight' => 200,
        'weight_unit' => 'lbs',
    ]));

    $sample = HealthSyncSample::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', HealthSyncType::Weight->value)
        ->firstOrFail();

    expect(round((float) $sample->value, 2))->toBe(90.72);
});

it('asks for the unit before logging a weight that reads like pounds', function (): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['height' => 175]);
    Auth::login($user);
    Context::add('chat.channel', 'web');

    $tool = resolve(LogHealthEntry::class);
    $request = new Request(['log_type' => 'vitals', 'weight' => 180]);

    $result = json_decode($tool->handle($request), true);

    expect($result)
        ->requires_clarification->toBeTrue()
        ->field->toBe('weight_unit')
        ->and($tool->shouldRequestApproval($request))->toBeNull()
        ->and(HealthSyncSample::query()->where('user_id', $user->id)->exists())->toBeFalse();
});

it('logs a weight without a unit as kilograms when it is plausible or there is no height to compare', function (?float $height): void {
    $user = User::factory()->create();
    UserProfile::factory()->for($user)->create(['height' => $height]);
    Auth::login($user);

    $result = json_decode(resolve(LogHealthEntry::class)->handle(new Request(['log_type' => 'vitals', 'weight' => 80])), true);

    expect($result)->toHaveKey('success', true);
})->with([
    'plausible for the height' => [175.0],
    'no height on file' => [null],
]);
