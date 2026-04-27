<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

it('returns 200 for the caffeine calculator route without authentication', function (): void {
    $this->get(route('caffeine-calculator'))
        ->assertSuccessful();
});

it('renders the caffeine calculator under the mini-app layout with the expected title', function (): void {
    $this->get(route('caffeine-calculator'))
        ->assertSuccessful()
        ->assertSee('<title>Coffee Caffeine Calculator: How Much Is Too Much?</title>', false)
        ->assertSee('Caffeine Calculator');
});

it('renders the H1 and subheading copy', function (): void {
    $this->get(route('caffeine-calculator'))
        ->assertSuccessful()
        ->assertSeeInOrder([
            '<h1',
            'Coffee Caffeine Calculator: How Much Is Too Much?',
            '</h1>',
            'Choose your drink, tell us about you, and find your safe daily limit.',
        ], false);
});

it('renders the standard form card wrapper with Acara tokens and 24px-separated rows', function (): void {
    $response = $this->get(route('caffeine-calculator'))->assertSuccessful();

    $response->assertSeeInOrder([
        'data-testid="caffeine-form-card"',
        'rounded-xl',
        'border-gray-200',
        'bg-white',
        'data-testid="caffeine-form-rows"',
        'space-y-6',
    ], false);
});

it('renders a self-referential canonical link tag and a meta description', function (): void {
    $response = $this->get(route('caffeine-calculator'))->assertSuccessful();

    $canonicalUrl = strtok(route('caffeine-calculator'), '?');

    $response->assertSee('<link rel="canonical" href="'.$canonicalUrl.'"', false)
        ->assertSeeInOrder([
            '<meta name="description"',
            'content="Free caffeine calculator: estimate your safe daily caffeine dose and find out when to stop drinking coffee for better sleep."',
        ], false);
});

it('renders a number input bound to the weight property with an inline error slot', function (): void {
    $this->get(route('caffeine-calculator'))
        ->assertSuccessful()
        ->assertSeeInOrder([
            'data-testid="caffeine-form-row-weight"',
            'for="caffeine-weight"',
            'Your weight',
            'type="number"',
            'id="caffeine-weight"',
            'wire:model.blur="weight"',
        ], false);
});

it('blocks calculation and shows an inline message when weight is blank', function (): void {
    Livewire::test('pages::caffeine-calculator')
        ->set('weight', '')
        ->call('calculate')
        ->assertHasErrors(['weight' => 'required'])
        ->assertSee('Enter your weight to calculate.');
});

it('blocks calculation and shows an inline message when weight is non-numeric', function (): void {
    Livewire::test('pages::caffeine-calculator')
        ->set('weight', 'abc')
        ->call('calculate')
        ->assertHasErrors(['weight' => 'numeric'])
        ->assertSee('Weight must be a number.');
});

it('blocks calculation and shows an inline message when weight is negative', function (): void {
    Livewire::test('pages::caffeine-calculator')
        ->set('weight', '-5')
        ->call('calculate')
        ->assertHasErrors(['weight' => 'gt'])
        ->assertSee('Weight must be greater than 0.');
});

it('validates inline as the weight field is updated', function (): void {
    Livewire::test('pages::caffeine-calculator')
        ->set('weight', '')
        ->assertHasErrors(['weight' => 'required'])
        ->set('weight', '70')
        ->assertHasNoErrors('weight');
});

it('renders a 2-segment weight unit toggle with kg active by default', function (): void {
    $this->get(route('caffeine-calculator'))
        ->assertSuccessful()
        ->assertSeeInOrder([
            'data-testid="caffeine-weight-unit-toggle"',
            'data-testid="caffeine-weight-unit-kg"',
            'aria-pressed="true"',
            'bg-emerald-600',
            'Kilos',
            'data-testid="caffeine-weight-unit-lb"',
            'aria-pressed="false"',
            'Pounds',
        ], false);
});

