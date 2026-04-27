<?php

declare(strict_types=1);

use App\Ai\Agents\BrewBuddyAgent;
use App\Http\Controllers\CaffeineCalculatorController;

covers(CaffeineCalculatorController::class);

it('returns 200 for the caffeine calculator route without authentication', function (): void {
    $this->get(route('caffeine-calculator'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('caffeine-calculator'));
});

it('rejects the plan endpoint when prompt is missing', function (): void {
    $this->postJson(route('caffeine-calculator.plan'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['prompt']);
});

it('returns a brew plan spec from the agent', function (): void {
    BrewBuddyAgent::fake([
        [
            'summary' => 'A balanced focus plan with one morning espresso and afternoon green tea.',
            'blocks' => [
                ['type' => 'Hero', 'props' => ['title' => 'Steady focus', 'subtitle' => 'A two-drink day']],
                ['type' => 'Stat', 'props' => ['label' => 'Total mg', 'value' => '92', 'tone' => 'good']],
                ['type' => 'DrinkCard', 'props' => [
                    'name' => 'Espresso',
                    'volume_oz' => 1.0,
                    'caffeine_mg' => 64,
                    'time_hint' => '08:30',
                    'reason' => 'Tight, focused jolt before the demo.',
                ]],
            ],
        ],
    ]);

    $this->postJson(route('caffeine-calculator.plan'), [
        'prompt' => 'I have a 9am demo and need focus, ~75kg.',
    ])
        ->assertSuccessful()
        ->assertJsonStructure([
            'summary',
            'spec' => ['root', 'elements'],
        ])
        ->assertJsonPath('spec.root', 'root')
        ->assertJsonPath('spec.elements.root.type', 'Stack')
        ->assertJsonPath('spec.elements.b0.type', 'Hero');
});
