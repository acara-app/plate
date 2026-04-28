<?php

declare(strict_types=1);

use App\Ai\Agents\CaffeineGuidanceAgent;
use App\Http\Controllers\CaffeineCalculatorController;

covers(CaffeineCalculatorController::class);

it('returns 200 for the caffeine calculator route without authentication', function (): void {
    $this->get(route('caffeine-calculator'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('caffeine-calculator')
            ->where('seo.appName', config('app.name'))
            ->where('seo.appUrl', url('/'))
            ->where('seo.canonicalUrl', route('caffeine-calculator')));
});

it('rejects the assessment endpoint when required inputs are missing', function (): void {
    $this->postJson(route('caffeine-calculator.plan'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['height_cm', 'sensitivity']);
});

it('returns a caffeine guidance spec from the agent', function (): void {
    CaffeineGuidanceAgent::fake([
        [
            'summary' => 'Keep caffeine under 150 mg per day.',
            'verdict_card' => [
                'title' => '150 mg is your limit',
                'body' => 'Because your context calls for a lower cap, treat anything above 150 mg as too much.',
                'badge' => 'Normal sensitivity',
                'tone' => 'amber',
                'limit_mg' => 150,
            ],
            'limit_gauge' => [
                'label' => 'Daily caffeine limit',
                'value_label' => '150 mg',
                'limit_mg' => 150,
                'max_mg' => 400,
                'tone' => 'amber',
                'caption' => 'Adjusted from the adult reference limit.',
            ],
            'guidance_list' => [
                'title' => 'What to do today',
                'items' => ['Stay below 150 mg.', 'Stop if you feel jittery.'],
            ],
            'context_note' => [
                'title' => 'Context changed the limit',
                'body' => 'Pregnancy or breastfeeding context lowers the starting ceiling.',
            ],
            'safety_note' => [
                'title' => 'Safety note',
                'body' => 'This is educational guidance, not medical advice.',
                'items' => ['Medication interactions', 'Heart symptoms'],
            ],
        ],
    ]);

    $this->postJson(route('caffeine-calculator.plan'), [
        'height_cm' => 170,
        'sensitivity' => 'normal',
        'context' => 'Breastfeeding',
    ])
        ->assertSuccessful()
        ->assertJsonStructure([
            'summary',
            'limit',
            'spec' => ['root', 'elements'],
        ])
        ->assertJsonPath('summary', 'Keep caffeine under 150 mg per day.')
        ->assertJsonPath('spec.root', 'root')
        ->assertJsonPath('spec.elements.root.type', 'Stack')
        ->assertJsonPath('spec.elements.verdict.type', 'VerdictCard')
        ->assertJsonPath('spec.elements.gauge.type', 'LimitGauge')
        ->assertJsonPath('spec.elements.guidance.type', 'GuidanceList');
});
