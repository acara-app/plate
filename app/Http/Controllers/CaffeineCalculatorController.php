<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\BuildCaffeineGuidanceSpec;
use App\Actions\ResolveCaffeineLimit;
use App\Ai\Agents\CaffeineGuidanceAgent;
use App\Http\Requests\CaffeineAssessmentRequest;
use App\Utilities\LanguageUtil;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class CaffeineCalculatorController
{
    public function create(Request $request): Response
    {
        $locale = $request->route('locale', 'en');
        app()->setLocale($locale);

        return Inertia::render('caffeine-calculator', [
            'locale' => $locale,
            'translations' => LanguageUtil::translations($locale),
            'seo' => [
                'appName' => config('app.name'),
                'appUrl' => url('/'),
                'canonicalUrl' => $locale === 'en'
                    ? route('caffeine-calculator')
                    : route('caffeine-calculator.locale', ['locale' => $locale]),
            ],
        ]);
    }

    public function plan(CaffeineAssessmentRequest $request): JsonResponse
    {
        $context = $request->context();
        $locale = $request->locale();
        app()->setLocale($locale);

        $limit = resolve(ResolveCaffeineLimit::class)->handle(
            heightCm: $request->heightCm(),
            weightKg: $request->weightKg(),
            age: $request->age(),
            sex: $request->sex(),
            sensitivity: $request->sensitivity(),
            context: $context,
            conditions: $request->conditions(),
        );
        $guidance = resolve(CaffeineGuidanceAgent::class)->assess(
            limit: $limit,
            context: $context,
            locale: $locale,
        );
        $spec = resolve(BuildCaffeineGuidanceSpec::class)->handle($guidance);

        return response()->json([
            'summary' => $guidance->summary,
            'limit' => $limit->toArray(),
            'spec' => $spec,
        ]);
    }
}
