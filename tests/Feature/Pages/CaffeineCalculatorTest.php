<?php

declare(strict_types=1);

use App\Actions\CalculateCaffeineSafeDose;
use App\Actions\CalculateCaffeineSleepCutoff;
use App\Models\CaffeineDrink;
use App\Models\User;
use App\Utilities\WeightConverter;
use Carbon\CarbonImmutable;
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

it('wires arrow-left and arrow-right keyboard navigation on the sensitivity stepper', function (): void {
    $this->get(route('caffeine-calculator'))
        ->assertSuccessful()
        ->assertSee('x-on:keydown.arrow-right.prevent', false)
        ->assertSee('x-on:keydown.arrow-left.prevent', false);
});

it('announces the current sensitivity step via an aria-live region', function (): void {
    $this->get(route('caffeine-calculator'))
        ->assertSuccessful()
        ->assertSeeInOrder([
            'data-testid="caffeine-sensitivity-announcement"',
            'aria-live="polite"',
            'Sensitivity: Normal, step 3 of 5',
        ], false);
});

it('updates the sensitivity announcement when the step changes', function (): void {
    Livewire::test('pages::caffeine-calculator')
        ->assertSee('Sensitivity: Normal, step 3 of 5')
        ->call('setSensitivity', 1)
        ->assertSee('Sensitivity: More tolerant, step 1 of 5')
        ->call('setSensitivity', 5)
        ->assertSee('Sensitivity: More sensitive, step 5 of 5');
});

