<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Actions\DecryptSyncPayloadAction;
use App\Actions\DispatchAggregateUserUtcDatesAction;
use App\Actions\ProcessMobileSyncBatchAction;
use App\Http\Requests\Api\V2\StoreMobileSyncHealthEntriesRequest;
use App\Models\MobileSyncDevice;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/** @codeCoverageIgnore */
final readonly class MobileSyncHealthEntriesController
{
    public function __construct(
        private DecryptSyncPayloadAction $decryptPayloadAction,
        private ProcessMobileSyncBatchAction $processBatchAction,
        private DispatchAggregateUserUtcDatesAction $dispatchAggregateDatesAction,
    ) {}

    public function __invoke(StoreMobileSyncHealthEntriesRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var string $deviceIdentifier */
        $deviceIdentifier = $request->validated('device_identifier');

        /** @var string|null $timezone */
        $timezone = $request->validated('timezone');

        $device = MobileSyncDevice::query()
            ->where('user_id', $user->id)
            ->where('device_identifier', $deviceIdentifier)
            ->where('is_active', true)
            ->firstOrFail();

        if ($device->encryption_key === null) {
            throw ValidationException::withMessages([
                'encrypted_payload' => ['Device encryption key is missing. Please re-pair the device.'],
            ]);
        }

        /** @var string $encryptedPayload */
        $encryptedPayload = $request->validated('encrypted_payload');

        $payload = $this->decryptPayloadAction->handle($encryptedPayload, $device->encryption_key);

        $result = $this->processBatchAction->handle($user, $device, $payload, $timezone);

        $this->dispatchAggregateDatesAction->handle($user, $result['affected_utc_dates']);

        return response()->json($result['response']);
    }
}
