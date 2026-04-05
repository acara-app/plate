<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\DecryptSyncPayloadAction;
use App\Actions\SyncMobileHealthEntriesAction;
use App\Actions\UpdateUserTimezoneAction;
use App\Http\Requests\Api\V1\StoreMobileSyncHealthEntriesRequest;
use App\Models\MobileSyncDevice;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

final readonly class MobileSyncHealthEntriesController
{
    public function __construct(
        private DecryptSyncPayloadAction $decryptPayloadAction,
        private SyncMobileHealthEntriesAction $syncHealthEntriesAction,
        private UpdateUserTimezoneAction $updateTimezoneAction,
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

        $entries = $this->decryptPayloadAction->handle($encryptedPayload, $device->encryption_key);

        $result = $this->syncHealthEntriesAction->handle(
            user: $user,
            device: $device,
            entries: $entries,
            timezone: $timezone,
        );

        if (is_string($timezone)) {
            $this->updateTimezoneAction->handle($user, $timezone);
        }

        return response()->json([
            'message' => 'Synced successfully.',
            'samples_created' => $result['samples_created'],
            'samples_updated' => $result['samples_updated'],
            'profile_updated' => $result['profile_updated'],
        ]);
    }
}
