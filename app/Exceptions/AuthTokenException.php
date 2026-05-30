<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

final class AuthTokenException extends RuntimeException implements ShouldntReport
{
    public function render(Request $request): JsonResponse
    {
        return response()->json(['message' => __('Invalid token.')], 401);
    }
}
