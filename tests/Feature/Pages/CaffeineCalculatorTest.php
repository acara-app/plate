<?php

declare(strict_types=1);

use App\Ai\Agents\CaffeineGuidanceAgent;
use App\Http\Controllers\CaffeineCalculatorController;
use Tests\Fixtures\CaffeineGuidanceFixture;

covers(CaffeineCalculatorController::class);

it('returns 200 for the caffeine calculator route without authentication', function (): void {
    $this->get(route('caffeine-calculator'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('caffeine-calculator')
            ->where('locale', 'en')
            ->where('bedtimeLabels.22', '10:00 PM')
            ->where('seo.appName', config('app.name'))
            ->where('seo.appUrl', url('/'))
            ->where('seo.canonicalUrl', route('caffeine-calculator')));
});

it('returns 200 for the mongolian caffeine calculator route without authentication', function (): void {
    $this->get(route('caffeine-calculator.locale', ['locale' => 'mn']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('caffeine-calculator')
            ->where('locale', 'mn')
            ->where('bedtimeLabels.22', '22:00')
            ->where('seo.appName', config('app.name'))
            ->where('seo.appUrl', url('/'))
            ->where('seo.canonicalUrl', route('caffeine-calculator.locale', ['locale' => 'mn'])));
});

it('rejects the assessment endpoint when required inputs are missing', function (): void {
    $this->postJson(route('caffeine-calculator.plan'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['weight_kg', 'sex', 'sensitivity']);
});

it('rejects unknown values in the conditions array', function (): void {
    $this->postJson(route('caffeine-calculator.plan'), [
        'weight_kg' => 70,
        'sex' => 'female',
        'sensitivity' => 'normal',
        'conditions' => ['evil_condition'],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['conditions.0']);
});

it('fills the result cards with the numbers, times and links Plate calculated', function (): void {
    $copy = CaffeineGuidanceFixture::response([
        'verdict_card' => ['limit_mg' => 999],
        'limit_gauge' => ['limit_mg' => 999, 'max_mg' => 1000],
        'timing_card' => ['cutoff_24h' => 3, 'bedtime_24h' => 4, 'cutoff_label' => '3:00 AM', 'bedtime_label' => '4:00 AM'],
        'condition_sections' => [
            ['condition' => 'pregnancy', 'title' => 'Grossesse', 'body' => 'Restez sous 200 mg.', 'tone' => 'green', 'link_url' => 'https://example.com/made-up'],
            ['condition' => 'anxiety', 'title' => 'Anxiété', 'body' => 'La caféine peut accentuer l\'anxiété.', 'tone' => 'blue'],
        ],
    ]);
    $copy['safety_note']['items'] = [];
    CaffeineGuidanceAgent::fake([$copy]);

    $this->postJson(route('caffeine-calculator.plan'), [
        'weight_kg' => 70,
        'sex' => 'female',
        'sensitivity' => 'normal',
        'conditions' => ['pregnancy'],
        'bedtime_hour' => 22,
        'locale' => 'fr',
    ])
        ->assertSuccessful()
        ->assertJsonPath('spec.elements.verdict.props.limit_mg', 150)
        ->assertJsonPath('spec.elements.gauge.props.max_mg', 400)
        ->assertJsonPath('spec.elements.drinks.props.limit_mg', 150)
        ->assertJsonPath('spec.elements.timing.props.cutoff_24h', 16)
        ->assertJsonPath('spec.elements.timing.props.cutoff_label', '16:00')
        ->assertJsonPath('spec.elements.condition_0.props.link_url', 'https://www.acog.org/womens-health/faqs/moderate-caffeine-consumption-during-pregnancy')
        ->assertJsonMissingPath('spec.elements.condition_1')
        ->assertJsonPath('spec.elements.safety.props.items', []);
});

it('withholds the result when the model copy is incomplete', function (): void {
    CaffeineGuidanceAgent::fake([CaffeineGuidanceFixture::response([
        'timing_card' => ['title' => ''],
    ])]);

    $this->postJson(route('caffeine-calculator.plan'), [
        'weight_kg' => 70,
        'sex' => 'female',
        'sensitivity' => 'normal',
    ])
        ->assertServiceUnavailable()
        ->assertExactJson(['error' => 'guidance_unavailable']);
});
