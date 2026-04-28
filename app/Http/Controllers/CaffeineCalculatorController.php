<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\BuildCaffeineGuidanceSpec;
use App\Actions\ResolveCaffeineLimit;
use App\Ai\Agents\CaffeineGuidanceAgent;
use App\Http\Requests\CaffeineAssessmentRequest;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class CaffeineCalculatorController
{
    public function create(): Response
    {
        return Inertia::render('caffeine-calculator', [
            'seo' => [
                'appName' => config('app.name'),
                'appUrl' => url('/'),
                'canonicalUrl' => route('caffeine-calculator'),
            ],
        ]);
    }

    public function plan(CaffeineAssessmentRequest $request): JsonResponse
    {
        $context = $request->context();
        $limit = resolve(ResolveCaffeineLimit::class)->handle(
            heightCm: $request->heightCm(),
            sensitivity: $request->sensitivity(),
            context: $context,
        );
        $guidance = resolve(CaffeineGuidanceAgent::class)->assess(
            limit: $limit,
            context: $context,
        );
        $spec = resolve(BuildCaffeineGuidanceSpec::class)->handle($guidance);

        return response()->json([
            'summary' => $guidance->summary,
            'limit' => $limit->toArray(),
            'spec' => $spec,
        ]);
    }
}
