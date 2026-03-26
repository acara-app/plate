<?php

declare(strict_types=1);

use App\Enums\GlucoseReadingType;
use App\Enums\HealthEntrySource;
use App\Enums\HealthSyncType;
use App\Models\HealthEntry;
use App\Models\HealthSyncSample;
use App\Models\MobileSyncDevice;
use App\Models\User;
use Illuminate\Support\Facades\Date;

/**
 * @param  array<int, array<string, mixed>>  $entries
 */
function encryptSyncPayload(array $entries, string $base64Key, string $deviceIdentifier = 'test-uuid'): string
{
    $json = json_encode([
        'device_identifier' => $deviceIdentifier,
        'entries' => $entries,
    ]);

    $key = base64_decode($base64Key);
    $nonce = random_bytes(12);
    $tag = '';

    $ciphertext = openssl_encrypt($json, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag);

    return base64_encode($nonce.$ciphertext.$tag);
}

it('requires authentication', function (): void {
    $this->postJson('/api/v1/sync/health-entries', [
        'device_identifier' => 'test-uuid',
        'encrypted_payload' => 'some-payload',
    ])->assertUnauthorized();
});

it('validates device_identifier is required', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/sync/health-entries', [
            'encrypted_payload' => 'some-payload',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['device_identifier']);
});

it('validates encrypted_payload is required', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/sync/health-entries', [
            'device_identifier' => 'test-uuid',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['encrypted_payload']);
});

it('returns 404 when device does not belong to user', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    MobileSyncDevice::factory()->for($otherUser)->paired()->create([
        'device_identifier' => 'other-uuid',
    ]);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/sync/health-entries', [
            'device_identifier' => 'other-uuid',
            'encrypted_payload' => 'dummy',
        ])
        ->assertNotFound();
});

it('validates decrypted entry structure', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    /** @var string $encryptionKey */
    $encryptionKey = $device->encryption_key;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/sync/health-entries', [
            'device_identifier' => 'test-uuid',
            'encrypted_payload' => encryptSyncPayload(
                [['value' => 5.5]],
                $encryptionKey,
            ),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['entries.0.type', 'entries.0.date']);
});

it('syncs blood glucose to health entry with random reading type', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    /** @var string $encryptionKey */
    $encryptionKey = $device->encryption_key;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/sync/health-entries', [
            'device_identifier' => 'test-uuid',
            'encrypted_payload' => encryptSyncPayload([
                [
                    'type' => HealthSyncType::BloodGlucose->value,
                    'value' => 5.5,
                    'unit' => 'mmol/L',
                    'date' => '2026-03-25T10:30:00Z',
                    'source' => 'Apple Watch',
                ],
            ], $encryptionKey),
        ])
        ->assertOk()
        ->assertJson([
            'message' => 'Synced successfully.',
            'health_entries_created' => 1,
            'health_entries_updated' => 0,
            'samples_created' => 0,
            'samples_updated' => 0,
        ]);

    expect(HealthEntry::query()->where('user_id', $user->id)->first())
        ->glucose_value->toBe(5.5)
        ->glucose_reading_type->toBe(GlucoseReadingType::Random)
        ->source->toBe(HealthEntrySource::MobileSync);
});

it('syncs blood pressure to a single health entry row', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    /** @var string $encryptionKey */
    $encryptionKey = $device->encryption_key;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/sync/health-entries', [
            'device_identifier' => 'test-uuid',
            'encrypted_payload' => encryptSyncPayload([
                [
                    'type' => HealthSyncType::BloodPressureSystolic->value,
                    'value' => 120,
                    'unit' => 'mmHg',
                    'date' => '2026-03-25T10:30:00Z',
                ],
                [
                    'type' => HealthSyncType::BloodPressureDiastolic->value,
                    'value' => 80,
                    'unit' => 'mmHg',
                    'date' => '2026-03-25T10:30:00Z',
                ],
            ], $encryptionKey),
        ])
        ->assertOk()
        ->assertJson([
            'health_entries_created' => 1,
            'health_entries_updated' => 1,
        ]);

    expect(HealthEntry::query()->where('user_id', $user->id)->count())->toBe(1);

    $bp = HealthEntry::query()->where('user_id', $user->id)
        ->where('sync_type', HealthSyncType::BloodPressure->value)
        ->first();

    expect($bp->blood_pressure_systolic)->toBe(120)
        ->and($bp->blood_pressure_diastolic)->toBe(80)
        ->and($bp->source)->toBe(HealthEntrySource::MobileSync);
});

it('syncs weight to health entry', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    /** @var string $encryptionKey */
    $encryptionKey = $device->encryption_key;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/sync/health-entries', [
            'device_identifier' => 'test-uuid',
            'encrypted_payload' => encryptSyncPayload([
                [
                    'type' => HealthSyncType::Weight->value,
                    'value' => 75.5,
                    'unit' => 'kg',
                    'date' => '2026-03-25T10:30:00Z',
                ],
            ], $encryptionKey),
        ])
        ->assertOk()
        ->assertJson(['health_entries_created' => 1]);

    expect(HealthEntry::query()->where('user_id', $user->id)->first())
        ->weight->toBe(75.5);
});