it('uses a roving tabindex so only the selected sensitivity step is tabbable', function (): void {
    $response = $this->get(route('caffeine-calculator'))->assertSuccessful();

    $response->assertSeeInOrder([
        'data-testid="caffeine-sensitivity-step-3"',
        'tabindex="0"',
    ], false);

    foreach ([1, 2, 4, 5] as $step) {
        $response->assertSeeInOrder([
            'data-testid="caffeine-sensitivity-step-'.$step.'"',
            'tabindex="-1"',
        ], false);
    }
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

it('renders the drink typeahead with the Choose a coffee label and Americano placeholder', function (): void {
    CaffeineDrink::factory()->create(['name' => 'Americano', 'slug' => 'americano']);

    $this->get(route('caffeine-calculator'))
        ->assertSuccessful()
        ->assertSeeInOrder([
            'data-testid="caffeine-form-row-drink"',
            'for="caffeine-drink"',
            'Choose a coffee',
            'id="caffeine-drink"',
            'role="combobox"',
            'aria-autocomplete="list"',
            'aria-controls="caffeine-drink-listbox"',
            'placeholder="eg. Americano"',
        ], false);
});

it('ranks drink typeahead matches as exact, then prefix, then substring', function (): void {
    CaffeineDrink::factory()->create(['name' => 'Caramel Americano', 'slug' => 'caramel-americano']);
    CaffeineDrink::factory()->create(['name' => 'Iced Americano', 'slug' => 'iced-americano']);
    CaffeineDrink::factory()->create(['name' => 'Americano', 'slug' => 'americano']);
    CaffeineDrink::factory()->create(['name' => 'Americano with Milk', 'slug' => 'americano-milk']);
    CaffeineDrink::factory()->create(['name' => 'Latte', 'slug' => 'latte']);

    $component = Livewire::test('pages::caffeine-calculator')
        ->set('drinkQuery', 'ameri');

    $names = collect($component->instance()->drinkOptions)
        ->pluck('name')
        ->all();

    expect($names)->toBe([
        'Americano',
        'Americano with Milk',
        'Caramel Americano',
        'Iced Americano',
    ]);
});

it('does not render the drink dropdown listbox when the query is empty', function (): void {
    CaffeineDrink::factory()->create(['name' => 'Americano', 'slug' => 'americano']);

    $this->get(route('caffeine-calculator'))
        ->assertSuccessful()
        ->assertDontSee('id="caffeine-drink-listbox"', false);
});

it('renders the drink dropdown with floating shadow and listbox semantics when the query has matches', function (): void {
    CaffeineDrink::factory()->create(['name' => 'Americano', 'slug' => 'americano']);
    CaffeineDrink::factory()->create(['name' => 'Iced Americano', 'slug' => 'iced-americano']);

    $html = Livewire::test('pages::caffeine-calculator')
        ->set('drinkQuery', 'ameri')
        ->html();

    expect($html)
        ->toContain('id="caffeine-drink-listbox"')
        ->toContain('role="listbox"')
        ->toContain('shadow-lg')
        ->toContain('role="option"')
        ->toContain('Americano')
        ->toContain('Iced Americano');

    expect(mb_strpos($html, 'id="caffeine-drink-listbox"'))
        ->toBeLessThan(mb_strpos($html, 'role="option"'));
    expect(mb_strpos($html, 'Americano'))
        ->toBeLessThan(mb_strpos($html, 'Iced Americano'));
});

it('selects a drink when selectDrink is called and reflects it in the input value', function (): void {
    $drink = CaffeineDrink::factory()->create(['name' => 'Americano', 'slug' => 'americano']);

    Livewire::test('pages::caffeine-calculator')
        ->set('drinkQuery', 'ameri')
        ->call('selectDrink', $drink->id)
        ->assertSet('drinkId', $drink->id)
        ->assertSet('drinkQuery', 'Americano');
});

it('wires the drink typeahead input for keyboard accessibility', function (): void {
    CaffeineDrink::factory()->create(['name' => 'Americano', 'slug' => 'americano']);

    $this->get(route('caffeine-calculator'))
        ->assertSuccessful()
        ->assertSee('x-on:keydown.arrow-down.prevent', false)
        ->assertSee('x-on:keydown.arrow-up.prevent', false)
        ->assertSee('x-on:keydown.enter.prevent', false)
        ->assertSee('x-on:keydown.escape.prevent', false);
});

it('ignores selectDrink calls for unknown drink ids', function (): void {
    Livewire::test('pages::caffeine-calculator')
        ->call('selectDrink', 999_999)
        ->assertSet('drinkId', null)
        ->assertSet('drinkQuery', '');
});

it('logs a page_view tool event once when the component is mounted', function (): void {
    Livewire::test('pages::caffeine-calculator');

    $rows = DB::table('tool_events')
        ->where('event_name', 'page_view')
        ->where('tool_name', 'caffeine-calculator')
        ->get();

    expect($rows)->toHaveCount(1);
});

it('logs a drink_picked tool event with the drink slug when a drink is selected', function (): void {
    $drink = CaffeineDrink::factory()->create(['name' => 'Americano', 'slug' => 'americano']);

    Livewire::test('pages::caffeine-calculator')
        ->call('selectDrink', $drink->id);

    $row = DB::table('tool_events')
        ->where('event_name', 'drink_picked')
        ->latest('id')
        ->first();

    expect($row)->not->toBeNull()
        ->and($row->tool_name)->toBe('caffeine-calculator');

    $properties = json_decode($row->properties, true);

    expect($properties)->toHaveKey('drink', 'americano')
        ->and($properties)->not->toHaveKey('name')
        ->and($properties)->not->toHaveKey('id');
});

it('does not log a drink_picked tool event when selectDrink is called with an unknown id', function (): void {
    Livewire::test('pages::caffeine-calculator')
        ->call('selectDrink', 999_999);

    expect(DB::table('tool_events')->where('event_name', 'drink_picked')->count())->toBe(0);
});

it('produces a SafeDoseData result on submit using the shared LB_TO_KG conversion constant', function (): void {
    $drink = CaffeineDrink::factory()->create([
        'name' => 'Americano',
        'slug' => 'americano',
        'caffeine_mg' => 150,
    ]);

    $component = Livewire::test('pages::caffeine-calculator')
        ->set('weightUnit', 'lb')
        ->set('weight', '154')
        ->call('selectDrink', $drink->id)
        ->call('setSensitivity', 3)
        ->call('calculate');

    $weightKg = WeightConverter::convertToKg(154.0, 'lb');
    $expected = (new CalculateCaffeineSafeDose)->handle(
        weightKg: $weightKg,
        sensitivityStep: 2,
        perCupMg: 150.0,
    );

    $component
        ->assertHasNoErrors()
        ->assertSet('safeMg', $expected->safeMg)
        ->assertSet('safeCups', $expected->cups);
});

it('renders the result panel with cups, safe_mg, and breakdown after calculating', function (): void {
    $drink = CaffeineDrink::factory()->create([
        'name' => 'Americano',
        'slug' => 'americano',
        'caffeine_mg' => 150,
    ]);

    $html = Livewire::test('pages::caffeine-calculator')
        ->set('weight', '70')
        ->call('selectDrink', $drink->id)
        ->call('setSensitivity', 3)
        ->call('calculate')
        ->html();

    $expected = (new CalculateCaffeineSafeDose)->handle(
        weightKg: 70.0,
        sensitivityStep: 2,
        perCupMg: 150.0,
    );

    $cups = $expected->cups;
    $safeMg = (int) round($expected->safeMg);
    $breakdownTotal = 150 * $cups;

    expect($html)
        ->toContain('data-testid="caffeine-result-panel"')
        ->toContain('border-t-4')
        ->toContain('border-t-emerald-500')
        ->toContain('data-testid="caffeine-result-cups"')
        ->toContain('tabular-nums')
        ->toContain((string) $cups.' cups')
        ->toContain('data-testid="caffeine-result-safe-mg"')
        ->toContain((string) $safeMg.' mg')
        ->toContain('data-testid="caffeine-result-breakdown"')
        ->toContain('150')
        ->toContain('mg per cup')
        ->toContain((string) $breakdownTotal);

    expect(mb_strpos($html, 'data-testid="caffeine-result-cups"'))
        ->toBeLessThan(mb_strpos($html, 'data-testid="caffeine-result-safe-mg"'));
    expect(mb_strpos($html, 'data-testid="caffeine-result-safe-mg"'))
        ->toBeLessThan(mb_strpos($html, 'data-testid="caffeine-result-breakdown"'));
});

it('does not render the result panel before the user has calculated', function (): void {
    $this->get(route('caffeine-calculator'))
        ->assertSuccessful()
        ->assertDontSee('data-testid="caffeine-result-panel"', false);
});

it('does not store a safe dose result when no drink is selected', function (): void {
    Livewire::test('pages::caffeine-calculator')
        ->set('weight', '70')
        ->call('calculate')
        ->assertSet('safeMg', null)
        ->assertSet('safeCups', null);
});

it('shows the typical adult range copy under the weight input on initial render', function (): void {
    $this->get(route('caffeine-calculator'))
        ->assertSuccessful()
        ->assertSeeInOrder([
            'data-testid="caffeine-form-row-weight"',
            'data-testid="caffeine-weight-typical-adult-note"',
            'Calibrated for typical adult weights',
            '30',
            '250 kg',
        ], false);
});

it('shows a clamp notice when the entered weight in kg is below the documented minimum', function (): void {
    Livewire::test('pages::caffeine-calculator')
        ->set('weight', '20')
        ->assertSee('outside our documented range')
        ->assertSee('clamped to typical adult weights');
});

it('shows a clamp notice when the entered weight in kg is above the documented maximum', function (): void {
    Livewire::test('pages::caffeine-calculator')
        ->set('weight', '300')
        ->assertSee('outside our documented range');
});

it('shows a clamp notice when the entered weight in pounds falls outside the documented range', function (): void {
    Livewire::test('pages::caffeine-calculator')
        ->set('weightUnit', 'lb')
        ->set('weight', '40')
        ->assertSee('outside our documented range');
});

it('does not show a clamp notice for a typical adult weight inside the documented range', function (): void {
    Livewire::test('pages::caffeine-calculator')
        ->set('weight', '70')
        ->assertDontSee('outside our documented range');
});

it('clamps the safe dose calculation to the documented minimum weight', function (): void {
    $drink = CaffeineDrink::factory()->create([
        'name' => 'Americano',
        'slug' => 'americano',
        'caffeine_mg' => 150,
    ]);

    $component = Livewire::test('pages::caffeine-calculator')
        ->set('weight', '15')
        ->call('selectDrink', $drink->id)
        ->call('setSensitivity', 3)
        ->call('calculate');

    $expected = (new CalculateCaffeineSafeDose)->handle(
        weightKg: 30.0,
        sensitivityStep: 2,
        perCupMg: 150.0,
    );

    $component
        ->assertSet('safeMg', $expected->safeMg)
        ->assertSet('safeCups', $expected->cups);
});

it('clamps the safe dose calculation to the documented maximum weight', function (): void {
    $drink = CaffeineDrink::factory()->create([
        'name' => 'Americano',
        'slug' => 'americano',
        'caffeine_mg' => 150,
    ]);

    $component = Livewire::test('pages::caffeine-calculator')
        ->set('weight', '400')
        ->call('selectDrink', $drink->id)
        ->call('setSensitivity', 3)
        ->call('calculate');

    $expected = (new CalculateCaffeineSafeDose)->handle(
        weightKg: 250.0,
        sensitivityStep: 2,
        perCupMg: 150.0,
    );

    $component
        ->assertSet('safeMg', $expected->safeMg)
        ->assertSet('safeCups', $expected->cups);
});

it('renders an empty drinks state in the picker when no drinks exist', function (): void {
    expect(CaffeineDrink::count())->toBe(0);

    $this->get(route('caffeine-calculator'))
        ->assertSuccessful()
        ->assertSeeInOrder([
            'data-testid="caffeine-form-row-drink"',
            'data-testid="caffeine-drink-empty-state"',
            'refreshing our drinks list',
        ], false)
        ->assertDontSee('id="caffeine-drink"', false)
        ->assertDontSee('role="combobox"', false);
});

it('disables the primary CTA when no drinks exist', function (): void {
    expect(CaffeineDrink::count())->toBe(0);

    $this->get(route('caffeine-calculator'))
        ->assertSuccessful()
        ->assertSeeInOrder([
            'data-testid="caffeine-cta-calculate"',
            'disabled',
            'aria-disabled="true"',
        ], false);
});

it('does not render the empty drinks state when drinks exist', function (): void {
    CaffeineDrink::factory()->create(['name' => 'Americano', 'slug' => 'americano']);

    $this->get(route('caffeine-calculator'))
        ->assertSuccessful()
        ->assertDontSee('data-testid="caffeine-drink-empty-state"', false)
        ->assertDontSee('refreshing our drinks list', false);
});

it('keeps the primary CTA enabled when drinks exist', function (): void {
    CaffeineDrink::factory()->create(['name' => 'Americano', 'slug' => 'americano']);

    $response = $this->get(route('caffeine-calculator'))->assertSuccessful();

    $response->assertSee('aria-disabled="false"', false)
        ->assertDontSee('disabled aria-disabled', false);
});

it('renders a fallback panel when the picked drink lacks a caffeine_mg estimate', function (): void {
    $drink = CaffeineDrink::factory()->create([
        'name' => 'Mystery Brew',
        'slug' => 'mystery-brew',
        'caffeine_mg' => 0,
    ]);

    $component = Livewire::test('pages::caffeine-calculator')
        ->set('weight', '70')
        ->call('selectDrink', $drink->id)
        ->call('calculate');

    $component
        ->assertSet('lacksCaffeineEstimate', true)
        ->assertSet('safeMg', null)
        ->assertSet('safeCups', null)
        ->assertSet('perCupMg', null);

    $html = $component->html();

    expect($html)
        ->toContain('data-testid="caffeine-result-fallback"')
        ->toContain("We don't have a confident estimate for this drink yet.")
        ->toContain('Try picking another drink');

    expect($html)
        ->not->toContain('data-testid="caffeine-result-panel"')
        ->not->toContain('data-testid="caffeine-result-cups"');
});

it('clears the fallback panel and renders results when switching to a drink with an estimate', function (): void {
    $missing = CaffeineDrink::factory()->create([
        'name' => 'Mystery Brew',
        'slug' => 'mystery-brew',
        'caffeine_mg' => 0,
    ]);

    $known = CaffeineDrink::factory()->create([
        'name' => 'Americano',
        'slug' => 'americano',
        'caffeine_mg' => 150,
    ]);

    Livewire::test('pages::caffeine-calculator')
        ->set('weight', '70')
        ->call('selectDrink', $missing->id)
        ->call('calculate')
        ->assertSet('lacksCaffeineEstimate', true)
        ->call('selectDrink', $known->id)
        ->call('calculate')
        ->assertSet('lacksCaffeineEstimate', false)
        ->assertSet('perCupMg', 150.0);
});

it('does not render the bedtime input until the optimise-for-sleep disclosure is opened', function (): void {
    $drink = CaffeineDrink::factory()->create([
        'caffeine_mg' => 100,
    ]);

    $component = Livewire::test('pages::caffeine-calculator')
        ->set('weight', '70')
        ->call('selectDrink', $drink->id)
        ->call('calculate')
        ->assertSet('optimiseForSleep', false);

    $html = $component->html();

    expect($html)
        ->toContain('caffeine-optimise-sleep-toggle')
        ->toContain('Also, when should I stop drinking?')
        ->not->toContain('caffeine-bedtime-input')
        ->not->toContain('id="caffeine-bedtime"');

    $component->call('toggleOptimiseForSleep')
        ->assertSet('optimiseForSleep', true);

    $openHtml = $component->html();

    expect($openHtml)
        ->toContain('caffeine-bedtime-input')
        ->toContain('id="caffeine-bedtime"')
        ->toContain('type="time"');
});

it('hides the bedtime input again when the disclosure is toggled closed', function (): void {
    $drink = CaffeineDrink::factory()->create([
        'caffeine_mg' => 100,
    ]);

    $component = Livewire::test('pages::caffeine-calculator')
        ->set('weight', '70')
        ->call('selectDrink', $drink->id)
        ->call('calculate')
        ->call('toggleOptimiseForSleep')
        ->assertSet('optimiseForSleep', true)
        ->call('toggleOptimiseForSleep')
        ->assertSet('optimiseForSleep', false)
        ->assertSet('bedtime', null);

    expect($component->html())->not->toContain('caffeine-bedtime-input');
});

it('renders the sleep cutoff line using CalculateCaffeineSleepCutoff when bedtime is in the future', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 4, 27, 10, 0, 0));

    $drink = CaffeineDrink::factory()->create([
        'caffeine_mg' => 100,
    ]);

    $component = Livewire::test('pages::caffeine-calculator')
        ->set('weight', '70')
        ->call('selectDrink', $drink->id)
        ->call('calculate')
        ->call('toggleOptimiseForSleep')
        ->set('bedtime', '22:00');

    $bedtimeToday = CarbonImmutable::now()->setTimeFromTimeString('22:00');
    $expected = app(CalculateCaffeineSleepCutoff::class)->handle(
        $bedtimeToday,
        100.0,
        $component->get('safeCups'),
    );

    expect($expected)->toBeInstanceOf(CarbonImmutable::class);

    $html = $component->html();

    expect($html)
        ->toContain('data-testid="caffeine-sleep-cutoff"')
        ->toContain('Stop drinking after')
        ->toContain('to be below 50mg at bedtime.')
        ->toContain($expected->format('g:i A'))
        ->not->toContain('NaN')
        ->not->toContain('Invalid Date');

    CarbonImmutable::setTestNow();
});

