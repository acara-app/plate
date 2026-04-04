<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\SyncMobileHealthEntriesAction;
use App\Actions\UpdateUserTimezoneAction;
use App\Http\Requests\Api\V1\StoreMobileSyncHealthEntriesRequest;
use App\Models\MobileSyncDevice;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final readonly class MobileSyncHealthEntriesController
{
    public function __construct(
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

        $entries = $this->decryptPayload($encryptedPayload, $device->encryption_key);

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
            'health_entries_created' => $result['health_entries_created'],
            'health_entries_updated' => $result['health_entries_updated'],
            'samples_created' => $result['samples_created'],
            'samples_updated' => $result['samples_updated'],
            'profile_updated' => $result['profile_updated'],
        ]);
    }

    /**
     * @return array<int, array{type: string, value: float|int|string, unit: string, date: string, source?: string|null}>
     */
    private function decryptPayload(string $base64Payload, string $base64Key): array
    {
        $payload = base64_decode($base64Payload, true);

        abort_if($payload === false || mb_strlen($payload, '8bit') < 28, 422, 'Invalid encrypted payload.');

        $key = base64_decode($base64Key, true);

        abort_if($key === false || mb_strlen($key, '8bit') !== 32, 500, 'Device encryption key is corrupted.');

        $nonce = mb_substr($payload, 0, 12, '8bit');
        $tag = mb_substr($payload, -16, null, '8bit');
        $ciphertext = mb_substr($payload, 12, -16, '8bit');

        $decrypted = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
        );

        abort_if($decrypted === false, 422, 'Failed to decrypt payload. The encryption key may be out of sync — please re-pair the device.');

        $data = json_decode($decrypted, true);

        abort_unless(is_array($data), 422, 'Decrypted payload has an invalid structure.');

        $validated = Validator::make($data, [
            'entries' => ['required', 'array', 'min:1', 'max:1000'],
            'entries.*.type' => ['required', 'string', 'max:100'],
            'entries.*.value' => ['required', 'numeric'],
            'entries.*.unit' => ['required', 'string', 'max:20'],
            'entries.*.date' => ['required', 'date'],
            'entries.*.source' => ['nullable', 'string', 'max:100'],
        ])->validate();

        /** @var array<int, array{type: string, value: float|int|string, unit: string, date: string, source?: string|null}> $entries */
        $entries = $validated['entries'];

        return $entries;
    }
}
