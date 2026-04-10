<?php

declare(strict_types=1);

use App\Enums\BloodType;
use App\Enums\HealthSyncType;
use App\Enums\Sex;
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
    $this->postJson(route('api.v1.sync.health-entries'), [
        'device_identifier' => 'test-uuid',
        'encrypted_payload' => 'some-payload',
    ])->assertUnauthorized();
});

it('validates device_identifier is required', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v1.sync.health-entries'), [
            'encrypted_payload' => 'some-payload',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['device_identifier']);
});

it('validates encrypted_payload is required', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v1.sync.health-entries'), [
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
        ->postJson(route('api.v1.sync.health-entries'), [
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
        ->postJson(route('api.v1.sync.health-entries'), [
            'device_identifier' => 'test-uuid',
            'encrypted_payload' => encryptSyncPayload(
                [['value' => 5.5]],
                $encryptionKey,
            ),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['entries.0.type', 'entries.0.date']);
});

it('syncs blood glucose to health sync samples with random reading type', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    /** @var string $encryptionKey */
    $encryptionKey = $device->encryption_key;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v1.sync.health-entries'), [
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
            'samples_created' => 1,
            'samples_updated' => 0,
        ]);

    $sample = HealthSyncSample::query()->where('user_id', $user->id)->first();

    expect($sample)
        ->type_identifier->toBe('bloodGlucose')
        ->value->toBe(round(5.5 * 18.0182, 4))
        ->unit->toBe('mg/dL')
        ->original_unit->toBe('mmol/L')
        ->entry_source->value->toBe('mobile_sync')
        ->metadata->toBe(['glucose_reading_type' => 'random']);
});

it('syncs blood pressure to two health sync sample rows with shared group_id', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    /** @var string $encryptionKey */
    $encryptionKey = $device->encryption_key;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v1.sync.health-entries'), [
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
            'samples_created' => 2,
            'samples_updated' => 0,
        ]);

    expect(HealthSyncSample::query()->where('user_id', $user->id)->count())->toBe(2);

    $systolic = HealthSyncSample::query()->where('user_id', $user->id)
        ->where('type_identifier', HealthSyncType::BloodPressureSystolic->value)
        ->first();

    $diastolic = HealthSyncSample::query()->where('user_id', $user->id)
        ->where('type_identifier', HealthSyncType::BloodPressureDiastolic->value)
        ->first();

    expect($systolic->value)->toBe(120.0)
        ->and($diastolic->value)->toBe(80.0)
        ->and($systolic->entry_source->value)->toBe('mobile_sync')
        ->and($systolic->group_id)->not->toBeNull()
        ->and($systolic->group_id)->toBe($diastolic->group_id);
});

it('syncs weight to health sync samples', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    /** @var string $encryptionKey */
    $encryptionKey = $device->encryption_key;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v1.sync.health-entries'), [
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
        ->assertJson(['samples_created' => 1]);

    $sample = HealthSyncSample::query()->where('user_id', $user->id)
        ->where('type_identifier', HealthSyncType::Weight->value)
        ->first();

    expect($sample->value)->toBe(75.5);
});

it('syncs macros to health sync samples', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    /** @var string $encryptionKey */
    $encryptionKey = $device->encryption_key;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v1.sync.health-entries'), [
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
        ->assertOk()
        ->assertJson(['samples_created' => 4]);

    $carbs = HealthSyncSample::query()->where('user_id', $user->id)
        ->where('type_identifier', HealthSyncType::Carbohydrates->value)
        ->first();

    $protein = HealthSyncSample::query()->where('user_id', $user->id)
        ->where('type_identifier', HealthSyncType::Protein->value)
        ->first();

    $fat = HealthSyncSample::query()->where('user_id', $user->id)
        ->where('type_identifier', HealthSyncType::TotalFat->value)
        ->first();

    $calories = HealthSyncSample::query()->where('user_id', $user->id)
        ->where('type_identifier', HealthSyncType::DietaryEnergy->value)
        ->first();

    expect($carbs->value)->toBe(50.0)
        ->and($protein->value)->toBe(25.0)
        ->and($fat->value)->toBe(15.0)
        ->and($calories->value)->toBe(450.0);
});