it('renders the empty state when the chosen bedtime is already in the past today', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 4, 27, 23, 0, 0));

    $drink = CaffeineDrink::factory()->create([
        'caffeine_mg' => 100,
    ]);

    $component = Livewire::test('pages::caffeine-calculator')
        ->set('weight', '70')
        ->call('selectDrink', $drink->id)
        ->call('calculate')
        ->call('toggleOptimiseForSleep')
        ->set('bedtime', '07:00');

    $html = $component->html();

    expect($html)
        ->toContain('data-testid="caffeine-sleep-cutoff-empty"')
        ->toContain("Pick tonight's bedtime.")
        ->not->toContain('data-testid="caffeine-sleep-cutoff"')
        ->not->toContain('NaN')
        ->not->toContain('Invalid Date');

    CarbonImmutable::setTestNow();
});

it('does not render the sleep cutoff line when bedtime is empty', function (): void {
    $drink = CaffeineDrink::factory()->create([
        'caffeine_mg' => 100,
    ]);

    $html = Livewire::test('pages::caffeine-calculator')
        ->set('weight', '70')
        ->call('selectDrink', $drink->id)
        ->call('calculate')
        ->call('toggleOptimiseForSleep')
        ->html();

    expect($html)
        ->not->toContain('data-testid="caffeine-sleep-cutoff"')
        ->not->toContain('data-testid="caffeine-sleep-cutoff-empty"')
        ->not->toContain('NaN')
        ->not->toContain('Invalid Date');
});

