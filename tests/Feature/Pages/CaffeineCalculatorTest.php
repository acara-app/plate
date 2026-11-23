<?php

declare(strict_types=1);

use App\Http\Controllers\CaffeineCalculatorController;
use Illuminate\Support\Facades\Route;

covers(CaffeineCalculatorController::class);

it('serves the public caffeine intake calculator with localized reference guidance', function (string $locale, string $path): void {
    $this->get($path)
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('caffeine-calculator')
            ->where('locale', $locale)
            ->where('seo.appName', config('app.name'))
            ->where('seo.appUrl', url('/'))
            ->where('seo.canonicalUrl', url($path))
            ->has('translations.caffeine.intake_title')
            ->has('translations.caffeine.intake_comparison_scope')
            ->missing('translations.caffeine.availability_title')
            ->where('translations.caffeine.seo_single_dose_body', fn (string $body): bool => str_contains($body, $locale === 'mn' ? '3 мг/кг' : '3 mg/kg'))
            ->where('translations.caffeine.seo_daily_intake_body', fn (string $body): bool => str_contains($body, $locale === 'mn' ? '400 мг' : '400 mg'))
            ->missing('translations.caffeine.submit_button')
            ->missing('bedtimeLabels'));
})->with([
    'English' => ['en', '/tools/caffeine-calculator'],
    'Mongolian' => ['mn', '/mn/tools/caffeine-calculator'],
]);

it('does not expose the retired caffeine assessment endpoint', function (): void {
    expect(Route::has('caffeine-calculator.plan'))->toBeFalse();

    $this->postJson('/tools/caffeine-calculator/plan', [
        'weight_kg' => 150,
        'sex' => 'female',
        'sensitivity' => 'low',
    ])->assertNotFound();
});
