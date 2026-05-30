<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Auth;

use App\Data\MobileCapabilitiesData;
use Illuminate\Http\JsonResponse;

final class CapabilitiesController
{
    public function __invoke(): JsonResponse
    {
        return response()->json(MobileCapabilitiesData::fromConfig()->toArray());
    }
}
