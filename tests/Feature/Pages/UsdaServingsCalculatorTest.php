<?php

declare(strict_types=1);

use App\Enums\ContentType;
use App\Models\Content;
use Livewire\Livewire;

beforeEach(function (): void {
    // Seed the USDA data for tests
    $this->seed(Database\Seeders\DailyServingSizeSeeder::class);
});

it('renders the usda servings calculator page', function (): void {
    Livewire::test('pages::usda-daily-servings-calculator')
        ->assertStatus(200)
        ->assertSee('USDA 2025-2030 Daily Serving Calculator')
        ->assertSee('How much should you eat?');
});

it('displays the calorie slider with default 2000 calories', function (): void {
    Livewire::test('pages::usda-daily-servings-calculator')
        ->assertSet('calories', 2000)
        ->assertSee('2,000 calories');
});

it('allows changing calorie level via slider', function (): void {
    Livewire::test('pages::usda-daily-servings-calculator')
        ->set('calories', 1600)
        ->assertSet('calories', 1600)
        ->assertSee('1,600 calories');
});

it('displays all six food groups', function (): void {
    Livewire::test('pages::usda-daily-servings-calculator')
        ->assertSee('Protein Foods')
        ->assertSee('Dairy')
        ->assertSee('Vegetables')
        ->assertSee('Fruits')
        ->assertSee('Whole Grains')
        ->assertSee('Healthy Fats');
});

it('displays servings for 2000 calorie diet', function (): void {
    // At 2000 calories, dairy should show 3 cups
    Livewire::test('pages::usda-daily-servings-calculator')
        ->assertSet('calories', 2000)
        ->assertSee('3 cups') // Dairy, Vegetables
        ->assertSee('3-4 oz-eq') // Protein
        ->assertSee('2 cups'); // Fruits
});

it('updates servings when calorie level changes', function (): void {
    // At 1000 calories, dairy should be 2 cups
    Livewire::test('pages::usda-daily-servings-calculator')
        ->set('calories', 1000)
        ->assertSee('2 cups') // Dairy at 1000 cal
        ->assertSee('1-1.5 oz-eq'); // Protein at 1000 cal
});

it('displays low carb mode toggle', function (): void {
    Livewire::test('pages::usda-daily-servings-calculator')
        ->assertSet('lowCarbMode', false)
        ->assertSee('Dietary Mode')
        ->assertSee('Standard USDA 2030 Guidelines');
});

it('toggles low carb mode', function (): void {
    Livewire::test('pages::usda-daily-servings-calculator')
        ->assertSet('lowCarbMode', false)
        ->call('toggleLowCarbMode')
        ->assertSet('lowCarbMode', true)
        ->assertSee('Low-Carb Adjustment');
});

it('shows adjusted badge in low carb mode', function (): void {
    Livewire::test('pages::usda-daily-servings-calculator')
        ->call('toggleLowCarbMode')
        ->assertSee('Adjusted');
});

it('displays sugar limits section', function (): void {
    Livewire::test('pages::usda-daily-servings-calculator')
        ->assertSee('FDA Added Sugar Limits')
        ->assertSeeHtml('Maximum added sugar for "Healthy" food claims');
});

it('displays sugar limit values', function (): void {
    Livewire::test('pages::usda-daily-servings-calculator')
        ->assertSee('Max 2.5g') // Dairy
        ->assertSee('Max 5g') // Grains
        ->assertSee('Max 1g'); // Protein categories
});

it('displays diabetic disclaimer', function (): void {
    Livewire::test('pages::usda-daily-servings-calculator')
        ->assertSee('Important for Diabetics')
        ->assertSee('For Type 2 diabetics, this may cause blood sugar spikes');
});

it('displays faq section', function (): void {
    Livewire::test('pages::usda-daily-servings-calculator')
        ->assertSee('Frequently Asked Questions')
        ->assertSee('What are the USDA 2025-2030 Dietary Guidelines?')
        ->assertSee('How do I know how many calories I need?')
        ->assertSee('What is the Low-Carb Diabetic mode?')
        ->assertSee('What do the FDA sugar limits mean?');
});

it('displays more free tools section', function (): void {
    Livewire::test('pages::usda-daily-servings-calculator')
        ->assertSee('More Free Tools')
        ->assertSee('Spike Calculator')
        ->assertSee('Snap to Track');
});

it('only accepts valid calorie levels', function (): void {
    $component = Livewire::test('pages::usda-daily-servings-calculator')
        ->call('setCalories', 1500); // Not a valid level

    // Should not change from default
    $component->assertSet('calories', 2000);
});

it('accepts valid calorie level via setCalories', function (): void {
    Livewire::test('pages::usda-daily-servings-calculator')
        ->call('setCalories', 2400)
        ->assertSet('calories', 2400);
});

it('persists calories in url', function (): void {
    Livewire::withQueryParams(['calories' => 2800])
        ->test('pages::usda-daily-servings-calculator')
        ->assertSet('calories', 2800);
});

it('persists low carb mode in url', function (): void {
    Livewire::withQueryParams(['lowCarbMode' => true])
        ->test('pages::usda-daily-servings-calculator')
        ->assertSet('lowCarbMode', true);
});

it('resets to default if invalid calorie in url', function (): void {
    Livewire::withQueryParams(['calories' => 9999])
        ->test('pages::usda-daily-servings-calculator')
        ->assertSet('calories', 2000);
});

it('displays serving examples for each food group', function (): void {
    Livewire::test('pages::usda-daily-servings-calculator')
        ->assertSee('1 serving =')
        ->assertSee('1 cup milk');
});

it('displays diabetic tips in low carb mode', function (): void {
    Livewire::test('pages::usda-daily-servings-calculator')
        ->call('toggleLowCarbMode')
        ->assertSee('Choose Greek yogurt or cottage cheese')
        ->assertSee('Consider reducing portions if monitoring blood sugar');
});

it('loads data from database', function (): void {
    // Verify the seeded data exists
    expect(Content::query()->ofType(ContentType::UsdaDailyServingSize)->count())->toBe(6);
    expect(Content::query()->ofType(ContentType::UsdaSugarLimit)->count())->toBe(9);
});

it('displays food group icons', function (): void {
    Livewire::test('pages::usda-daily-servings-calculator')
        ->assertSeeHtml('🍖') // Protein
        ->assertSeeHtml('🥛') // Dairy
        ->assertSeeHtml('🥗') // Vegetables
        ->assertSeeHtml('🍎') // Fruits
        ->assertSeeHtml('🌾') // Grains
        ->assertSeeHtml('🫒'); // Fats
});

it('displays cta section', function (): void {
    Livewire::test('pages::usda-daily-servings-calculator')
        ->assertSee('Ready for a personalized meal plan?')
        ->assertSee('Create Free Account');
});

it('displays source attribution', function (): void {
    Livewire::test('pages::usda-daily-servings-calculator')
        ->assertSee('Dietary Guidelines for Americans, 2025-2030');
});

it('can be accessed via route', function (): void {
    $this->get('/tools/usda-daily-servings-calculator')
        ->assertStatus(200)
        ->assertSee('USDA 2025-2030 Daily Serving Calculator');
});

it('displays different servings at high calorie levels', function (): void {
    Livewire::test('pages::usda-daily-servings-calculator')
        ->set('calories', 3200)
        ->assertSee('3,200 calories')
        ->assertSee('4-5 oz-eq') // Protein at 3200 cal
        ->assertSee('8 tsp'); // Healthy fats at 3200 cal
});
