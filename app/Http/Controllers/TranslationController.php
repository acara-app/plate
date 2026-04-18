<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Utilities\LanguageUtil;
use Illuminate\Http\JsonResponse;

final readonly class TranslationController
{
    public function __invoke(string $locale): JsonResponse
    {
        abort_unless(LanguageUtil::has($locale), 404);

        return response()->json(LanguageUtil::translations($locale));
    }
}