it('syncs exercise minutes to health sync samples', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    /** @var string $encryptionKey */
    $encryptionKey = $device->encryption_key;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v1.sync.health-entries'), [
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
        ->assertOk()
        ->assertJson(['samples_created' => 2]);

    $exercise = HealthSyncSample::query()->where('user_id', $user->id)
        ->where('type_identifier', HealthSyncType::ExerciseMinutes->value)
        ->first();

    $workout = HealthSyncSample::query()->where('user_id', $user->id)
        ->where('type_identifier', HealthSyncType::Workouts->value)
        ->first();

    expect($exercise->value)->toBe(30.0)
        ->and($workout->value)->toBe(45.0);
});

it('syncs all types to health sync samples table', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    /** @var string $encryptionKey */
    $encryptionKey = $device->encryption_key;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v1.sync.health-entries'), [
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
        ->postJson(route('api.v1.sync.health-entries'), [
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
        ->postJson(route('api.v1.sync.health-entries'), [
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
        ->assertJson(['samples_created' => 1]);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v1.sync.health-entries'), [
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
            'samples_created' => 0,
            'samples_updated' => 1,
        ]);

    expect(HealthSyncSample::query()->where('user_id', $user->id)->count())->toBe(1)
        ->and(HealthSyncSample::query()->where('user_id', $user->id)->first()->value)->toBe(round(6.0 * 18.0182, 4));
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
        ->postJson(route('api.v1.sync.health-entries'), [
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

it('rejects device with missing encryption key', function (): void {
    $user = User::factory()->create();
    MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
        'encryption_key' => null,
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v1.sync.health-entries'), [
            'device_identifier' => 'test-uuid',
            'encrypted_payload' => 'some-payload',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['encrypted_payload']);
});

it('rejects invalid encrypted payload', function (): void {
    $user = User::factory()->create();
    MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v1.sync.health-entries'), [
            'device_identifier' => 'test-uuid',
            'encrypted_payload' => base64_encode('short'),
        ])
        ->assertUnprocessable();
});

it('rejects corrupted device encryption key', function (): void {
    $user = User::factory()->create();
    MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
        'encryption_key' => base64_encode('short-key'),
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    $validPayload = base64_encode(str_repeat('x', 30));

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v1.sync.health-entries'), [
            'device_identifier' => 'test-uuid',
            'encrypted_payload' => $validPayload,
        ])
        ->assertInternalServerError();
});

it('rejects payload that fails decryption', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    $wrongPayload = base64_encode(str_repeat('x', 30));

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v1.sync.health-entries'), [
            'device_identifier' => 'test-uuid',
            'encrypted_payload' => $wrongPayload,
        ])
        ->assertUnprocessable();
});

it('rejects payload with invalid json structure', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    /** @var string $encryptionKey */
    $encryptionKey = $device->encryption_key;

    $key = base64_decode($encryptionKey);
    $nonce = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt('not-json', 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag);

    $payload = base64_encode($nonce.$ciphertext.$tag);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v1.sync.health-entries'), [
            'device_identifier' => 'test-uuid',
            'encrypted_payload' => $payload,
        ])
        ->assertUnprocessable();
});

it('updates existing samples on duplicate sync', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    /** @var string $encryptionKey */
    $encryptionKey = $device->encryption_key;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v1.sync.health-entries'), [
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
        ->assertJson(['samples_created' => 1]);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v1.sync.health-entries'), [
            'device_identifier' => 'test-uuid',
            'encrypted_payload' => encryptSyncPayload([
                [
                    'type' => HealthSyncType::Weight->value,
                    'value' => 76.0,
                    'unit' => 'kg',
                    'date' => '2026-03-25T10:30:00Z',
                ],
            ], $encryptionKey),
        ])
        ->assertOk()
        ->assertJson([
            'samples_created' => 0,
            'samples_updated' => 1,
        ]);

    expect(HealthSyncSample::query()->where('user_id', $user->id)->count())->toBe(1)
        ->and(HealthSyncSample::query()->where('user_id', $user->id)->first()->value)->toBe(76.0);
});

