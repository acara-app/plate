<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Auth;

use App\Data\MobileCapabilitiesData;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MeController
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'user' => [
                'name' => $user->name,
            ],
            'consent' => [
                'terms_accepted_at' => $user->terms_accepted_at?->toIso8601String(),
                'privacy_accepted_at' => $user->privacy_accepted_at?->toIso8601String(),
                'medical_disclaimer_accepted_at' => $user->accepted_disclaimer_at?->toIso8601String(),
                'consent_version' => $user->consent_version,
                'privacy_version' => $user->privacy_version,
                'consent_required' => $user->requiresConsent(),
            ],
            'capabilities' => MobileCapabilitiesData::fromConfig()->toArray(),
        ]);
    }
}
