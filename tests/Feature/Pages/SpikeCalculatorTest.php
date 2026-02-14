<?php

declare(strict_types=1);

use App\Ai\Agents\SpikePredictorAgent;
use Livewire\Livewire;
use RyanChandler\LaravelCloudflareTurnstile\Facades\Turnstile;

function fakeTurnstile(bool $success = true): void
{
    if ($success) {
        Turnstile::fake();
    } else {
        Turnstile::fake()->fail();
    }
}

it('renders the spike calculator page', function (): void {
    Livewire::test('pages::spike-calculator')
        ->assertStatus(200)
        ->assertSee('Which Foods Spike Your Blood Sugar?')
        ->assertSee('Free AI-powered glucose spike checker');
});

it('has food input field', function (): void {
    Livewire::test('pages::spike-calculator')
        ->assertSee('e.g. white rice, chocolate cake, or grilled salmon');
});

it('validates food is required', function (): void {
    fakeTurnstile();

    Livewire::test('pages::spike-calculator')
        ->set('food', '')
        ->set('turnstileToken', Turnstile::dummy())
        ->call('predict')
        ->assertHasErrors(['food' => 'required']);
});

it('validates food minimum length', function (): void {
    fakeTurnstile();

    Livewire::test('pages::spike-calculator')
        ->set('food', 'a')
        ->set('turnstileToken', Turnstile::dummy())
        ->call('predict')
        ->assertHasErrors(['food' => 'min']);
});

it('validates food maximum length', function (): void {
    fakeTurnstile();

    Livewire::test('pages::spike-calculator')
        ->set('food', str_repeat('a', 501))
        ->set('turnstileToken', Turnstile::dummy())
        ->call('predict')
        ->assertHasErrors(['food' => 'max']);
});

it('sets example food when clicking example button', function (): void {
    Livewire::test('pages::spike-calculator')
        ->call('setExample', 'White rice with chicken')
        ->assertSet('food', 'White rice with chicken');
});

it('displays result after successful prediction', function (): void {
    fakeTurnstile();

    SpikePredictorAgent::fake([
        '{"risk_level": "high", "estimated_gl": 43, "explanation": "White rice is a refined carbohydrate.", "smart_fix": "Try cauliflower rice instead.", "spike_reduction_percentage": 40}',
    ]);

    Livewire::test('pages::spike-calculator')
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
    Livewire::test('pages::spike-calculator')
        ->assertSee('Not sure what to check? Pick one:')
        ->assertSee('White rice with chicken')
        ->assertSee('Overnight oats with berries')
        ->assertSee('Chocolate chip cookie')
        ->assertSee('Grilled salmon with quinoa');
});

it('shows all risk levels correctly', function (string $riskLevel, string $label): void {
    fakeTurnstile();

    SpikePredictorAgent::fake([
        '{"risk_level": "'.$riskLevel.'", "estimated_gl": 25, "explanation": "Test explanation.", "smart_fix": "Test smart fix.", "spike_reduction_percentage": 20}',
    ]);

    Livewire::test('pages::spike-calculator')
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

    SpikePredictorAgent::fake(function (): void {
        throw new Exception('AI prediction failed');
    });

    Livewire::test('pages::spike-calculator')
        ->set('food', 'Some food')
        ->set('turnstileToken', Turnstile::dummy())
        ->call('predict')
        ->assertSet('error', 'Something went wrong. Please try again.')
        ->assertSet('result', null);
});

it('returns null risk level when no result', function (): void {
    $component = Livewire::test('pages::spike-calculator');

    expect($component->instance()->getRiskLevel())->toBeNull();
});

it('validates turnstile token is required in testing environment', function (): void {
    fakeTurnstile();

    Livewire::test('pages::spike-calculator')
        ->set('food', 'White rice')
        ->call('predict')
        ->assertHasErrors(['turnstileToken' => 'required']);
});

it('validates turnstile token with failed verification', function (): void {
    fakeTurnstile(success: false);

    Livewire::test('pages::spike-calculator')
        ->set('food', 'White rice')
        ->set('turnstileToken', Turnstile::dummy())
        ->call('predict')
        ->assertHasErrors(['turnstileToken']);
});

it('populates food input from compare param on mount', function (): void {
    Livewire::test('pages::spike-calculator', ['compare' => 'Brown Rice vs White Rice'])
        ->assertSet('food', 'Brown Rice vs White Rice');
});