it('renders the How we calculated this disclosure inline as a details element after calculating', function (): void {
    $drink = CaffeineDrink::factory()->create([
        'name' => 'Americano',
        'slug' => 'americano',
        'caffeine_mg' => 150,
        'source' => 'USDA FoodData Central',
        'license_url' => 'https://fdc.nal.usda.gov/',
        'attribution' => 'U.S. Department of Agriculture',
    ]);

    $html = Livewire::test('pages::caffeine-calculator')
        ->set('weight', '70')
        ->call('selectDrink', $drink->id)
        ->call('setSensitivity', 3)
        ->call('calculate')
        ->html();

    expect($html)
        ->toContain('<details')
        ->toContain('data-testid="caffeine-how-calculated"')
        ->toContain('How we calculated this')
        ->toContain('self-reported')
        ->toContain('400 mg/day')
        ->toContain('FDA')
        ->toContain('USDA FoodData Central')
        ->toContain('150 mg');
});

it('does not render the How we calculated this disclosure before a successful calculation', function (): void {
    $this->get(route('caffeine-calculator'))
        ->assertSuccessful()
        ->assertDontSee('data-testid="caffeine-how-calculated"', false)
        ->assertDontSee('How we calculated this');
});

it('places the self-reported phrase near the sensitivity multiplier in the disclosure', function (): void {
    $drink = CaffeineDrink::factory()->create([
        'name' => 'Americano',
        'slug' => 'americano',
        'caffeine_mg' => 150,
    ]);

    $html = Livewire::test('pages::caffeine-calculator')
        ->set('weight', '70')
        ->call('selectDrink', $drink->id)
        ->call('setSensitivity', 3)
        ->call('calculate')
        ->html();

    $sensitivityPos = mb_strpos($html, 'data-testid="caffeine-how-calculated-sensitivity"');
    $selfReportedPos = mb_strpos($html, 'self-reported');

    expect($sensitivityPos)->not->toBeFalse();
    expect($selfReportedPos)->not->toBeFalse();
    expect($selfReportedPos)->toBeGreaterThan($sensitivityPos);
});

