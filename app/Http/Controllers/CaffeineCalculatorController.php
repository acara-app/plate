<?php

declare(strict_types=1);

namespace App\Http\Controllers;

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
                'appName' => config()->string('app.name'),
                'appUrl' => url('/'),
                'canonicalUrl' => $this->getCanonicalUrl($locale),
                'toolsUrl' => route('tools.index'),
                'imageUrl' => 'https://pub-plate-assets.acara.app/images/coffee-cup-pale-neutral.png',
                'hreflangLinks' => $this->getHreflangLinks('caffeine-calculator', 'caffeine-calculator.locale'),
                'xDefaultUrl' => $this->getXDefaultUrl('caffeine-calculator'),
            ],
        ]);
    }

    public function plan(): JsonResponse
    {
        // Keep the route for stale clients while personalized guidance is under review.
        return response()->json(['error' => 'guidance_unavailable'], 503);
    }

    private function getXDefaultUrl(string $enRoute): string
    {
        return route($enRoute);
    }

    /**
     * @return array<int, array{locale: string, url: string}>
     */
    private function getHreflangLinks(string $enRoute, string $localeRoute): array
    {
        $links = [];

        foreach (LanguageUtil::keys() as $hrefLocale) {
            $links[] = [
                'locale' => $hrefLocale,
                'url' => $hrefLocale === 'en'
                    ? route($enRoute)
                    : route($localeRoute, ['locale' => $hrefLocale]),
            ];
        }

        return $links;
    }

    private function getCanonicalUrl(string $locale): string
    {
        if ($locale === 'en') {
            return route('caffeine-calculator');
        }

        return route('caffeine-calculator.locale', ['locale' => $locale]);
    }
}