it('syncs macros to health entry', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    /** @var string $encryptionKey */
    $encryptionKey = $device->encryption_key;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/sync/health-entries', [
            'device_identifier' => 'test-uuid',
            'encrypted_payload' => encryptSyncPayload([
                [
                    'type' => HealthSyncType::Carbohydrates->value,
                    'value' => 50.0,
                    'unit' => 'g',
                    'date' => '2026-03-25T12:00:00Z',
                ],
                [
                    'type' => HealthSyncType::Protein->value,
                    'value' => 25.0,
                    'unit' => 'g',
                    'date' => '2026-03-25T12:00:00Z',
                ],
                [
                    'type' => HealthSyncType::TotalFat->value,
                    'value' => 15.0,
                    'unit' => 'g',
                    'date' => '2026-03-25T12:00:00Z',
                ],
                [
                    'type' => HealthSyncType::DietaryEnergy->value,
                    'value' => 450,
                    'unit' => 'kcal',
                    'date' => '2026-03-25T12:00:00Z',
                ],
            ], $encryptionKey),
        ])
        ->assertOk();

    $carbsEntry = HealthEntry::query()->where('user_id', $user->id)
        ->where('sync_type', HealthSyncType::Carbohydrates->value)
        ->where('measured_at', '2026-03-25 12:00:00')
        ->first();

    $proteinEntry = HealthEntry::query()->where('user_id', $user->id)
        ->where('sync_type', HealthSyncType::Protein->value)
        ->where('measured_at', '2026-03-25 12:00:00')
        ->first();

    $fatEntry = HealthEntry::query()->where('user_id', $user->id)
        ->where('sync_type', HealthSyncType::TotalFat->value)
        ->where('measured_at', '2026-03-25 12:00:00')
        ->first();

    $caloriesEntry = HealthEntry::query()->where('user_id', $user->id)
        ->where('sync_type', HealthSyncType::DietaryEnergy->value)
        ->where('measured_at', '2026-03-25 12:00:00')
        ->first();

    expect($carbsEntry->carbs_grams)->toBe('50.00');
    expect($proteinEntry->protein_grams)->toBe('25.00');
    expect($fatEntry->fat_grams)->toBe('15.00');
    expect($caloriesEntry->calories)->toBe(450);
});

it('syncs exercise minutes to health entry', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    /** @var string $encryptionKey */
    $encryptionKey = $device->encryption_key;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/sync/health-entries', [
            'device_identifier' => 'test-uuid',
            'encrypted_payload' => encryptSyncPayload([
                [
                    'type' => HealthSyncType::ExerciseMinutes->value,
                    'value' => 30,
                    'unit' => 'min',
                    'date' => '2026-03-25T14:00:00Z',
                ],
                [
                    'type' => HealthSyncType::Workouts->value,
                    'value' => 45,
                    'unit' => 'min',
                    'date' => '2026-03-25T15:00:00Z',
                ],
            ], $encryptionKey),
        ])
        ->assertOk();

    $exercise = HealthEntry::query()->where('user_id', $user->id)
        ->where('sync_type', HealthSyncType::ExerciseMinutes->value)
        ->where('measured_at', '2026-03-25 14:00:00')
        ->first();

    $workout = HealthEntry::query()->where('user_id', $user->id)
        ->where('sync_type', HealthSyncType::Workouts->value)
        ->where('measured_at', '2026-03-25 15:00:00')
        ->first();

    expect($exercise->exercise_type)->toBe('exercise');
    expect($exercise->exercise_duration_minutes)->toBe(30);

    expect($workout->exercise_type)->toBe('workout');
    expect($workout->exercise_duration_minutes)->toBe(45);
});

it('syncs unmapped types to health sync samples table', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    /** @var string $encryptionKey */
    $encryptionKey = $device->encryption_key;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/sync/health-entries', [
            'device_identifier' => 'test-uuid',
            'encrypted_payload' => encryptSyncPayload([
                [
                    'type' => 'heartRate',
                    'value' => 72,
                    'unit' => 'bpm',
                    'date' => '2026-03-25T10:30:00Z',
                    'source' => 'Apple Watch',
                ],
                [
                    'type' => 'stepCount',
                    'value' => 5000,
                    'unit' => 'count',
                    'date' => '2026-03-25T10:30:00Z',
                ],
            ], $encryptionKey),
        ])
        ->assertOk()
        ->assertJson([
            'health_entries_created' => 0,
            'health_entries_updated' => 0,
            'samples_created' => 2,
            'samples_updated' => 0,
        ]);

    expect(HealthSyncSample::query()->where('user_id', $user->id)->count())->toBe(2);

    $heartRate = HealthSyncSample::query()->where('user_id', $user->id)
        ->where('type_identifier', 'heartRate')
        ->first();

    expect($heartRate->value)->toBe(72.0);
    expect($heartRate->unit)->toBe('bpm');
    expect($heartRate->source)->toBe('Apple Watch');
    expect($heartRate->mobile_sync_device_id)->toBe($device->id);
});