it('renders a sign-up CTA card linking to register with source=caffeine_calculator after a successful calculation', function (): void {
    $drink = CaffeineDrink::factory()->create([
        'name' => 'Americano',
        'slug' => 'americano',
        'caffeine_mg' => 150,
    ]);

    $html = Livewire::test('pages::caffeine-calculator')
        ->set('weight', '70')
        ->call('selectDrink', $drink->id)
        ->call('calculate')
        ->html();

    expect($html)
        ->toContain('data-testid="caffeine-signup-cta"')
        ->toContain('data-testid="caffeine-signup-cta-button"')
        ->toContain(route('register').'?source=caffeine_calculator');
});

it('does not render the sign-up CTA before a successful calculation', function (): void {
    $this->get(route('caffeine-calculator'))
        ->assertSuccessful()
        ->assertDontSee('data-testid="caffeine-signup-cta"', false);
});

it('does not render the sign-up CTA for authenticated visitors after a successful calculation', function (): void {
    $drink = CaffeineDrink::factory()->create([
        'name' => 'Americano',
        'slug' => 'americano',
        'caffeine_mg' => 150,
    ]);

    $html = Livewire::actingAs(User::factory()->create())
        ->test('pages::caffeine-calculator')
        ->set('weight', '70')
        ->call('selectDrink', $drink->id)
        ->call('calculate')
        ->html();

    expect($html)
        ->not->toContain('data-testid="caffeine-signup-cta"')
        ->not->toContain('data-testid="caffeine-signup-cta-button"');
});

