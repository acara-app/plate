<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Auth;

use Illuminate\Http\JsonResponse;

final class CapabilitiesController
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'methods' => array_keys(array_filter(config()->array('mobile.auth_methods'))),
            'chat_first' => config()->boolean('mobile.chat_first_enabled'),
            'min_app_version' => config()->string('mobile.min_app_version'),
        ]);
    }
}
