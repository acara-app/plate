<?php

declare(strict_types=1);

use App\Actions\AggregateHealthDailySamplesAction;
use App\Actions\ProcessMobileSyncBatchAction;
use App\Ai\Tools\GetHealthData;
use App\Ai\Tools\GetHealthSummary;
use App\Models\HealthSyncSample;
use App\Models\MobileSyncDevice;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Ai\Tools\Request;

covers(ProcessMobileSyncBatchAction::class);

/** @param array<string, mixed> $payload */
function encryptAcknowledgementPayload(array $payload, string $key): string
{
    $nonce = random_bytes(12);
    $tag = '';
    $encrypted = openssl_encrypt(json_encode($payload), 'aes-256-gcm', base64_decode($key), OPENSSL_RAW_DATA, $nonce, $tag);

    return base64_encode($nonce.$encrypted.$tag);
}

/** @return array{user: User, device: MobileSyncDevice, token: string, payload: array<string, mixed>} */
function acknowledgementScenario(): array
{
    $user = User::factory()->create();
    $device = MobileSyncDevice::factory()->for($user)->paired()->create();

    return [
        'user' => $user,
        'device' => $device,
        'token' => $user->createToken('sync test', ['sync:push'])->plainTextToken,
        'payload' => [
            'batch_id' => (string) Str::uuid(),
            'entries' => [
                ['type' => 'biologicalSex', 'value' => 2, 'unit' => 'enum', 'date' => '2026-10-15T01:00:00Z'],
                ['type' => 'stepCount', 'value' => 1234, 'unit' => 'steps', 'date' => '2026-10-15T01:00:00Z', 'sample_uuid' => (string) Str::uuid()],
                ['type' => 'weight', 'value' => 70, 'unit' => 'stone', 'date' => '2026-10-15T01:00:00Z'],
                ['type' => 'medication', 'value' => 1, 'unit' => 'pill', 'date' => '2026-10-15T01:00:00Z'],
            ],
            'sleep_events' => [
                ['type' => 'sleepAnalysis', 'stage' => 'asleepCore', 'started_at' => '2026-10-15T00:00:00Z', 'ended_at' => '2026-10-15T01:00:00Z', 'sample_uuid' => (string) Str::uuid()],
            ],
        ],
    ];
}

it('acknowledges original rejection indexes and exposes accepted steps to both assistant tools', function (): void {
    $scenario = acknowledgementScenario();
    $response = $this->withToken($scenario['token'])->postJson(route('api.v2.sync.health-entries'), [
        'device_identifier' => $scenario['device']->device_identifier,
        'timezone' => 'Asia/Ulaanbaatar',
        'encrypted_payload' => encryptAcknowledgementPayload($scenario['payload'], $scenario['device']->encryption_key),
    ])->assertOk()->assertJson([
        'batch_id' => $scenario['payload']['batch_id'],
        'samples_created' => 2,
        'samples_dropped' => 1,
        'sleep_events_created' => 1,
        'rejected_entries' => [['index' => 2, 'code' => 'unsupported_unit']],
    ]);

    expect(HealthSyncSample::query()->where('user_id', $scenario['user']->id)->count())->toBe(2);
    expect($scenario['user']->sleepSessions()->count())->toBe(1);
    expect(DB::table('mobile_sync_receipts')->count())->toBe(1);

    app(AggregateHealthDailySamplesAction::class)->handle($scenario['user'], CarbonImmutable::parse('2026-10-15', 'UTC'));
    $this->actingAs($scenario['user']);
    $query = new Request(['type' => 'steps', 'days' => 1, 'date' => '2026-10-15']);
    $raw = json_decode(app(GetHealthData::class)->handle($query), true);
    $summary = json_decode(app(GetHealthSummary::class)->handle($query), true);
    expect($raw['records'][0]['value'])->toBe(1234);
    expect($summary['summaries'][0]['primary_value'])->toBe(1234);
});

it('returns the original receipt on replay and does not repeat accepted medication or sleep writes', function (): void {
    $scenario = acknowledgementScenario();
    $request = [
        'device_identifier' => $scenario['device']->device_identifier,
        'encrypted_payload' => encryptAcknowledgementPayload($scenario['payload'], $scenario['device']->encryption_key),
    ];
    $first = $this->withToken($scenario['token'])->postJson(route('api.v2.sync.health-entries'), $request)->assertOk()->json();
    $replay = $this->postJson(route('api.v2.sync.health-entries'), $request)->assertOk()->json();

    expect($replay)->toBe($first);
    expect(HealthSyncSample::query()->where('type_identifier', 'medication')->count())->toBe(1);
    expect($scenario['user']->sleepSessions()->count())->toBe(1);

    $retry = ['batch_id' => (string) Str::uuid(), 'entries' => [array_replace($scenario['payload']['entries'][2], ['unit' => 'kg'])]];
    $this->postJson(route('api.v2.sync.health-entries'), [
        ...$request,
        'encrypted_payload' => encryptAcknowledgementPayload($retry, $scenario['device']->encryption_key),
    ])->assertOk()->assertJson(['samples_created' => 1, 'samples_dropped' => 0, 'rejected_entries' => []]);
    expect(HealthSyncSample::query()->where('type_identifier', 'medication')->count())->toBe(1);
});

