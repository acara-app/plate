<?php

declare(strict_types=1);

use App\Livewire\SpikeCalculator;
use Livewire\Livewire;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\TextResponseFake;

it('renders the spike calculator component', function (): void {
    Livewire::test(SpikeCalculator::class)
        ->assertStatus(200)
        ->assertSee('Will It Spike?')
        ->assertSee('Type in a food to check its impact.');
});

it('has food input field', function (): void {
    Livewire::test(SpikeCalculator::class)
        ->assertSee('e.g. 2 slices of pepperoni pizza');
});

it('validates food is required', function (): void {
    Livewire::test(SpikeCalculator::class)
        ->set('food', '')
        ->call('predict')
        ->assertHasErrors(['food' => 'required']);
});

it('validates food minimum length', function (): void {
    Livewire::test(SpikeCalculator::class)
        ->set('food', 'a')
        ->call('predict')
        ->assertHasErrors(['food' => 'min']);
});

it('validates food maximum length', function (): void {
    Livewire::test(SpikeCalculator::class)
        ->set('food', str_repeat('a', 501))
        ->call('predict')
        ->assertHasErrors(['food' => 'max']);
});

it('sets example food when clicking example button', function (): void {
    Livewire::test(SpikeCalculator::class)
        ->call('setExample', 'White rice with chicken')
        ->assertSet('food', 'White rice with chicken');
});

it('displays result after successful prediction', function (): void {
    Prism::fake([
        TextResponseFake::make()
            ->withText('{"risk_level": "high", "estimated_gl": 43, "explanation": "White rice is a refined carbohydrate.", "smart_fix": "Try cauliflower rice instead.", "spike_reduction_percentage": 40}'),
    ]);

    Livewire::test(SpikeCalculator::class)
        ->set('food', 'White rice')
        ->call('predict')
        ->assertSet('result.riskLevel', 'high')
        ->assertSee('HIGH')
        ->assertSee('White rice is a refined carbohydrate.')
        ->assertSee('Try cauliflower rice instead.')
        ->assertSee('about 40% lower');
});

it('shows example suggestions when no result', function (): void {
    Livewire::test(SpikeCalculator::class)
        ->assertSee('Not sure what to check? Pick one:')
        ->assertSee('White rice with chicken')
        ->assertSee('Overnight oats with berries')
        ->assertSee('Chocolate chip cookie')
        ->assertSee('Grilled salmon with quinoa');
});

it('shows all risk levels correctly', function (string $riskLevel, string $label): void {
    Prism::fake([
        TextResponseFake::make()
            ->withText('{"risk_level": "'.$riskLevel.'", "estimated_gl": 25, "explanation": "Test explanation.", "smart_fix": "Test smart fix.", "spike_reduction_percentage": 20}'),
    ]);

    Livewire::test(SpikeCalculator::class)
        ->set('food', 'Test food')
        ->call('predict')
        ->assertSee($label);
})->with([
    'low risk' => ['low', 'LOW'],
    'medium risk' => ['medium', 'MEDIUM'],
    'high risk' => ['high', 'HIGH'],
]);

it('displays error when prediction fails', function (): void {
    Prism::fake([
        TextResponseFake::make()
            ->withText('invalid json response'),
    ]);

    Livewire::test(SpikeCalculator::class)
        ->set('food', 'Some food')
        ->call('predict')
        ->assertSet('error', 'Something went wrong. Please try again.')
        ->assertSet('result', null);
});

it('returns null risk level when no result', function (): void {
    $component = Livewire::test(SpikeCalculator::class);

    expect($component->instance()->getRiskLevel())->toBeNull();
});