it('preserves the weight value when toggling units', function (): void {
    Livewire::test('pages::caffeine-calculator')
        ->set('weight', '70')
        ->call('setUnit', 'lb')
        ->assertSet('weightUnit', 'lb')
        ->assertSet('weight', '70')
        ->call('setUnit', 'kg')
        ->assertSet('weightUnit', 'kg')
        ->assertSet('weight', '70');
});

it('persists the weight unit choice via the unit query param', function (): void {
    $this->get(route('caffeine-calculator', ['unit' => 'lb']))
        ->assertSuccessful()
        ->assertSeeInOrder([
            'data-testid="caffeine-weight-unit-lb"',
            'aria-pressed="true"',
            'bg-emerald-600',
            'Pounds',
        ], false);
});

it('ignores unsupported unit values', function (): void {
    Livewire::test('pages::caffeine-calculator')
        ->call('setUnit', 'stone')
        ->assertSet('weightUnit', 'kg');
});

it('renders a 5-step sensitivity segmented control with step 3 selected by default', function (): void {
    $this->get(route('caffeine-calculator'))
        ->assertSuccessful()
        ->assertSeeInOrder([
            'data-testid="caffeine-form-row-sensitivity"',
            'Caffeine sensitivity',
            'data-testid="caffeine-sensitivity-rail"',
            'data-testid="caffeine-sensitivity-step-1"',
            'aria-checked="false"',
            'data-testid="caffeine-sensitivity-step-2"',
            'aria-checked="false"',
            'data-testid="caffeine-sensitivity-step-3"',
            'aria-checked="true"',
            'bg-emerald-600',
            'ring-white',
            'data-testid="caffeine-sensitivity-step-4"',
            'aria-checked="false"',
            'data-testid="caffeine-sensitivity-step-5"',
            'aria-checked="false"',
            'More tolerant',
            'Normal',
            'More sensitive',
        ], false);
});

it('changes sensitivity selection when a step is clicked', function (): void {
    Livewire::test('pages::caffeine-calculator')
        ->assertSet('sensitivity', 3)
        ->call('setSensitivity', 1)
        ->assertSet('sensitivity', 1)
        ->call('setSensitivity', 5)
        ->assertSet('sensitivity', 5);
});

it('ignores out-of-range sensitivity values', function (): void {
    Livewire::test('pages::caffeine-calculator')
        ->call('setSensitivity', 0)
        ->assertSet('sensitivity', 3)
        ->call('setSensitivity', 6)
        ->assertSet('sensitivity', 3);
});

it('renders the How Much Coffee? primary CTA with solid emerald and responsive width', function (): void {
    $this->get(route('caffeine-calculator'))
        ->assertSuccessful()
        ->assertSeeInOrder([
            'data-testid="caffeine-cta-calculate"',
            'w-full',
            'rounded-lg',
            'bg-emerald-500',
            'sm:w-auto',
            'How Much Coffee?',
        ], false);
});

it('uses spec hover, focus, and 150ms transition states on the primary CTA', function (): void {
    $response = $this->get(route('caffeine-calculator'))->assertSuccessful();

    $response->assertSee('hover:-translate-y-px', false)
        ->assertSee('hover:bg-emerald-600', false)
        ->assertSee('focus:ring-2', false)
        ->assertSee('focus:ring-emerald-500', false)
        ->assertSee('focus:ring-offset-2', false)
        ->assertSee('duration-150', false);
});

it('does not use a gradient on the primary CTA background', function (): void {
    $response = $this->get(route('caffeine-calculator'))->assertSuccessful();

    $response->assertDontSee('bg-gradient', false);
});