it('rejects reused IDs with changed values order or timezone without additional writes', function (string $change): void {
    $scenario = acknowledgementScenario();
    $request = ['device_identifier' => $scenario['device']->device_identifier, 'timezone' => 'UTC'];
    $this->withToken($scenario['token'])->postJson(route('api.v2.sync.health-entries'), [
        ...$request, 'encrypted_payload' => encryptAcknowledgementPayload($scenario['payload'], $scenario['device']->encryption_key),
    ])->assertOk();

    if ($change === 'value') {
        $scenario['payload']['entries'][1]['value']++;
    }
    if ($change === 'order') {
        $scenario['payload']['entries'] = array_reverse($scenario['payload']['entries']);
    }
    if ($change === 'timezone') {
        $request['timezone'] = 'Asia/Ulaanbaatar';
    }
    $this->postJson(route('api.v2.sync.health-entries'), [
        ...$request, 'encrypted_payload' => encryptAcknowledgementPayload($scenario['payload'], $scenario['device']->encryption_key),
    ])->assertConflict();
    expect(HealthSyncSample::query()->count())->toBe(2);
    expect(DB::table('mobile_sync_receipts')->count())->toBe(1);
})->with(['value', 'order', 'timezone']);

it('advertises reliable sync without requiring legacy clients to send batch IDs', function (): void {
    $this->getJson(route('api.v2.auth.capabilities'))->assertOk()->assertJson(['health_sync_protocol' => 2]);
    $scenario = acknowledgementScenario();
    unset($scenario['payload']['batch_id']);
    $this->withToken($scenario['token'])->postJson(route('api.v2.sync.health-entries'), [
        'device_identifier' => $scenario['device']->device_identifier,
        'encrypted_payload' => encryptAcknowledgementPayload($scenario['payload'], $scenario['device']->encryption_key),
    ])->assertOk()->assertJson(['samples_created' => 2]);
    expect(DB::table('mobile_sync_receipts')->count())->toBe(0);
});

it('rejects malformed batch IDs and non-list entries before writing records', function (string $problem): void {
    $scenario = acknowledgementScenario();
    if ($problem === 'id') {
        $scenario['payload']['batch_id'] = 'not-a-uuid';
    }
    if ($problem === 'entries') {
        $scenario['payload']['entries'] = ['named' => $scenario['payload']['entries'][1]];
    }
    $this->withToken($scenario['token'])->postJson(route('api.v2.sync.health-entries'), [
        'device_identifier' => $scenario['device']->device_identifier,
        'encrypted_payload' => encryptAcknowledgementPayload($scenario['payload'], $scenario['device']->encryption_key),
    ])->assertUnprocessable()->assertJsonValidationErrors($problem === 'id' ? ['batch_id'] : ['entries']);
    expect(HealthSyncSample::query()->count())->toBe(0);
    expect(DB::table('mobile_sync_receipts')->count())->toBe(0);
})->with(['id', 'entries']);

it('recognizes the same payload when encryption nonces and metadata key order change', function (): void {
    $scenario = acknowledgementScenario();
    $scenario['payload']['entries'][1]['metadata'] = ['b' => '2', 'a' => '1'];
    $request = ['device_identifier' => $scenario['device']->device_identifier];
    $first = $this->withToken($scenario['token'])->postJson(route('api.v2.sync.health-entries'), [
        ...$request, 'encrypted_payload' => encryptAcknowledgementPayload($scenario['payload'], $scenario['device']->encryption_key),
    ])->assertOk()->json();
    $scenario['payload']['entries'][1]['metadata'] = ['a' => '1', 'b' => '2'];
    $replay = $this->postJson(route('api.v2.sync.health-entries'), [
        ...$request, 'encrypted_payload' => encryptAcknowledgementPayload($scenario['payload'], $scenario['device']->encryption_key),
    ])->assertOk()->json();
    expect($replay)->toBe($first);
    expect(HealthSyncSample::query()->where('type_identifier', 'medication')->count())->toBe(1);
    expect(DB::table('mobile_sync_receipts')->count())->toBe(1);
});