it('updates existing unmapped samples on duplicate sync', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    /** @var string $encryptionKey */
    $encryptionKey = $device->encryption_key;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v1.sync.health-entries'), [
            'device_identifier' => 'test-uuid',
            'encrypted_payload' => encryptSyncPayload([
                [
                    'type' => 'heartRate',
                    'value' => 72,
                    'unit' => 'bpm',
                    'date' => '2026-03-25T10:30:00Z',
                    'source' => 'Apple Watch',
                ],
            ], $encryptionKey),
        ])
        ->assertOk()
        ->assertJson(['samples_created' => 1]);

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v1.sync.health-entries'), [
            'device_identifier' => 'test-uuid',
            'encrypted_payload' => encryptSyncPayload([
                [
                    'type' => 'heartRate',
                    'value' => 80,
                    'unit' => 'bpm',
                    'date' => '2026-03-25T10:30:00Z',
                    'source' => 'iPhone',
                ],
            ], $encryptionKey),
        ])
        ->assertOk()
        ->assertJson([
            'samples_created' => 0,
            'samples_updated' => 1,
        ]);

    expect(HealthSyncSample::query()->where('user_id', $user->id)->count())->toBe(1);

    $sample = HealthSyncSample::query()->where('user_id', $user->id)->first();

    expect($sample->value)->toBe(80.0)
        ->and($sample->source)->toBe('iPhone');
});

it('syncs biologicalSex to user profile', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    /** @var string $encryptionKey */
    $encryptionKey = $device->encryption_key;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v1.sync.health-entries'), [
            'device_identifier' => 'test-uuid',
            'encrypted_payload' => encryptSyncPayload([
                [
                    'type' => 'biologicalSex',
                    'value' => 2,
                    'unit' => 'enum',
                    'date' => '2026-04-04T00:00:00Z',
                ],
            ], $encryptionKey),
        ])
        ->assertOk()
        ->assertJson([
            'profile_updated' => true,
            'samples_created' => 0,
        ]);

    expect($user->profile->fresh()->sex)->toBe(Sex::Male);
});

it('syncs dateOfBirth to user profile', function (): void {
    Date::setTestNow('2026-04-04 12:00:00');

    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    /** @var string $encryptionKey */
    $encryptionKey = $device->encryption_key;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v1.sync.health-entries'), [
            'device_identifier' => 'test-uuid',
            'encrypted_payload' => encryptSyncPayload([
                [
                    'type' => 'dateOfBirth',
                    'value' => 19850315.0,
                    'unit' => 'yyyyMMdd',
                    'date' => '2026-04-04T00:00:00Z',
                ],
            ], $encryptionKey),
        ])
        ->assertOk()
        ->assertJson(['profile_updated' => true]);

    $profile = $user->profile->fresh();

    expect($profile->date_of_birth->format('Y-m-d'))->toBe('1985-03-15')
        ->and($profile->age)->toBe(41);

    Date::setTestNow();
});

it('syncs bloodType to user profile', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    /** @var string $encryptionKey */
    $encryptionKey = $device->encryption_key;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v1.sync.health-entries'), [
            'device_identifier' => 'test-uuid',
            'encrypted_payload' => encryptSyncPayload([
                [
                    'type' => 'bloodType',
                    'value' => 7,
                    'unit' => 'enum',
                    'date' => '2026-04-04T00:00:00Z',
                ],
            ], $encryptionKey),
        ])
        ->assertOk()
        ->assertJson(['profile_updated' => true]);

    expect($user->profile->fresh()->blood_type)->toBe(BloodType::OPositive);
});

it('does not leak user characteristics to samples', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    /** @var string $encryptionKey */
    $encryptionKey = $device->encryption_key;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v1.sync.health-entries'), [
            'device_identifier' => 'test-uuid',
            'encrypted_payload' => encryptSyncPayload([
                ['type' => 'biologicalSex', 'value' => 1, 'unit' => 'enum', 'date' => '2026-04-04T00:00:00Z'],
                ['type' => 'dateOfBirth', 'value' => 19900101.0, 'unit' => 'yyyyMMdd', 'date' => '2026-04-04T00:00:00Z'],
                ['type' => 'bloodType', 'value' => 3, 'unit' => 'enum', 'date' => '2026-04-04T00:00:00Z'],
            ], $encryptionKey),
        ])
        ->assertOk()
        ->assertJson([
            'samples_created' => 0,
            'samples_updated' => 0,
            'profile_updated' => true,
        ]);

    expect(HealthSyncSample::query()->where('user_id', $user->id)->count())->toBe(0);
});