it('emits parseable WebApplication and FAQPage JSON-LD blocks', function (): void {
    $html = $this->get(route('caffeine-calculator'))
        ->assertSuccessful()
        ->getContent();

    preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', (string) $html, $matches);

    $blocks = collect($matches[1])
        ->map(fn (string $json): ?array => json_decode(mb_trim($json), true))
        ->filter();

    expect($blocks)->not->toBeEmpty();

    $webApp = $blocks->firstWhere('@type', 'WebApplication');
    expect($webApp)->not->toBeNull()
        ->and($webApp['name'] ?? null)->toBeString()->not->toBe('')
        ->and($webApp['applicationCategory'] ?? null)->toBe('HealthApplication');

    $faq = $blocks->firstWhere('@type', 'FAQPage');
    expect($faq)->not->toBeNull()
        ->and($faq['mainEntity'] ?? null)->toBeArray()->not->toBeEmpty()
        ->and($faq['mainEntity'][0]['@type'] ?? null)->toBe('Question')
        ->and($faq['mainEntity'][0]['acceptedAnswer']['text'] ?? null)->toBeString()->not->toBe('');
});

it('logs a weight_entered tool event with the bucketed kilogram weight only', function (): void {
    Livewire::test('pages::caffeine-calculator')
        ->set('weight', '72');

    $row = DB::table('tool_events')
        ->where('event_name', 'weight_entered')
        ->latest('id')
        ->first();

    expect($row)->not->toBeNull()
        ->and($row->tool_name)->toBe('caffeine-calculator');

    $properties = json_decode($row->properties, true);

    expect($properties)->toHaveKey('weight_kg', '70-79')
        ->and($properties)->not->toHaveKey('weight');
});

it('does not log a weight_entered tool event when the weight is invalid', function (): void {
    Livewire::test('pages::caffeine-calculator')
        ->set('weight', '');

    expect(DB::table('tool_events')->where('event_name', 'weight_entered')->count())->toBe(0);
});

it('logs a weight_entered tool event with a kilogram bucket converted from pounds', function (): void {
    Livewire::test('pages::caffeine-calculator')
        ->set('weightUnit', 'lb')
        ->set('weight', '154');

    $row = DB::table('tool_events')
        ->where('event_name', 'weight_entered')
        ->latest('id')
        ->first();

    expect($row)->not->toBeNull();

    $properties = json_decode($row->properties, true);

    expect($properties)->toHaveKey('weight_kg', '60-69');
});

it('logs a unit_toggled tool event recording lb when switching to pounds', function (): void {
    Livewire::test('pages::caffeine-calculator')
        ->call('setUnit', 'lb');

    $row = DB::table('tool_events')
        ->where('event_name', 'unit_toggled')
        ->latest('id')
        ->first();

    expect($row)->not->toBeNull()
        ->and($row->tool_name)->toBe('caffeine-calculator');

    $properties = json_decode($row->properties, true);

    expect($properties)->toHaveKey('unit', 'lb');
});

it('logs a unit_toggled tool event recording kg when switching back to kilograms', function (): void {
    Livewire::test('pages::caffeine-calculator')
        ->set('weightUnit', 'lb')
        ->call('setUnit', 'kg');

    $row = DB::table('tool_events')
        ->where('event_name', 'unit_toggled')
        ->latest('id')
        ->first();

    expect($row)->not->toBeNull();

    $properties = json_decode($row->properties, true);

    expect($properties)->toHaveKey('unit', 'kg');
});

it('does not log a unit_toggled tool event when the unit value is unsupported', function (): void {
    Livewire::test('pages::caffeine-calculator')
        ->call('setUnit', 'stone');

    expect(DB::table('tool_events')->where('event_name', 'unit_toggled')->count())->toBe(0);
});

it('registers the caffeine calculator route at /tools/caffeine-calculator without auth middleware', function (): void {
    $route = collect(app('router')->getRoutes())
        ->first(fn ($route) => $route->getName() === 'caffeine-calculator');

    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe('tools/caffeine-calculator')
        ->and($route->gatherMiddleware())->not->toContain('auth')
        ->and($route->gatherMiddleware())->not->toContain('auth:web');
});