it('renders the tiny 12px disclaimer footer below the result after a successful calculation', function (): void {
    $drink = CaffeineDrink::factory()->create([
        'name' => 'Americano',
        'slug' => 'americano',
        'caffeine_mg' => 150,
    ]);

    $html = Livewire::test('pages::caffeine-calculator')
        ->set('weight', '70')
        ->call('selectDrink', $drink->id)
        ->call('calculate')
        ->html();

    expect($html)
        ->toContain('data-testid="caffeine-disclaimer"')
        ->toContain('text-xs')
        ->toContain('Estimates from public sources. Talk to a clinician for medical caffeine guidance.');

    $resultPos = mb_strpos($html, 'data-testid="caffeine-result-panel"');
    $disclaimerPos = mb_strpos($html, 'data-testid="caffeine-disclaimer"');

    expect($resultPos)->not->toBeFalse()
        ->and($disclaimerPos)->not->toBeFalse()
        ->and($disclaimerPos)->toBeGreaterThan($resultPos);
});

it('does not render the disclaimer footer before a successful calculation', function (): void {
    $this->get(route('caffeine-calculator'))
        ->assertSuccessful()
        ->assertDontSee('data-testid="caffeine-disclaimer"', false);
});

it('logs a sensitivity_changed tool event with the bucketed sensitivity step', function (): void {
    Livewire::test('pages::caffeine-calculator')
        ->call('setSensitivity', 5);

    $row = DB::table('tool_events')
        ->where('event_name', 'sensitivity_changed')
        ->latest('id')
        ->first();

    expect($row)->not->toBeNull()
        ->and($row->tool_name)->toBe('caffeine-calculator');

    $properties = json_decode($row->properties, true);

    expect($properties)->toHaveKey('sensitivity_step', 5);
});