it('syncs biologicalSex other to user profile', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    /** @var string $encryptionKey */
    $encryptionKey = $device->encryption_key;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v1.sync.health-entries'), [
            'device_identifier' => 'test-uuid',
            'encrypted_payload' => encryptSyncPayload([
                ['type' => 'biologicalSex', 'value' => 3, 'unit' => 'enum', 'date' => '2026-04-04T00:00:00Z'],
            ], $encryptionKey),
        ])
        ->assertOk()
        ->assertJson(['profile_updated' => true]);

    expect($user->profile->fresh()->sex)->toBe(Sex::Other);
});

it('syncs all bloodType values to user profile', function (int $value, BloodType $expected): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    /** @var string $encryptionKey */
    $encryptionKey = $device->encryption_key;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v1.sync.health-entries'), [
            'device_identifier' => 'test-uuid',
            'encrypted_payload' => encryptSyncPayload([
                ['type' => 'bloodType', 'value' => $value, 'unit' => 'enum', 'date' => '2026-04-04T00:00:00Z'],
            ], $encryptionKey),
        ])
        ->assertOk()
        ->assertJson(['profile_updated' => true]);

    expect($user->profile->fresh()->blood_type)->toBe($expected);
})->with([
    'A+' => [1, BloodType::APositive],
    'A-' => [2, BloodType::ANegative],
    'B-' => [4, BloodType::BNegative],
    'AB+' => [5, BloodType::ABPositive],
    'AB-' => [6, BloodType::ABNegative],
    'O-' => [8, BloodType::ONegative],
]);

it('ignores dateOfBirth with invalid length', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    /** @var string $encryptionKey */
    $encryptionKey = $device->encryption_key;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v1.sync.health-entries'), [
            'device_identifier' => 'test-uuid',
            'encrypted_payload' => encryptSyncPayload([
                ['type' => 'dateOfBirth', 'value' => 123.0, 'unit' => 'yyyyMMdd', 'date' => '2026-04-04T00:00:00Z'],
            ], $encryptionKey),
        ])
        ->assertOk()
        ->assertJson(['profile_updated' => false]);
});

it('ignores invalid HealthKit characteristic values', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    /** @var string $encryptionKey */
    $encryptionKey = $device->encryption_key;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v1.sync.health-entries'), [
            'device_identifier' => 'test-uuid',
            'encrypted_payload' => encryptSyncPayload([
                [
                    'type' => 'biologicalSex',
                    'value' => 99,
                    'unit' => 'enum',
                    'date' => '2026-04-04T00:00:00Z',
                ],
            ], $encryptionKey),
        ])
        ->assertOk()
        ->assertJson(['profile_updated' => false]);
});

it('handles mixed entry types all going to samples', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    /** @var string $encryptionKey */
    $encryptionKey = $device->encryption_key;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v1.sync.health-entries'), [
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
            'samples_created' => 4,
            'samples_updated' => 0,
        ]);
});

it('stores timezone on health sync samples and updates user timezone', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    /** @var string $encryptionKey */
    $encryptionKey = $device->encryption_key;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v1.sync.health-entries'), [
            'device_identifier' => 'test-uuid',
            'timezone' => 'Asia/Ulaanbaatar',
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

    $sample = HealthSyncSample::query()->where('user_id', $user->id)->first();

    expect($sample->timezone)->toBe('Asia/Ulaanbaatar');
    expect($user->fresh()->timezone)->toBe('Asia/Ulaanbaatar');
});

it('syncs without timezone for backward compatibility', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    /** @var string $encryptionKey */
    $encryptionKey = $device->encryption_key;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v1.sync.health-entries'), [
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

    $sample = HealthSyncSample::query()->where('user_id', $user->id)->first();

    expect($sample->timezone)->toBeNull();
});

it('syncs medication dose event with camelCase metadata mapped to snake_case', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    /** @var string $encryptionKey */
    $encryptionKey = $device->encryption_key;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v1.sync.health-entries'), [
            'device_identifier' => 'test-uuid',
            'encrypted_payload' => encryptSyncPayload([
                [
                    'type' => HealthSyncType::MedicationDoseEvent->value,
                    'value' => 1.0,
                    'unit' => 'tablet',
                    'date' => '2026-04-06T09:00:00Z',
                    'source' => 'iPhone Health',
                    'metadata' => [
                        'medicationName' => 'Aspirin',
                        'logStatus' => 'taken',
                    ],
                ],
            ], $encryptionKey),
        ])
        ->assertOk()
        ->assertJson(['samples_created' => 1]);

    $sample = HealthSyncSample::query()->where('user_id', $user->id)->first();

    expect($sample)
        ->type_identifier->toBe('medicationDoseEvent')
        ->value->toBe(1.0)
        ->metadata->toHaveKey('medication_name', 'Aspirin')
        ->metadata->toHaveKey('log_status', 'taken')
        ->metadata->not->toHaveKey('medicationName')
        ->metadata->not->toHaveKey('logStatus');
});

