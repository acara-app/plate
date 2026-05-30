<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Actions\Auth\IssueMobileAuthToken;
use App\Actions\Auth\RevokeDeviceTokens;
use App\Http\Requests\Api\V2\MobileSyncPairRequest;
use App\Models\MobileSyncDevice;
use Illuminate\Http\JsonResponse;

final readonly class MobileSyncPairController
{
    public function __construct(
        private IssueMobileAuthToken $issueMobileAuthToken,
        private RevokeDeviceTokens $revokeDeviceTokens,
    ) {}

    public function __invoke(MobileSyncPairRequest $request): JsonResponse
    {
        $device = MobileSyncDevice::query()
            ->where('linking_token', mb_strtoupper($request->string('token')->toString()))
            ->where('is_active', true)
            ->first();

        if (! $device instanceof MobileSyncDevice) {
            return response()->json([
                'message' => 'Invalid pairing token. Please generate a new one in Settings → Mobile Sync on your Acara Plate dashboard.',
            ], 422);
        }

        if (! $device->isTokenValid()) {
            return response()->json([
                'message' => 'Pairing token has expired. Please generate a new one in Settings → Mobile Sync on your Acara Plate dashboard.',
            ], 422);
        }

        $deviceIdentifier = $request->string('device_identifier')->toString() ?: null;

        if ($deviceIdentifier !== null) {
            $oldDevices = MobileSyncDevice::query()
                ->where('device_identifier', $deviceIdentifier)
                ->where('id', '!=', $device->id)
                ->get();

            foreach ($oldDevices as $oldDevice) {
                $this->revokeDeviceTokens->handle($oldDevice->user, $oldDevice->device_identifier, $oldDevice->id);
                $oldDevice->update(['is_active' => false, 'device_identifier' => null]);
            }
        }

        $device->markAsPaired(
            $request->string('device_name')->toString(),
            $deviceIdentifier,
        );

        $encryptionKey = base64_encode(random_bytes(32));
        $device->update(['encryption_key' => $encryptionKey]);

        $apiToken = $deviceIdentifier !== null
            ? $this->issueMobileAuthToken->handle($device->user, $deviceIdentifier, ['sync:push', 'chat:converse'])
            : $device->user->createToken('mobile-sync:'.$device->id, ['sync:push', 'chat:converse']);

        return response()->json([
            'message' => 'Device paired successfully.',
            'api_token' => $apiToken->plainTextToken,
            'encryption_key' => $encryptionKey,
            'user' => [
                'name' => $device->user->name,
            ],
        ]);
    }
}