it('does not log a sensitivity_changed tool event when the step is unchanged or out of range', function (): void {
    Livewire::test('pages::caffeine-calculator')
        ->call('setSensitivity', 3)
        ->call('setSensitivity', 0)
        ->call('setSensitivity', 6);

    expect(DB::table('tool_events')->where('event_name', 'sensitivity_changed')->count())->toBe(0);
});

it('logs a calculation_completed tool event with sensitivity_step, safe_mg_bucket, and cups_bucket', function (): void {
    $drink = CaffeineDrink::factory()->create([
        'name' => 'Americano',
        'slug' => 'americano',
        'caffeine_mg' => 150,
    ]);

    Livewire::test('pages::caffeine-calculator')
        ->set('weight', '70')
        ->call('selectDrink', $drink->id)
        ->call('calculate');

    $row = DB::table('tool_events')
        ->where('event_name', 'calculation_completed')
        ->latest('id')
        ->first();

    expect($row)->not->toBeNull()
        ->and($row->tool_name)->toBe('caffeine-calculator');

    $properties = json_decode($row->properties, true);

    $expected = (new CalculateCaffeineSafeDose)->handle(
        weightKg: 70.0,
        sensitivityStep: 2,
        perCupMg: 150.0,
    );

    $logger = app(App\Actions\LogToolEvent::class);

    expect($properties)
        ->toHaveKey('sensitivity_step', 3)
        ->toHaveKey('safe_mg_bucket', $logger->bucketSafeMg($expected->safeMg))
        ->toHaveKey('cups_bucket', $logger->bucketCups($expected->cups))
        ->not->toHaveKey('safe_mg')
        ->not->toHaveKey('cups');
});

it('does not log a calculation_completed tool event when the calculation does not succeed', function (): void {
    $drink = CaffeineDrink::factory()->create([
        'name' => 'Mystery Brew',
        'slug' => 'mystery-brew',
        'caffeine_mg' => 0,
    ]);

    Livewire::test('pages::caffeine-calculator')
        ->set('weight', '70')
        ->call('selectDrink', $drink->id)
        ->call('calculate');

    expect(DB::table('tool_events')->where('event_name', 'calculation_completed')->count())->toBe(0);
});

it('registers the caffeine calculator route at /tools/caffeine-calculator without auth middleware', function (): void {
    $route = collect(app('router')->getRoutes())
        ->first(fn ($route) => $route->getName() === 'caffeine-calculator');

    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe('tools/caffeine-calculator')
        ->and($route->gatherMiddleware())->not->toContain('auth')
        ->and($route->gatherMiddleware())->not->toContain('auth:web');
});