it('syncs sleep stages to health sync samples', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    /** @var string $encryptionKey */
    $encryptionKey = $device->encryption_key;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/sync/health-entries', [
            'device_identifier' => 'test-uuid',
            'encrypted_payload' => encryptSyncPayload([
                ['type' => 'timeInBed', 'value' => 480, 'unit' => 'min', 'date' => '2026-03-25T07:00:00Z'],
                ['type' => 'timeAsleep', 'value' => 420, 'unit' => 'min', 'date' => '2026-03-25T07:00:00Z'],
                ['type' => 'remSleep', 'value' => 90, 'unit' => 'min', 'date' => '2026-03-25T07:00:00Z'],
                ['type' => 'coreSleep', 'value' => 210, 'unit' => 'min', 'date' => '2026-03-25T07:00:00Z'],
                ['type' => 'deepSleep', 'value' => 120, 'unit' => 'min', 'date' => '2026-03-25T07:00:00Z'],
                ['type' => 'awakeTime', 'value' => 60, 'unit' => 'min', 'date' => '2026-03-25T07:00:00Z'],
            ], $encryptionKey),
        ])
        ->assertOk()
        ->assertJson(['samples_created' => 6]);

    expect(HealthSyncSample::query()->where('user_id', $user->id)->count())->toBe(6);
});

it('upserts on duplicate user type measured_at', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    /** @var string $encryptionKey */
    $encryptionKey = $device->encryption_key;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/sync/health-entries', [
            'device_identifier' => 'test-uuid',
            'encrypted_payload' => encryptSyncPayload([
                [
                    'type' => HealthSyncType::BloodGlucose->value,
                    'value' => 5.5,
                    'unit' => 'mmol/L',
                    'date' => '2026-03-25T10:30:00Z',
                ],
            ], $encryptionKey),
        ])
        ->assertOk()
        ->assertJson(['health_entries_created' => 1]);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/sync/health-entries', [
            'device_identifier' => 'test-uuid',
            'encrypted_payload' => encryptSyncPayload([
                [
                    'type' => HealthSyncType::BloodGlucose->value,
                    'value' => 6.0,
                    'unit' => 'mmol/L',
                    'date' => '2026-03-25T10:30:00Z',
                ],
            ], $encryptionKey),
        ])
        ->assertOk()
        ->assertJson([
            'health_entries_created' => 0,
            'health_entries_updated' => 1,
        ]);

    expect(HealthEntry::query()->where('user_id', $user->id)->count())->toBe(1)
        ->and(HealthEntry::query()->where('user_id', $user->id)->first()->glucose_value)->toBe(6.0);
});

it('updates mobile sync device last_synced_at', function (): void {
    Date::setTestNow('2026-03-25 12:00:00');

    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
        'last_synced_at' => null,
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    /** @var string $encryptionKey */
    $encryptionKey = $device->encryption_key;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/sync/health-entries', [
            'device_identifier' => 'test-uuid',
            'encrypted_payload' => encryptSyncPayload([
                [
                    'type' => 'heartRate',
                    'value' => 72,
                    'unit' => 'bpm',
                    'date' => '2026-03-25T10:30:00Z',
                ],
            ], $encryptionKey),
        ])
        ->assertOk();

    expect($device->fresh()->last_synced_at->toDateTimeString())->toBe('2026-03-25 12:00:00');

    Date::setTestNow();
});

it('handles mixed health entries and sync samples', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    /** @var string $encryptionKey */
    $encryptionKey = $device->encryption_key;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/sync/health-entries', [
            'device_identifier' => 'test-uuid',
            'encrypted_payload' => encryptSyncPayload([
                [
                    'type' => HealthSyncType::BloodGlucose->value,
                    'value' => 5.5,
                    'unit' => 'mmol/L',
                    'date' => '2026-03-25T10:30:00Z',
                ],
                [
                    'type' => 'heartRate',
                    'value' => 72,
                    'unit' => 'bpm',
                    'date' => '2026-03-25T10:30:00Z',
                ],
                [
                    'type' => HealthSyncType::Weight->value,
                    'value' => 75.5,
                    'unit' => 'kg',
                    'date' => '2026-03-25T10:30:00Z',
                ],
                [
                    'type' => 'stepCount',
                    'value' => 5000,
                    'unit' => 'count',
                    'date' => '2026-03-25T10:30:00Z',
                ],
            ], $encryptionKey),
        ])
        ->assertOk()
        ->assertJson([
            'health_entries_created' => 2,
            'health_entries_updated' => 0,
            'samples_created' => 2,
            'samples_updated' => 0,
        ]);
});
