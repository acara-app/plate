<?php

declare(strict_types=1);

use App\Ai\Agents\CaffeineGuidanceAgent;
use App\Http\Controllers\CaffeineCalculatorController;

covers(CaffeineCalculatorController::class);

it('keeps the public caffeine reference page available without an assessment form', function (string $locale, string $path): void {
    $this->get($path)
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('caffeine-calculator')
            ->where('locale', $locale)
            ->where('seo.appName', config('app.name'))
            ->where('seo.appUrl', url('/'))
            ->where('seo.canonicalUrl', url($path))
            ->has('translations.caffeine.availability_title')
            ->where('translations.caffeine.seo_single_dose_body', fn (string $body): bool => str_contains($body, $locale === 'mn' ? '3 мг/кг' : '3 mg/kg'))
            ->where('translations.caffeine.seo_daily_intake_body', fn (string $body): bool => str_contains($body, $locale === 'mn' ? '400 мг' : '400 mg'))
            ->missing('translations.caffeine.submit_button')
            ->missing('bedtimeLabels'));
})->with([
    'English' => ['en', '/tools/caffeine-calculator'],
    'Mongolian' => ['mn', '/mn/tools/caffeine-calculator'],
]);

it('withholds personalized results from direct or stale clients without prompting AI', function (array $payload): void {
    CaffeineGuidanceAgent::fake();

    $this->postJson(route('caffeine-calculator.plan'), $payload)
        ->assertServiceUnavailable()
        ->assertExactJson(['error' => 'guidance_unavailable']);

    CaffeineGuidanceAgent::assertNeverPrompted();
})->with([
    'empty payload' => [[]],
    'previously valid high-weight profile' => [[
        'weight_kg' => 150,
        'sex' => 'female',
        'sensitivity' => 'low',
    ]],
    'previously valid caution context' => [[
        'weight_kg' => 70,
        'sex' => 'female',
        'sensitivity' => 'normal',
        'conditions' => ['pregnancy'],
    ]],
]);
