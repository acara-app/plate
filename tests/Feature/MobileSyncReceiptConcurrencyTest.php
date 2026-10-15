<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\ProcessMobileSyncBatchAction;
use App\Data\MobileSync\DecryptedSyncPayloadData;
use App\Models\HealthSyncSample;
use App\Models\MobileSyncDevice;
use App\Models\User;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MobileSyncReceiptConcurrencyTest extends TestCase
{
    #[Test]
    public function concurrent_postgres_uploads_share_one_receipt_and_one_set_of_records(): void
    {
        if (DB::getDriverName() !== 'pgsql' || ! str_starts_with(DB::getDatabaseName(), 'acara_sync_protocol_test_')) {
            $this->markTestSkipped('Requires an isolated acara_sync_protocol_test_* PostgreSQL database.');
        }

        $this->artisan('migrate:fresh', ['--force' => true, '--no-interaction' => true])->assertSuccessful();
        $user = User::factory()->create();
        $device = MobileSyncDevice::factory()->for($user)->paired()->create();
        $userId = $user->id;
        $deviceId = $device->id;
        $data = [
            'batch_id' => (string) Str::uuid(),
            'entries' => [
                ['type' => 'stepCount', 'value' => 1234, 'unit' => 'steps', 'date' => '2026-10-15T01:00:00Z'],
                ['type' => 'medication', 'value' => 1, 'unit' => 'pill', 'date' => '2026-10-15T01:00:00Z'],
            ],
            'sleep_events' => [
                ['type' => 'sleepAnalysis', 'stage' => 'asleepCore', 'started_at' => '2026-10-15T00:00:00Z', 'ended_at' => '2026-10-15T01:00:00Z'],
            ],
        ];
        $submit = static function () use ($userId, $deviceId, $data): array {
            return app(ProcessMobileSyncBatchAction::class)->handle(
                User::query()->findOrFail($userId),
                MobileSyncDevice::query()->findOrFail($deviceId),
                DecryptedSyncPayloadData::from($data),
                'UTC',
            );
        };

        [$first, $second] = Concurrency::driver('process')->run([$submit, $submit]);

        $this->assertSame($first, $second);
        $this->assertSame(1, DB::table('mobile_sync_receipts')->count());
        $this->assertSame(1, HealthSyncSample::query()->where('type_identifier', 'medication')->count());
        $this->assertSame(1, $user->sleepSessions()->count());
        $this->assertSame(2, HealthSyncSample::query()->count());
    }
}