it('syncs a medication library entry with camelCase metadata normalized to snake_case', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    /** @var string $encryptionKey */
    $encryptionKey = $device->encryption_key;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v1.sync.health-entries'), [
            'device_identifier' => 'test-uuid',
            'encrypted_payload' => encryptSyncPayload([
                [
                    'type' => HealthSyncType::Medication->value,
                    'value' => 1.0,
                    'unit' => 'pill',
                    'date' => '2026-04-07T15:30:45Z',
                    'source' => 'Apple Health',
                    'metadata' => [
                        'name' => 'Metformin 500mg',
                        'displayText' => 'Metformin Hydrochloride 500 mg',
                        'form' => 'pill',
                        'hasSchedule' => 'true',
                        'isArchived' => 'false',
                    ],
                ],
            ], $encryptionKey),
        ])
        ->assertOk()
        ->assertJson(['samples_created' => 1]);

    $sample = HealthSyncSample::query()->where('user_id', $user->id)->first();

    expect($sample)
        ->type_identifier->toBe('medication')
        ->value->toBe(1.0)
        ->unit->toBe('pill')
        ->metadata->toHaveKey('name', 'Metformin 500mg')
        ->metadata->toHaveKey('display_text', 'Metformin Hydrochloride 500 mg')
        ->metadata->toHaveKey('form', 'pill')
        ->metadata->toHaveKey('has_schedule', 'true')
        ->metadata->toHaveKey('is_archived', 'false')
        ->metadata->not->toHaveKey('displayText')
        ->metadata->not->toHaveKey('hasSchedule')
        ->metadata->not->toHaveKey('isArchived');
});

it('syncs multiple medications with the same timestamp without collision', function (): void {
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create([
        'device_identifier' => 'test-uuid',
    ]);
    $token = $user->createToken('test', ['sync:push'])->plainTextToken;

    /** @var string $encryptionKey */
    $encryptionKey = $device->encryption_key;

    $sharedDate = '2026-04-07T15:30:45Z';

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('api.v1.sync.health-entries'), [
            'device_identifier' => 'test-uuid',
            'encrypted_payload' => encryptSyncPayload([
                [
                    'type' => HealthSyncType::Medication->value,
                    'value' => 1.0,
                    'unit' => 'pill',
                    'date' => $sharedDate,
                    'source' => 'Apple Health',
                    'metadata' => [
                        'displayText' => 'Metformin 500 mg',
                        'name' => 'Metformin',
                        'form' => 'pill',
                    ],
                ],
                [
                    'type' => HealthSyncType::Medication->value,
                    'value' => 1.0,
                    'unit' => 'tablet',
                    'date' => $sharedDate,
                    'source' => 'Apple Health',
                    'metadata' => [
                        'displayText' => 'Lisinopril 10 mg',
                        'name' => 'Lisinopril',
                        'form' => 'tablet',
                    ],
                ],
                [
                    'type' => HealthSyncType::Medication->value,
                    'value' => 1.0,
                    'unit' => 'pill',
                    'date' => $sharedDate,
                    'source' => 'Apple Health',
                    'metadata' => [
                        'displayText' => 'Atorvastatin 20 mg',
                        'name' => 'Atorvastatin',
                        'form' => 'pill',
                    ],
                ],
            ], $encryptionKey),
        ])
        ->assertOk()
        ->assertJson(['samples_created' => 3]);

    $samples = HealthSyncSample::query()
        ->where('user_id', $user->id)
        ->where('type_identifier', HealthSyncType::Medication->value)
        ->get();

    expect($samples)->toHaveCount(3);

    $displayTexts = $samples
        ->map(fn (HealthSyncSample $s): string => (string) ($s->metadata['display_text'] ?? ''))
        ->sort()
        ->values()
        ->all();

    expect($displayTexts)->toBe(['Atorvastatin 20 mg', 'Lisinopril 10 mg', 'Metformin 500 mg']);
});
