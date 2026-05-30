<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Auth;

use App\Http\Requests\Api\V2\Auth\NonceRequest;
use App\Models\MobileAuthNonce;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

final class NonceController
{
    public function __invoke(NonceRequest $request): JsonResponse
    {
        $deviceIdentifier = $request->string('device_identifier')->toString();

        MobileAuthNonce::query()
            ->where('device_identifier', $deviceIdentifier)
            ->delete();

        $nonce = MobileAuthNonce::query()->create([
            'nonce_id' => Str::uuid()->toString(),
            'nonce' => bin2hex(random_bytes(32)),
            'device_identifier' => $deviceIdentifier,
            'expires_at' => now()->addMinutes(5),
        ]);

        return response()->json([
            'nonce_id' => $nonce->nonce_id,
            'nonce' => $nonce->nonce,
            'expires_at' => $nonce->expires_at->toIso8601String(),
        ]);
    }
}
