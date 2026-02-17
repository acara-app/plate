<?php

declare(strict_types=1);

use App\Enums\ContentType;
use App\Models\Content;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('layouts.mini-app', ['metaDescription' => 'Calculate daily food servings with USDA 2025-2030 guidelines. Adjusts for 1,000-3,200 calories, includes FDA sugar limits & diabetic-friendly options.', 'metaKeywords' => 'USDA dietary guidelines 2030, daily serving calculator, food group servings, calorie intake guide, diabetic meal planning, FDA sugar limits, healthy eating guide'])]
#[Title('USDA 2025-2030 Daily Serving Calculator | Official Dietary Guidelines')]
class extends Component
{
    #[Url]
    public int $calories = 2000;

    #[Url]
    public bool $lowCarbMode = false;

    /** @var array<int> */
    public array $validCalorieLevels = [1000, 1200, 1400, 1600, 1800, 2000, 2200, 2400, 2600, 2800, 3000, 3200];

    public function mount(): void
    {
        // Ensure calories is a valid level
        if (! in_array($this->calories, $this->validCalorieLevels, true)) {
            $this->calories = 2000;
        }
    }

    public function setCalories(int $calories): void
    {
        if (in_array($calories, $this->validCalorieLevels, true)) {
            $this->calories = $calories;
        }
    }

    public function toggleLowCarbMode(): void
    {
        $this->lowCarbMode = ! $this->lowCarbMode;
    }

    /**
     * @return Illuminate\Database\Eloquent\Collection<int, Content>
     */
    #[Computed]
    public function servingSizes(): Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember('usda-daily-serving-sizes', 3600, fn () => Content::query()
            ->ofType(ContentType::UsdaDailyServingSize)
            ->published()
            ->get()
        );
    }

    /**
     * @return Illuminate\Database\Eloquent\Collection<int, Content>
     */
    #[Computed]
    public function sugarLimits(): Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember('usda-sugar-limits', 3600, fn () => Content::query()
            ->ofType(ContentType::UsdaSugarLimit)
            ->published()
            ->get()
        );
    }

    /**
     * @return array<string, array{min: float, max: float, unit: string, examples: array<string>, details: string, icon: string, adjusted: bool, diabeticTip: string|null}>
     */
    #[Computed]
    public function foodGroupData(): array
    {
        $data = [];
        $calorieKey = (string) $this->calories;

        $icons = [
            'Protein Foods' => '🍖',
            'Dairy' => '🥛',
            'Vegetables' => '🥗',
            'Fruits' => '🍎',
            'Whole Grains' => '🌾',
            'Healthy Fats' => '🫒',
        ];

        $diabeticTips = [
            'Dairy' => 'Choose Greek yogurt or cottage cheese for lower blood sugar impact.',
            'Whole Grains' => 'Consider reducing portions if monitoring blood sugar. Try cauliflower rice as a substitute.',
            'Fruits' => 'Pair with protein or fat to slow sugar absorption. Berries are lowest glycemic.',
        ];

        foreach ($this->servingSizes as $serving) {
            /** @var array<string, mixed> $body */
            $body = $serving->body;
            $foodGroup = $body['food_group'] ?? 'Unknown';
            $servingsByCalorie = $body['servings_by_calorie_level'] ?? [];

            if (! isset($servingsByCalorie[$calorieKey])) {
                continue;
            }

            $servingData = $servingsByCalorie[$calorieKey];
            $min = (float) ($servingData['min'] ?? 0);
            $max = (float) ($servingData['max'] ?? 0);
            $adjusted = false;

            // Apply low-carb adjustments
            if ($this->lowCarbMode) {
                if ($foodGroup === 'Whole Grains') {
                    // Reduce grains by 50%
                    $min = round($min * 0.5, 2);
                    $max = round($max * 0.5, 2);
                    $adjusted = true;
                } elseif ($foodGroup === 'Protein Foods' || $foodGroup === 'Vegetables') {
                    // Increase protein and vegetables by 25%
                    $min = round($min * 1.25, 2);
                    $max = round($max * 1.25, 2);
                    $adjusted = true;
                }
            }

            $data[$foodGroup] = [
                'min' => $min,
                'max' => $max,
                'unit' => $this->getUnitForGroup($foodGroup),
                'examples' => $body['serving_size_examples'] ?? [],
                'details' => $body['food_group_details'] ?? '',
                'icon' => $icons[$foodGroup] ?? '🍽️',
                'adjusted' => $adjusted,
                'diabeticTip' => $diabeticTips[$foodGroup] ?? null,
            ];
        }

        // Ensure consistent ordering
        $order = ['Protein Foods', 'Dairy', 'Vegetables', 'Fruits', 'Whole Grains', 'Healthy Fats'];
        $ordered = [];
        foreach ($order as $group) {
            if (isset($data[$group])) {
                $ordered[$group] = $data[$group];
            }
        }

        return $ordered;
    }

    /**
     * @return array<string, array{limit: float, equivalent: string, icon: string}>
     */
    #[Computed]
    public function sugarLimitData(): array
    {
        $data = [];

        $icons = [
            'Dairy product' => '🥛',
            'Grain product' => '🌾',
            'Vegetable product' => '🥗',
            'Fruit product' => '🍎',
            'Game meat' => '🦌',
            'Seafood' => '🐟',
            'Eggs' => '🥚',
            'Beans, peas, and lentils' => '🫘',
            'Nuts, seeds, and soy products' => '🥜',
        ];

        foreach ($this->sugarLimits as $limit) {
            /** @var array<string, mixed> $body */
            $body = $limit->body;
            $foodGroup = $body['food_group'] ?? 'Unknown';

            $data[$foodGroup] = [
                'limit' => (float) ($body['added_sugar_limit_grams'] ?? 0),
                'equivalent' => $body['minimum_equivalent'] ?? '',
                'icon' => $icons[$foodGroup] ?? '🍽️',
            ];
        }

        // Sort by sugar limit (highest first for visual impact)
        uasort($data, fn ($a, $b) => $b['limit'] <=> $a['limit']);

        return $data;
    }

    /**
     * Get the progress percentage for visualization (based on max 8 servings scale)
     */
    public function getProgressPercentage(float $value): int
    {
        $maxScale = 8;

        return (int) min(100, ($value / $maxScale) * 100);
    }

    private function getUnitForGroup(string $group): string
    {
        return match ($group) {
            'Protein Foods' => 'oz-eq',
            'Dairy' => 'cup-eq',
            'Vegetables' => 'cup-eq',
            'Fruits' => 'cup-eq',
            'Whole Grains' => 'oz-eq',
            'Healthy Fats' => 'tsp',
            default => 'servings',
        };
    }
};
?>

