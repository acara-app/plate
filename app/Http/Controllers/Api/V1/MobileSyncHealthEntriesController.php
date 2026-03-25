<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;

final class MobileSyncHealthEntriesController
{
    public function __invoke(): JsonResponse
    {
        return response()->json(['message' => 'Not implemented'], 501);
    }
}
