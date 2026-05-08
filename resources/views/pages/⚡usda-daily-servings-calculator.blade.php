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
        return $this->rememberCollection('usda-daily-serving-sizes', ContentType::UsdaDailyServingSize);
    }

    /**
     * @return Illuminate\Database\Eloquent\Collection<int, Content>
     */
    #[Computed]
    public function sugarLimits(): Illuminate\Database\Eloquent\Collection
    {
        return $this->rememberCollection('usda-sugar-limits', ContentType::UsdaSugarLimit);
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

            if ($this->lowCarbMode) {
                if ($foodGroup === 'Whole Grains') {
                    $min = round($min * 0.5, 2);
                    $max = round($max * 0.5, 2);
                    $adjusted = true;
                } elseif ($foodGroup === 'Protein Foods' || $foodGroup === 'Vegetables') {
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

        uasort($data, fn ($a, $b) => $b['limit'] <=> $a['limit']);

        return $data;
    }

    public function getProgressPercentage(float $value): int
    {
        $maxScale = 8;

        return (int) min(100, ($value / $maxScale) * 100);
    }

    private function rememberCollection(string $key, ContentType $type): Illuminate\Database\Eloquent\Collection
    {
        $result = Cache::remember($key, 3600, fn () => Content::query()
            ->ofType($type)
            ->published()
            ->get()
        );

        if (! $result instanceof Illuminate\Database\Eloquent\Collection) {
            Cache::forget($key);

            return Content::query()->ofType($type)->published()->get();
        }

        return $result;
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

<div class="min-h-screen bg-[#F2EBDD] text-[#1A1814]">
    <x-tools-header theme="cream" />

    <div class="px-4 py-8 md:py-12">
        {{-- Editorial breadcrumbs --}}
        <nav aria-label="Breadcrumb" class="mx-auto flex max-w-7xl items-center gap-2 font-mono text-[11px] uppercase tracking-[0.14em] text-[#6E665C] lg:px-8">
            <a href="/" aria-label="Home" class="inline-flex items-center transition hover:text-[#1A1814]">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="size-3.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
            </a>
            <span aria-hidden="true">›</span>
            <a href="{{ route('tools.index') }}" class="transition hover:text-[#1A1814]">Tools</a>
            <span aria-hidden="true">›</span>
            <span aria-current="page" class="text-[#1A1814]">USDA Servings Calculator</span>
        </nav>

        {{-- Hero --}}
        <header class="speakable-intro mx-auto mt-6 max-w-7xl lg:px-8">
            <div class="inline-flex items-center gap-2 border border-[#D9CFBC] bg-[#EBE2D0] px-3 py-1.5">
                <span class="size-1.5 rounded-full bg-[#C4623A] shadow-[0_0_0_3px_rgba(196,98,58,0.18)]" aria-hidden="true"></span>
                <span class="font-mono text-[10px] uppercase tracking-[0.18em] text-[#3D3833]">2025-2030 Dietary Guidelines</span>
            </div>

            <h1 class="mt-5 max-w-5xl text-balance font-bold text-[clamp(40px,5vw,68px)] leading-[1.02] tracking-[-0.02em] text-[#1A1814]">
                USDA 2025-2030 Daily Serving Calculator
            </h1>
            <p class="mt-4 max-w-2xl text-base leading-relaxed text-[#3D3833] sm:text-lg">
                How much should you eat? Slide your daily calories — we map it to USDA serving targets across six food groups and FDA added-sugar caps.
            </p>
        </header>

        {{-- Tool: 2-col with sticky controls --}}
        <main class="mx-auto mt-10 grid max-w-7xl gap-6 lg:grid-cols-[0.92fr_1.08fr] lg:gap-10 lg:px-8">
            {{-- Controls (left, sticky on desktop) --}}
            <section class="lg:sticky lg:top-28 lg:self-start">
                <div class="border border-[#D9CFBC] bg-[#EBE2D0] p-6 sm:p-8">
                    <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">Daily intake</p>

                    <div class="mt-4 flex items-end gap-3">
                        <span class="font-bold text-[clamp(48px,6vw,80px)] leading-[1] tracking-[-0.03em] text-[#1A1814]">
                            {{ number_format($calories) }}
                        </span>
                        <span class="mb-2 font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">
                            kcal / day
                        </span>
                    </div>

                    <div class="mt-6 border-t border-[#D9CFBC] pt-6">
                        <label for="calorie-slider" class="sr-only">Daily calorie intake</label>
                        <input
                            type="range"
                            id="calorie-slider"
                            min="1000"
                            max="3200"
                            step="200"
                            wire:model.live="calories"
                            class="h-1.5 w-full cursor-pointer appearance-none rounded-none bg-[#D9CFBC] accent-[#C4623A] [&::-moz-range-thumb]:size-5 [&::-moz-range-thumb]:cursor-pointer [&::-moz-range-thumb]:appearance-none [&::-moz-range-thumb]:rounded-none [&::-moz-range-thumb]:border-2 [&::-moz-range-thumb]:border-[#1A1814] [&::-moz-range-thumb]:bg-[#C4623A] [&::-webkit-slider-thumb]:size-5 [&::-webkit-slider-thumb]:cursor-pointer [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:rounded-none [&::-webkit-slider-thumb]:border-2 [&::-webkit-slider-thumb]:border-[#1A1814] [&::-webkit-slider-thumb]:bg-[#C4623A]"
                            aria-label="Daily calorie intake"
                        >
                        <div class="mt-2 flex justify-between font-mono text-[10px] uppercase tracking-[0.18em] text-[#6E665C]">
                            <span>1,000</span>
                            <span>2,000</span>
                            <span>3,200</span>
                        </div>
                    </div>

                    <div class="mt-6 border-t border-[#D9CFBC] pt-6">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">Dietary mode</p>
                                <p class="mt-1 font-bold text-base text-[#1A1814]">
                                    {{ $lowCarbMode ? 'Low-Carb adjustment' : 'Standard USDA' }}
                                </p>
                                <p class="mt-1 text-xs leading-relaxed text-[#3D3833]">
                                    @if ($lowCarbMode)
                                        Grains reduced 50%, protein &amp; veggies +25%.
                                    @else
                                        2025-2030 guidelines, unmodified.
                                    @endif
                                </p>
                            </div>
                            <button
                                type="button"
                                wire:click="toggleLowCarbMode"
                                role="switch"
                                aria-checked="{{ $lowCarbMode ? 'true' : 'false' }}"
                                aria-label="Toggle Low-Carb mode"
                                class="relative inline-flex h-7 w-12 shrink-0 cursor-pointer items-center border border-[#1A1814] transition-colors duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#C4623A] focus-visible:ring-offset-2 focus-visible:ring-offset-[#EBE2D0] {{ $lowCarbMode ? 'bg-[#1A1814]' : 'bg-[#F2EBDD]' }}"
                            >
                                <span class="absolute size-5 transition-transform duration-200 {{ $lowCarbMode ? 'translate-x-[22px] bg-[#F2EBDD]' : 'translate-x-0.5 bg-[#1A1814]' }}"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Results (right) --}}
            <section class="space-y-12" wire:loading.class.delay="opacity-50" wire:target="calories, lowCarbMode">
                {{-- Food groups --}}
                <div>
                    <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">Food groups</p>
                    <h2 class="mt-3 font-bold text-2xl leading-tight tracking-[-0.02em] text-[#1A1814] sm:text-3xl">
                        Your daily plate
                    </h2>
                    <p class="mt-3 max-w-2xl text-sm leading-relaxed text-[#3D3833]">
                        Across {{ number_format($calories) }} kcal, the USDA spreads servings between protein, dairy, vegetables, fruits, grains, and fats.
                    </p>

                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        @foreach ($this->foodGroupData as $groupName => $group)
                            <article wire:key="food-group-{{ Str::slug($groupName) }}" class="border bg-[#F2EBDD] p-5 sm:p-6 {{ $group['adjusted'] ? 'border-[#C4623A]' : 'border-[#D9CFBC]' }}">
                                <div class="flex items-baseline justify-between gap-3">
                                    <div class="flex items-baseline gap-3">
                                        <span class="text-xl leading-none" aria-hidden="true">{{ $group['icon'] }}</span>
                                        <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">
                                            {{ $groupName === 'Whole Grains' ? 'Carbs & Grains' : $groupName }}
                                        </p>
                                    </div>
                                    @if ($group['adjusted'])
                                        <span class="border border-[#C4623A] px-1.5 py-0.5 font-mono text-[9px] uppercase tracking-[0.16em] text-[#C4623A]">Adjusted</span>
                                    @endif
                                </div>

                                <p class="mt-3 flex items-baseline gap-2">
                                    <span class="font-bold text-3xl leading-none tracking-[-0.02em] text-[#1A1814]">
                                        @if ($group['min'] === $group['max'])
                                            {{ $group['min'] }}
                                        @else
                                            {{ $group['min'] }}–{{ $group['max'] }}
                                        @endif
                                    </span>
                                    <span class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">
                                        {{ $group['unit'] }} / day
                                    </span>
                                </p>

                                <div class="mt-3 h-1 w-full overflow-hidden bg-[#D9CFBC]">
                                    <div
                                        class="h-full {{ $group['adjusted'] ? 'bg-[#C4623A]' : 'bg-[#1A1814]' }} transition-[width] duration-500"
                                        style="width: {{ $this->getProgressPercentage($group['max']) }}%"
                                    ></div>
                                </div>

                                <p class="mt-4 text-sm leading-relaxed text-[#3D3833]">
                                    <span class="font-bold text-[#1A1814]">1 {{ $group['unit'] }}</span> = {{ implode(', ', array_slice($group['examples'], 0, 2)) }}
                                </p>

                                @if ($group['diabeticTip'] && $lowCarbMode)
                                    <div class="mt-4 border-l-2 border-[#C4623A] pl-3">
                                        <p class="font-mono text-[10px] uppercase tracking-[0.18em] text-[#C4623A]">Tip</p>
                                        <p class="mt-1 text-xs leading-relaxed text-[#3D3833]">{{ $group['diabeticTip'] }}</p>
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>

                    {{-- Diabetic note --}}
                    <aside class="speakable-how-it-works mt-6 grid gap-4 border-t border-[#D9CFBC] pt-6 sm:grid-cols-[auto_1fr] sm:gap-6">
                        <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#C4623A]">For Type 2 diabetics</p>
                        <div>
                            <p class="text-sm leading-relaxed text-[#3D3833]">
                                The USDA recommends <strong class="font-bold text-[#1A1814]">{{ $this->foodGroupData['Whole Grains']['min'] ?? 2 }}–{{ $this->foodGroupData['Whole Grains']['max'] ?? 4 }} servings of grains</strong> for a {{ number_format($calories) }}-calorie diet. For people managing blood sugar, that range can drive spikes.
                            </p>
                            <p class="mt-2 text-sm leading-relaxed">
                                @if ($lowCarbMode)
                                    <span class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#C4623A]">Low-Carb mode is on</span>
                                    <span class="text-[#3D3833]">— grain servings reduced 50%, protein &amp; veggies bumped 25%.</span>
                                @else
                                    <button wire:click="toggleLowCarbMode" class="font-bold text-[#C4623A] underline decoration-[#C4623A]/40 underline-offset-4 transition hover:decoration-[#C4623A]">
                                        Enable Low-Carb mode →
                                    </button>
                                @endif
                            </p>
                        </div>
                    </aside>
                </div>

                {{-- FDA Sugar Limits --}}
                <div>
                    <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">FDA added-sugar caps</p>
                    <h2 class="mt-3 font-bold text-2xl leading-tight tracking-[-0.02em] text-[#1A1814] sm:text-3xl">
                        The "Healthy" label thresholds.
                    </h2>
                    <p class="mt-3 max-w-2xl text-sm leading-relaxed text-[#3D3833]">
                        The maximum added sugar a food can carry to claim "Healthy" on its label, sorted by how much the rule allows.
                    </p>

                    <dl class="mt-6 border-t border-[#D9CFBC]" wire:loading.class.delay="opacity-50" wire:target="calories">
                        @foreach ($this->sugarLimitData as $category => $limit)
                            <div wire:key="sugar-limit-{{ Str::slug($category) }}" class="grid grid-cols-[auto_1fr_auto] items-center gap-4 border-b border-[#D9CFBC] py-4">
                                <span class="text-xl leading-none" aria-hidden="true">{{ $limit['icon'] }}</span>
                                <dt>
                                    <p class="font-bold text-sm leading-tight text-[#1A1814]">{{ $category }}</p>
                                    <p class="mt-0.5 font-mono text-[10px] uppercase tracking-[0.18em] text-[#6E665C]">
                                        per {{ $limit['equivalent'] }}
                                    </p>
                                </dt>
                                <dd class="border border-[#C4623A]/60 px-2.5 py-1 font-mono text-[11px] uppercase tracking-[0.16em] text-[#1A1814]">
                                    Max {{ $limit['limit'] }}g
                                </dd>
                            </div>
                        @endforeach
                    </dl>

                    <blockquote class="mt-6 border-l-4 border-[#C4623A] py-2 pl-6">
                        <p class="font-mono text-[10px] uppercase tracking-[0.18em] text-[#C4623A]">How to use</p>
                        <p class="mt-2 max-w-2xl text-sm leading-relaxed text-[#1A1814]">
                            Check the nutrition label. Does your yogurt have more than 2.5&nbsp;g added sugar per ⅔&nbsp;cup? If yes, it exceeds the 2030 guideline for "healthy" foods.
                        </p>
                    </blockquote>
                </div>
            </section>
        </main>

        {{-- Field manual / about --}}
        <section class="mx-auto mt-24 max-w-7xl lg:px-8">
            <div class="grid gap-6 border-t border-[#D9CFBC] pt-10 sm:grid-cols-[1fr_2fr] sm:gap-12">
                <div>
                    <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">Field manual</p>
                </div>
                <p class="max-w-3xl text-base leading-relaxed text-[#3D3833] sm:text-lg">
                    The <strong class="font-bold text-[#1A1814]">USDA 2025-2030 Dietary Guidelines</strong> are the official, science-based recommendations Americans see referenced on packaging, school menus, and clinic posters. Updated every five years by the USDA and HHS, they translate the latest nutrition research into daily-serving targets across calorie levels — this calculator just maps those tables to your number.
                </p>
            </div>
        </section>

        {{-- CTA: Personalized meal plan --}}
        <section class="mx-auto mt-16 max-w-7xl lg:px-8">
            <article class="border border-[#1A1814] bg-[#1A1814] p-8 text-[#F2EBDD] sm:p-12">
                <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#C4623A]">From plate to plan</p>
                <div class="mt-3 grid gap-8 sm:grid-cols-[2fr_1fr] sm:items-center">
                    <div>
                        <h2 class="font-bold text-2xl leading-tight tracking-[-0.02em] sm:text-3xl">
                            Ready for a personalized meal plan?
                        </h2>
                        <p class="mt-3 max-w-xl text-sm leading-relaxed text-[#F2EBDD]/80 sm:text-base">
                            Get AI-generated, diabetic-friendly meals built around these guidelines, your goals, and what you actually like to eat.
                        </p>
                    </div>
                    <a
                        href="{{ route('register') }}"
                        class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-none bg-[#C4623A] px-6 text-base font-semibold text-[#F2EBDD] transition hover:bg-[#A04A28]"
                    >
                        Create free account
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </a>
                </div>
            </article>
        </section>

        {{-- FAQ --}}
        <section class="mx-auto mt-24 max-w-7xl lg:px-8" aria-labelledby="faq-heading">
            <div class="grid gap-12 sm:grid-cols-[1fr_2fr]">
                <div>
                    <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">FAQ</p>
                    <h2 id="faq-heading" class="mt-4 font-bold text-[clamp(28px,3.4vw,44px)] leading-[1.05] tracking-[-0.02em] text-[#1A1814]">
                        Frequently Asked Questions
                    </h2>
                    <p class="mt-3 max-w-sm text-sm leading-relaxed text-[#3D3833]">
                        Quick context for using the calculator as a planning aid — not a replacement for your dietitian.
                    </p>
                </div>

                <div x-data="{ openFaq: 1 }">
                    @php
                    $faqs = [
                        [
                            'q' => 'What are the USDA 2025-2030 Dietary Guidelines?',
                            'a' => "The Dietary Guidelines for Americans 2025-2030 provide science-based advice on what to eat and drink to promote health, reduce chronic disease risk, and meet nutrient needs. They're updated every 5 years by the USDA and HHS.",
                        ],
                        [
                            'q' => 'How do I know how many calories I need?',
                            'a' => 'Calorie needs depend on age, sex, and activity level. Generally: sedentary adults need 1,600–2,000 calories; moderately active need 1,800–2,400; and very active need 2,000–3,200. Consult a healthcare provider for personalized advice.',
                        ],
                        [
                            'q' => 'What is the Low-Carb Diabetic mode?',
                            'a' => 'The Low-Carb mode adjusts the standard USDA guidelines for people managing blood sugar. It reduces grain servings by 50% and increases protein and vegetable servings by 25%. This is not medical advice — consult your doctor.',
                        ],
                        [
                            'q' => 'What do the FDA sugar limits mean?',
                            'a' => 'The FDA has set maximum added-sugar limits for foods to qualify for the "Healthy" label. For example, dairy products must have no more than 2.5g added sugar per ⅔ cup. This helps you identify truly healthy options at the grocery store.',
                        ],
                    ];
                    @endphp

                    @foreach ($faqs as $index => $faq)
                        @php $position = $index + 1; @endphp
                        <div class="border-t {{ $loop->first ? 'border-[#1A1814]' : 'border-[#D9CFBC]' }} {{ $loop->last ? 'border-b border-[#D9CFBC]' : '' }}">
                            <button
                                type="button"
                                @click="openFaq = openFaq === {{ $position }} ? null : {{ $position }}"
                                :aria-expanded="openFaq === {{ $position }} ? 'true' : 'false'"
                                class="flex w-full items-baseline justify-between gap-4 py-5 text-left transition hover:text-[#1A1814]"
                            >
                                <div class="flex items-baseline gap-4">
                                    <span class="font-mono text-[11px] tracking-[0.14em] text-[#6E665C]" aria-hidden="true">
                                        {{ str_pad((string) $position, 2, '0', STR_PAD_LEFT) }}
                                    </span>
                                    <span class="font-bold text-lg leading-tight tracking-[-0.01em] text-[#1A1814] sm:text-xl">
                                        {{ $faq['q'] }}
                                    </span>
                                </div>
                                <svg
                                    class="mt-1 size-5 shrink-0 text-[#C4623A] transition-transform duration-200"
                                    :class="{ 'rotate-45': openFaq === {{ $position }} }"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    aria-hidden="true"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                            </button>
                            <div x-show="openFaq === {{ $position }}" x-collapse class="overflow-hidden">
                                <p class="mb-6 max-w-prose pl-10 text-sm leading-relaxed text-[#3D3833]">
                                    {{ $faq['a'] }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- More tools strip --}}
        <section class="mx-auto mt-24 max-w-7xl lg:px-8">
            <a
                href="{{ route('tools.index') }}"
                class="group flex flex-col gap-4 border-t border-[#1A1814] pt-8 sm:flex-row sm:items-baseline sm:justify-between"
            >
                <div>
                    <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">Explore More Free Tools</p>
                    <h2 class="mt-3 font-bold text-2xl leading-tight tracking-[-0.01em] text-[#1A1814] transition-colors group-hover:text-[#C4623A]">
                        View all free tools →
                    </h2>
                </div>
                <span class="font-mono text-[11px] uppercase tracking-[0.16em] text-[#6E665C]">
                    Calculators · Trackers · Planners
                </span>
            </a>
        </section>
    </div>

    <x-footer class="bg-[#F2EBDD]! border-[#D9CFBC]!" />
</div>