<x-slot:jsonLd>
    <x-json-ld.usda-servings-calculator />
</x-slot:jsonLd>

<div
    class="relative flex min-h-screen flex-col items-center overflow-hidden bg-linear-to-br from-slate-50 via-white to-emerald-50 p-4 text-slate-900 lg:justify-center lg:p-8 dark:from-slate-950 dark:via-slate-900 dark:to-emerald-950 dark:text-slate-50"
>
    {{-- Animated background elements --}}
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 overflow-hidden">
        <div class="absolute -left-4 top-0 h-72 w-72 animate-pulse rounded-full bg-emerald-300/20 blur-3xl dark:bg-emerald-500/10"></div>
        <div class="absolute -right-4 bottom-0 h-96 w-96 animate-pulse rounded-full bg-teal-300/20 blur-3xl dark:bg-teal-500/10"></div>
    </div>

    {{-- Header --}}
    <header class="relative z-10 mb-6 w-full max-w-2xl lg:mb-8">
        <nav class="flex items-center justify-center">
            <a href="/" class="flex items-center gap-2 text-xl font-bold text-slate-900 transition-opacity hover:opacity-80 dark:text-white">
                <span class="text-2xl" role="img" aria-label="strawberry">🍓</span>
                Acara Plate
            </a>
        </nav>
    </header>

    {{-- Main Content --}}
    <main class="relative z-10 w-full max-w-2xl space-y-6">

        {{-- Header Card --}}
        <div class="rounded-3xl bg-white p-6 shadow-xl shadow-emerald-500/10 dark:bg-slate-800 dark:shadow-emerald-900/20">
            {{-- Title Section --}}
            <div class="mb-6 text-center">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-2xl dark:bg-emerald-900/50">🥗</div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">USDA 2025-2030 Daily Serving Calculator</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">How much should you eat? Find out based on your calorie needs.</p>
            </div>

            {{-- Calorie Slider Section --}}
            <div class="mb-6">
                <label for="calorie-slider" class="mb-2 block text-sm font-semibold text-slate-700 dark:text-slate-300">
                    Daily Calorie Intake: <span class="text-emerald-600 dark:text-emerald-400">{{ number_format($calories) }} calories</span>
                </label>
                <div class="relative">
                    <input
                        type="range"
                        id="calorie-slider"
                        min="1000"
                        max="3200"
                        step="200"
                        wire:model.live="calories"
                        class="h-3 w-full cursor-pointer appearance-none rounded-full bg-slate-200 accent-emerald-600 dark:bg-slate-700"
                    >
                    <div class="mt-2 flex justify-between text-xs text-slate-400">
                        <span>1,000</span>
                        <span>2,000</span>
                        <span>3,200</span>
                    </div>
                </div>
            </div>

            {{-- Mode Toggle --}}
            <div class="flex items-center justify-between rounded-xl bg-slate-50 p-4 dark:bg-slate-700/50">
                <div>
                    <p class="font-semibold text-slate-900 dark:text-white">Dietary Mode</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        @if ($lowCarbMode)
                            Low-Carb Adjustment: Reduced grains, increased protein & veggies
                        @else
                            Standard USDA 2030 Guidelines
                        @endif
                    </p>
                </div>
                <button
                    type="button"
                    wire:click="toggleLowCarbMode"
                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:outline-none {{ $lowCarbMode ? 'bg-emerald-600' : 'bg-slate-300 dark:bg-slate-600' }}"
                    role="switch"
                    aria-checked="{{ $lowCarbMode ? 'true' : 'false' }}"
                    aria-label="Toggle low-carb mode"
                >
                    <span
                        class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $lowCarbMode ? 'translate-x-5' : 'translate-x-0' }}"
                    ></span>
                </button>
            </div>
        </div>

        {{-- Bento Box Grid --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" wire:loading.class="opacity-50" wire:target="calories, lowCarbMode">
            @foreach ($this->foodGroupData as $groupName => $group)
                <div wire:key="food-group-{{ Str::slug($groupName) }}" class="relative overflow-hidden rounded-2xl bg-white p-5 shadow-lg transition-all hover:shadow-xl dark:bg-slate-800 {{ $group['adjusted'] ? 'ring-2 ring-emerald-500' : '' }}">
                    @if ($group['adjusted'])
                        <div class="absolute right-2 top-2">
                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400">
                                Adjusted
                            </span>
                        </div>
                    @endif

                    {{-- Icon & Title --}}
                    <div class="mb-3 flex items-center gap-3">
                        <span class="text-3xl">{{ $group['icon'] }}</span>
                        <div>
                            <h3 class="font-bold text-slate-900 dark:text-white">{{ $groupName === 'Whole Grains' ? 'Carbs & Grains' : $groupName }}</h3>
                            <p class="text-lg font-semibold text-emerald-600 dark:text-emerald-400">
                                @if ($group['min'] === $group['max'])
                                    {{ $group['min'] }} {{ $group['unit'] }}
                                @else
                                    {{ $group['min'] }}-{{ $group['max'] }} {{ $group['unit'] }}
                                @endif
                            </p>
                        </div>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="mb-3">
                        <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                            <div
                                class="h-full rounded-full bg-emerald-500 transition-all duration-500"
                                style="width: {{ $this->getProgressPercentage($group['max']) }}%"
                            ></div>
                        </div>
                    </div>

                    {{-- Serving Examples --}}
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        <span class="font-medium">1 {{ $group['unit'] }} =</span>
                        {{ implode(', ', array_slice($group['examples'], 0, 2)) }}
                    </p>

                    {{-- Diabetic Tip --}}
                    @if ($group['diabeticTip'] && $lowCarbMode)
                        <div class="mt-3 rounded-lg bg-amber-50 p-2 text-xs text-amber-800 dark:bg-amber-900/30 dark:text-amber-200">
                            <span class="font-semibold">Tip:</span> {{ $group['diabeticTip'] }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Sugar Limits Section --}}
        <div class="rounded-3xl bg-white p-6 shadow-xl shadow-red-500/5 dark:bg-slate-800">
            <div class="mb-4 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 text-xl dark:bg-red-900/50">🚨</div>
                <div>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">FDA Added Sugar Limits</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Maximum added sugar for "Healthy" food claims</p>
                </div>
            </div>

            <div class="space-y-3" wire:loading.class="opacity-50" wire:target="calories">
                @foreach ($this->sugarLimitData as $category => $limit)
                    <div wire:key="sugar-limit-{{ Str::slug($category) }}" class="rounded-xl bg-slate-50 p-3 dark:bg-slate-700/50">
                        <div class="mb-2 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-lg">{{ $limit['icon'] }}</span>
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ $category }}</span>
                            </div>
                            <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700 dark:bg-red-900/50 dark:text-red-400">
                                Max {{ $limit['limit'] }}g
                            </span>
                        </div>
                        {{-- Sugar Gauge --}}
                        <div class="relative h-2 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-600">
                            {{-- Gradient background --}}
                            <div class="absolute inset-0 bg-linear-to-r from-emerald-400 via-amber-400 to-red-400"></div>
                            {{-- Red line marker --}}
                            <div
                                class="absolute top-0 h-full w-0.5 bg-red-600"
                                style="left: {{ min(100, ($limit['limit'] / 6) * 100) }}%"
                            ></div>
                        </div>
                        <p class="mt-1 text-xs text-slate-400">Per {{ $limit['equivalent'] }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Sugar Tip --}}
            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-900/20">
                <p class="text-sm text-amber-800 dark:text-amber-200">
                    <span class="font-bold">How to use:</span> Check the nutrition label. Does your yogurt have more than 2.5g added sugar per ⅔ cup? If yes, it exceeds the 2030 guideline for "healthy" foods.
                </p>
            </div>
        </div>

        {{-- Diabetic Disclaimer --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
            <div class="flex items-start gap-3">
                <span class="text-2xl">⚠️</span>
                <div>
                    <h3 class="font-bold text-slate-900 dark:text-white">Important for Diabetics</h3>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                        The USDA recommends <strong>{{ $this->foodGroupData['Whole Grains']['min'] ?? 2 }}-{{ $this->foodGroupData['Whole Grains']['max'] ?? 4 }} servings of grains</strong> for a {{ number_format($calories) }}-calorie diet.
                        For Type 2 diabetics, this may cause blood sugar spikes.
                    </p>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                        @if ($lowCarbMode)
                            <span class="font-semibold text-emerald-600 dark:text-emerald-400">Low-Carb Mode is ON</span> - Grain servings are reduced by 50%.
                        @else
                            <button wire:click="toggleLowCarbMode" class="font-semibold text-emerald-600 underline hover:text-emerald-700 dark:text-emerald-400">
                                Enable Low-Carb Mode
                            </button> to reduce grain servings and increase protein/vegetables.
                        @endif
                    </p>
                </div>
            </div>
        </div>

        {{-- CTA Section --}}
        <div class="rounded-2xl bg-emerald-600 p-6 text-center text-white shadow-lg">
            <h3 class="text-xl font-bold">Ready for a personalized meal plan?</h3>
            <p class="mt-1 text-sm text-emerald-100">Get AI-generated diabetic-friendly meals based on these guidelines.</p>
            <a
                href="{{ route('register') }}"
                class="mt-4 inline-block rounded-xl bg-white px-6 py-3 font-bold text-emerald-600 transition-transform hover:scale-105"
            >
                Create Free Account
            </a>
        </div>

        {{-- FAQ Section --}}
        <section class="rounded-3xl bg-white p-6 shadow-xl dark:bg-slate-800" aria-labelledby="faq-heading">
            <h2 id="faq-heading" class="mb-4 text-lg font-bold text-slate-900 dark:text-white">
                Frequently Asked Questions
            </h2>

            <div class="space-y-3" x-data="{ openFaq: null }">
                {{-- FAQ 1 --}}
                <div class="overflow-hidden rounded-xl bg-slate-50 dark:bg-slate-700/50">
                    <button
                        type="button"
                        @click="openFaq = openFaq === 1 ? null : 1"
                        class="flex w-full items-center justify-between p-4 text-left font-medium text-slate-900 transition-colors hover:bg-slate-100 dark:text-white dark:hover:bg-slate-700"
                        :aria-expanded="openFaq === 1 ? 'true' : 'false'"
                    >
                        <span>What are the USDA 2025-2030 Dietary Guidelines?</span>
                        <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform" :class="{ 'rotate-180': openFaq === 1 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="openFaq === 1" x-collapse class="border-t border-slate-200 px-4 pb-4 pt-2 text-sm text-slate-600 dark:border-slate-600 dark:text-slate-300">
                        <p>The Dietary Guidelines for Americans 2025-2030 provide science-based advice on what to eat and drink to promote health, reduce chronic disease risk, and meet nutrient needs. They're updated every 5 years by the USDA and HHS.</p>
                    </div>
                </div>

                {{-- FAQ 2 --}}
                <div class="overflow-hidden rounded-xl bg-slate-50 dark:bg-slate-700/50">
                    <button
                        type="button"
                        @click="openFaq = openFaq === 2 ? null : 2"
                        class="flex w-full items-center justify-between p-4 text-left font-medium text-slate-900 transition-colors hover:bg-slate-100 dark:text-white dark:hover:bg-slate-700"
                        :aria-expanded="openFaq === 2 ? 'true' : 'false'"
                    >
                        <span>How do I know how many calories I need?</span>
                        <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform" :class="{ 'rotate-180': openFaq === 2 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="openFaq === 2" x-collapse class="border-t border-slate-200 px-4 pb-4 pt-2 text-sm text-slate-600 dark:border-slate-600 dark:text-slate-300">
                        <p>Calorie needs depend on age, sex, and activity level. Generally: sedentary adults need 1,600-2,000 calories; moderately active need 1,800-2,400; and very active need 2,000-3,200. Consult a healthcare provider for personalized advice.</p>
                    </div>
                </div>

                {{-- FAQ 3 --}}
                <div class="overflow-hidden rounded-xl bg-slate-50 dark:bg-slate-700/50">
                    <button
                        type="button"
                        @click="openFaq = openFaq === 3 ? null : 3"
                        class="flex w-full items-center justify-between p-4 text-left font-medium text-slate-900 transition-colors hover:bg-slate-100 dark:text-white dark:hover:bg-slate-700"
                        :aria-expanded="openFaq === 3 ? 'true' : 'false'"
                    >
                        <span>What is the Low-Carb Diabetic mode?</span>
                        <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform" :class="{ 'rotate-180': openFaq === 3 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="openFaq === 3" x-collapse class="border-t border-slate-200 px-4 pb-4 pt-2 text-sm text-slate-600 dark:border-slate-600 dark:text-slate-300">
                        <p>The Low-Carb mode adjusts the standard USDA guidelines for people managing blood sugar. It reduces grain servings by 50% and increases protein and vegetable servings by 25%. This is not medical advice - consult your doctor.</p>
                    </div>
                </div>

                {{-- FAQ 4 --}}
                <div class="overflow-hidden rounded-xl bg-slate-50 dark:bg-slate-700/50">
                    <button
                        type="button"
                        @click="openFaq = openFaq === 4 ? null : 4"
                        class="flex w-full items-center justify-between p-4 text-left font-medium text-slate-900 transition-colors hover:bg-slate-100 dark:text-white dark:hover:bg-slate-700"
                        :aria-expanded="openFaq === 4 ? 'true' : 'false'"
                    >
                        <span>What do the FDA sugar limits mean?</span>
                        <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform" :class="{ 'rotate-180': openFaq === 4 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="openFaq === 4" x-collapse class="border-t border-slate-200 px-4 pb-4 pt-2 text-sm text-slate-600 dark:border-slate-600 dark:text-slate-300">
                        <p>The FDA has set maximum added sugar limits for foods to qualify for the "Healthy" label. For example, dairy products must have no more than 2.5g added sugar per ⅔ cup. This helps you identify truly healthy options at the grocery store.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="relative z-10 mt-12 mb-8 w-full">
            <h2 class="mb-4 text-center text-lg font-bold text-slate-900 dark:text-white">
                Explore More Free Tools
            </h2>
            <a href="{{ route('tools.index') }}" class="group flex flex-col items-center rounded-xl bg-white p-6 text-center shadow-sm transition-all hover:shadow-md dark:bg-slate-800">
                <span class="mb-2 text-3xl">🛠️</span>
                <h3 class="font-bold text-slate-900 dark:text-white">View All Free Tools</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Discover free health trackers, calculators, and nutrition tools</p>
            </a>
        </section>

    </main>

    <x-footer />
</div>
