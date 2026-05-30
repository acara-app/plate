<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Actions\ProvisionMobileSyncDeviceAction;
use App\Http\Requests\Api\V2\MobileSyncDeviceRegistrationRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

final readonly class MobileSyncDeviceRegistrationController
{
    public function __construct(private ProvisionMobileSyncDeviceAction $provision) {}

    public function __invoke(MobileSyncDeviceRegistrationRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $result = $this->provision->handle(
            $user,
            $request->string('device_name')->toString(),
            $request->string('device_identifier')->toString(),
        );

        return response()->json($result);
    }
}
