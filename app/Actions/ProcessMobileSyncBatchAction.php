<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\MobileSync\DecryptedSyncPayloadData;
use App\Models\MobileSyncDevice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use stdClass;
use UnexpectedValueException;

final readonly class ProcessMobileSyncBatchAction
{
    public function __construct(
        private SyncMobileHealthEntriesAction $syncSamples,
        private SyncSleepEventsAction $syncSleep,
        private UpdateUserTimezoneAction $updateTimezone,
    ) {}

    /** @return array{response: array<string, mixed>, affected_utc_dates: list<string>} */
    public function handle(User $user, MobileSyncDevice $device, DecryptedSyncPayloadData $payload, ?string $timezone): array
    {
        return DB::transaction(function () use ($user, $device, $payload, $timezone): array {
            $lockedDevice = MobileSyncDevice::query()
                ->whereKey($device->id)
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();

            abort_if($lockedDevice->encryption_key !== $device->encryption_key, 409, 'Device credentials changed. Please reconnect.');

            $batchId = $payload->batch_id !== null ? mb_strtolower($payload->batch_id) : null;
            $hash = hash('sha256', json_encode($this->canonicalize([
                'entries' => array_map(fn ($entry): array => $entry->toArray(), $payload->entries),
                'sleep_events' => array_map(fn ($event): array => $event->toArray(), $payload->sleep_events),
                'timezone' => $timezone,
            ]), JSON_THROW_ON_ERROR));

            if ($batchId !== null) {
                $receipt = DB::table('mobile_sync_receipts')
                    ->where('user_id', $user->id)
                    ->where('device_identifier', $lockedDevice->device_identifier)
                    ->where('batch_id', $batchId)
                    ->first();

                if ($receipt !== null) {
                    abort_unless(is_string($receipt->payload_hash) && hash_equals($receipt->payload_hash, $hash), 409, 'Batch ID was already used for different health data.');

                    return $this->readReceipt($receipt);
                }
            }

            $samples = $this->syncSamples->handle($user, $lockedDevice, $payload->entries, $timezone);
            $sleep = $this->syncSleep->handle($user, $payload->sleep_events, $timezone);

            if ($timezone !== null) {
                $this->updateTimezone->handle($user, $timezone);
            }

            $dates = array_values(array_unique([...$samples['affected_utc_dates'], ...$sleep['affected_utc_dates']]));
            $response = [
                'message' => $samples['samples_dropped'] > 0 ? 'Synced with rejected entries.' : 'Synced successfully.',
                'samples_created' => $samples['samples_created'],
                'samples_updated' => $samples['samples_updated'],
                'samples_dropped' => $samples['samples_dropped'],
                'rejected_entries' => $samples['rejected_entries'],
                'sleep_events_created' => $sleep['created'],
                'sleep_events_updated' => $sleep['updated'],
                'aggregate_jobs_dispatched' => count($dates),
                'profile_updated' => $samples['profile_updated'],
                ...($batchId !== null ? ['batch_id' => $batchId] : []),
            ];

            if ($batchId !== null) {
                DB::table('mobile_sync_receipts')->insert([
                    'user_id' => $user->id,
                    'device_identifier' => $lockedDevice->device_identifier,
                    'batch_id' => $batchId,
                    'payload_hash' => $hash,
                    'response' => json_encode($response, JSON_THROW_ON_ERROR),
                    'affected_utc_dates' => json_encode($dates, JSON_THROW_ON_ERROR),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return ['response' => $response, 'affected_utc_dates' => $dates];
        });
    }

    /** @return array{response: array<string, mixed>, affected_utc_dates: list<string>} */
    private function readReceipt(stdClass $receipt): array
    {
        if (! is_string($receipt->response) || ! is_string($receipt->affected_utc_dates)) {
            throw new UnexpectedValueException('Invalid stored sync receipt.');
        }

        $decoded = json_decode($receipt->response, true, 512, JSON_THROW_ON_ERROR);
        $dates = json_decode($receipt->affected_utc_dates, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded) || ! is_array($dates) || ! array_is_list($dates)) {
            throw new UnexpectedValueException('Invalid stored sync receipt.');
        }
        $response = [];
        foreach ($decoded as $key => $value) {
            if (! is_string($key)) {
                throw new UnexpectedValueException('Invalid stored sync response.');
            }
            $response[$key] = $value;
        }
        $validDates = array_values(array_filter($dates, is_string(...)));
        if (count($validDates) !== count($dates)) {
            throw new UnexpectedValueException('Invalid stored sync dates.');
        }

        return ['response' => $response, 'affected_utc_dates' => $validDates];
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    private function canonicalize(array $data): array
    {
        if (! array_is_list($data)) {
            ksort($data);
        }

        return array_map(fn (mixed $value): mixed => is_array($value) ? $this->canonicalize($value) : $value, $data);
    }
}
