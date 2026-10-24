<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Features;

final readonly class PasskeyEndpointsController
{
    public function __invoke(): JsonResponse
    {
        abort_unless(Features::canManagePasskeys(), 404);

        return response()->json([
            'enroll' => route('passkeys.edit'),
            'manage' => route('passkeys.edit'),
        ]);
    }
}
