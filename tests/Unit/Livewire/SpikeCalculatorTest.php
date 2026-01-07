<?php

declare(strict_types=1);

use App\Livewire\SpikeCalculator;
use Livewire\Livewire;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\TextResponseFake;
use RyanChandler\LaravelCloudflareTurnstile\Facades\Turnstile;

function fakeTurnstile(bool $success = true): void
{
    if ($success) {
        Turnstile::fake();
    } else {
        Turnstile::fake()->fail();
    }
}

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
    fakeTurnstile();

    Livewire::test(SpikeCalculator::class)
        ->set('food', '')
        ->set('turnstileToken', Turnstile::dummy())
        ->call('predict')
        ->assertHasErrors(['food' => 'required']);
});

it('validates food minimum length', function (): void {
    fakeTurnstile();

    Livewire::test(SpikeCalculator::class)
        ->set('food', 'a')
        ->set('turnstileToken', Turnstile::dummy())
        ->call('predict')
        ->assertHasErrors(['food' => 'min']);
});

it('validates food maximum length', function (): void {
    fakeTurnstile();

    Livewire::test(SpikeCalculator::class)
        ->set('food', str_repeat('a', 501))
        ->set('turnstileToken', Turnstile::dummy())
        ->call('predict')
        ->assertHasErrors(['food' => 'max']);
});

it('sets example food when clicking example button', function (): void {
    Livewire::test(SpikeCalculator::class)
        ->call('setExample', 'White rice with chicken')
        ->assertSet('food', 'White rice with chicken');
});

it('displays result after successful prediction', function (): void {
    fakeTurnstile();

    Prism::fake([
        TextResponseFake::make()
            ->withText('{"risk_level": "high", "estimated_gl": 43, "explanation": "White rice is a refined carbohydrate.", "smart_fix": "Try cauliflower rice instead.", "spike_reduction_percentage": 40}'),
    ]);

    Livewire::test(SpikeCalculator::class)
        ->set('food', 'White rice')
        ->set('turnstileToken', Turnstile::dummy())
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
    fakeTurnstile();

    Prism::fake([
        TextResponseFake::make()
            ->withText('{"risk_level": "'.$riskLevel.'", "estimated_gl": 25, "explanation": "Test explanation.", "smart_fix": "Test smart fix.", "spike_reduction_percentage": 20}'),
    ]);

    Livewire::test(SpikeCalculator::class)
        ->set('food', 'Test food')
        ->set('turnstileToken', Turnstile::dummy())
        ->call('predict')
        ->assertSee($label);
})->with([
    'low risk' => ['low', 'LOW'],
    'medium risk' => ['medium', 'MEDIUM'],
    'high risk' => ['high', 'HIGH'],
]);

it('displays error when prediction fails', function (): void {
    fakeTurnstile();

    Prism::fake([
        TextResponseFake::make()
            ->withText('invalid json response'),
    ]);

    Livewire::test(SpikeCalculator::class)
        ->set('food', 'Some food')
        ->set('turnstileToken', Turnstile::dummy())
        ->call('predict')
        ->assertSet('error', 'Something went wrong. Please try again.')
        ->assertSet('result', null);
});

it('returns null risk level when no result', function (): void {
    $component = Livewire::test(SpikeCalculator::class);

    expect($component->instance()->getRiskLevel())->toBeNull();
});

it('validates turnstile token is required in testing environment', function (): void {
    fakeTurnstile();

    Livewire::test(SpikeCalculator::class)
        ->set('food', 'White rice')
        ->call('predict')
        ->assertHasErrors(['turnstileToken' => 'required']);
});

it('validates turnstile token with failed verification', function (): void {
    fakeTurnstile(success: false);

    Livewire::test(SpikeCalculator::class)
        ->set('food', 'White rice')
        ->set('turnstileToken', Turnstile::dummy())
        ->call('predict')
        ->assertHasErrors(['turnstileToken']);
});

it('populates food input from compare param on mount', function (): void {
    Livewire::test(SpikeCalculator::class, ['compare' => 'Brown Rice vs White Rice'])
        ->assertSet('food', 'Brown Rice vs White Rice');
});
