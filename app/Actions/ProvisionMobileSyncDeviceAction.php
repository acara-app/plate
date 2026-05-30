<?php

declare(strict_types=1);

namespace App\Actions;

use App\Actions\Auth\IssueMobileAuthToken;
use App\Actions\Auth\RevokeDeviceTokens;
use App\Models\MobileSyncDevice;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class ProvisionMobileSyncDeviceAction
{
    public function __construct(
        private IssueMobileAuthToken $issueMobileAuthToken,
        private RevokeDeviceTokens $revokeDeviceTokens,
    ) {}

    /**
     * @return array{api_token: string, encryption_key: string}
     */
    public function handle(User $user, string $deviceName, string $deviceIdentifier): array
    {
        return DB::transaction(function () use ($user, $deviceName, $deviceIdentifier): array {
            $device = MobileSyncDevice::query()
                ->where('device_identifier', $deviceIdentifier)
                ->first();

            if ($device instanceof MobileSyncDevice && $device->user_id !== $user->id) {
                $this->revokeDeviceTokens->handle($device->user, $device->device_identifier, $device->id);
                $device->update(['is_active' => false, 'device_identifier' => null]);
                $device = null;
            }

            if (! $device instanceof MobileSyncDevice) {
                $device = new MobileSyncDevice;
                $device->user_id = $user->id;
            }

            $encryptionKey = $device->encryption_key ?? base64_encode(random_bytes(32));

            $device->fill([
                'device_name' => $deviceName,
                'device_identifier' => $deviceIdentifier,
                'encryption_key' => $encryptionKey,
                'is_active' => true,
                'paired_at' => $device->paired_at ?? now(),
            ]);
            $device->save();

            $this->revokeDeviceTokens->handle($user, $deviceIdentifier);
            $apiToken = $this->issueMobileAuthToken->handle($user, $deviceIdentifier, ['chat:converse', 'sync:push']);

            return [
                'api_token' => $apiToken->plainTextToken,
                'encryption_key' => $encryptionKey,
            ];
        });
    }
}
