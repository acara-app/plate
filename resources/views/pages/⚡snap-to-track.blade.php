<?php

declare(strict_types=1);

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.mini-app', ['metaDescription' => "Analyze your meal's calories and macros instantly by snapping a photo. AI food scanner for easy diabetes & nutrition tracking. Sign up to start.", 'metaKeywords' => 'food photo calorie counter, snap to track calories, AI food recognition, meal photo analyzer, instant macro breakdown, calorie tracking app, food image analysis, nutrition scanner'])]
#[Title('AI Food Photo Analyzer | Track Calories & Macros Instantly')]
class extends Component
{
    public bool $demoActive = false;

    public bool $demoAnalyzing = false;

    public bool $demoComplete = false;

    /** @var array{items: array<int, array{name: string, calories: float, protein: float, carbs: float, fat: float, portion: string}>, totalCalories: float, totalProtein: float, totalCarbs: float, totalFat: float, confidence: float} */
    public array $demoResult = [
        'items' => [
            ['name' => 'Grilled Chicken Breast', 'calories' => 165, 'protein' => 31.0, 'carbs' => 0.0, 'fat' => 3.6, 'portion' => '~120g'],
            ['name' => 'Steamed Brown Rice', 'calories' => 216, 'protein' => 4.5, 'carbs' => 45.0, 'fat' => 1.8, 'portion' => '~1 cup'],
            ['name' => 'Mixed Green Salad', 'calories' => 35, 'protein' => 2.1, 'carbs' => 6.5, 'fat' => 0.4, 'portion' => '~1 cup'],
        ],
        'totalCalories' => 416,
        'totalProtein' => 37.6,
        'totalCarbs' => 51.5,
        'totalFat' => 5.8,
        'confidence' => 92,
    ];

    public function startDemo(): void
    {
        $this->demoActive = true;
        $this->demoAnalyzing = true;
        $this->demoComplete = false;
    }

    public function showDemoResults(): void
    {
        $this->demoAnalyzing = false;
        $this->demoComplete = true;
    }

    public function resetDemo(): void
    {
        $this->demoActive = false;
        $this->demoAnalyzing = false;
        $this->demoComplete = false;
    }
};
?>

<x-slot:jsonLd>
    <x-json-ld.snap-to-track />
</x-slot:jsonLd>

<div
    class="relative flex min-h-screen flex-col items-center overflow-hidden bg-linear-to-br from-slate-50 via-white to-blue-50 p-4 text-slate-900 lg:justify-center lg:p-8 dark:from-slate-950 dark:via-slate-900 dark:to-blue-950 dark:text-slate-50"
>
    {{-- Animated background elements --}}
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 overflow-hidden">
        <div class="absolute -left-4 top-0 h-72 w-72 animate-pulse rounded-full bg-blue-300/20 blur-3xl dark:bg-blue-500/10"></div>
        <div class="absolute -right-4 bottom-0 h-96 w-96 animate-pulse rounded-full bg-sky-300/20 blur-3xl dark:bg-sky-500/10"></div>
    </div>

    {{-- Header --}}
    <header class="relative z-10 mb-6 w-full max-w-md lg:mb-8">
        <nav class="flex items-center justify-center">
            <a href="/" class="flex items-center gap-2 text-xl font-bold text-slate-900 transition-opacity hover:opacity-80 dark:text-white">
                <span class="text-2xl" role="img" aria-label="strawberry">🍓</span>
                Acara Plate
            </a>
        </nav>
    </header>

    {{-- Main Card --}}
    <main class="relative z-10 w-full max-w-md space-y-6 rounded-3xl bg-white p-6 shadow-xl shadow-blue-500/10 dark:bg-slate-800 dark:shadow-blue-900/20">

        {{-- Header Section --}}
        <div class="text-center speakable-intro">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-2xl dark:bg-blue-900/50">📸</div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Snap to Track: AI Food Photo Analyzer</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Track calories &amp; macros instantly with AI</p>
        </div>

        {{-- Interactive Demo Section --}}
        @if (!$demoActive)
            {{-- Demo Upload Area (simulated) --}}
            <div class="relative">
                <button
                    type="button"
                    wire:click="startDemo"
                    class="flex w-full min-h-40 cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 p-8 transition-colors hover:border-blue-500 hover:bg-blue-50/50 dark:border-slate-600 dark:bg-slate-900 dark:hover:border-blue-500 dark:hover:bg-blue-900/20"
                >
                    <div class="mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/50">
                        <svg class="h-8 w-8 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Try the interactive demo</span>
                    <span class="mt-1 text-xs text-slate-500 dark:text-slate-400">See how Snap to Track works</span>
                </button>
            </div>

            {{-- Tips for best results --}}
            <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-900/50 dark:bg-blue-900/20">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-lg">💡</span>
                    <span class="text-xs font-bold uppercase tracking-wider text-blue-700 dark:text-blue-400">Tips for best results</span>
                </div>
                <ul class="space-y-1 text-sm text-blue-800 dark:text-blue-200">
                    <li>• Take photo in good lighting</li>
                    <li>• Make sure all food is visible</li>
                    <li>• Capture from directly above</li>
                    <li>• Include a reference for scale (optional)</li>
                </ul>
            </div>
        @elseif ($demoAnalyzing)
            {{-- Demo Photo Preview (CSS plate illustration) --}}
            <div class="relative overflow-hidden rounded-xl">
                <div class="flex h-48 w-full items-center justify-center bg-gradient-to-br from-amber-100 to-orange-100 dark:from-amber-900/30 dark:to-orange-900/30">
                    <div class="text-center">
                        <div class="mx-auto flex h-28 w-28 items-center justify-center rounded-full border-4 border-white bg-slate-50 shadow-inner dark:border-slate-300 dark:bg-slate-200">
                            <div class="flex gap-1.5">
                                <div class="h-8 w-5 rounded-md bg-amber-700/80"></div>
                                <div class="h-8 w-5 rounded-md bg-amber-100 ring-1 ring-amber-200"></div>
                                <div class="h-8 w-5 rounded-md bg-emerald-500/70"></div>
                            </div>
                        </div>
                        <p class="mt-2 text-xs font-medium text-amber-700 dark:text-amber-300">Sample meal photo</p>
                    </div>
                </div>
            </div>

            {{-- Simulated Analyzing State with Progressive Steps --}}
            <div
                x-data="{ step: 0 }"
                x-init="
                    setTimeout(() => step = 1, 600);
                    setTimeout(() => step = 2, 1400);
                    setTimeout(() => step = 3, 2200);
                    setTimeout(() => $wire.showDemoResults(), 3000);
                "
                class="space-y-3"
            >
                <div class="w-full min-h-14 rounded-xl bg-blue-600 py-4 text-center font-bold text-white">
                    <span class="flex items-center justify-center gap-2">
                        <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Analyzing your meal...
                    </span>
                </div>

                {{-- Progress Steps --}}
                <div class="space-y-2 rounded-xl bg-slate-50 p-4 dark:bg-slate-900/50">
                    <div class="flex items-center gap-2 text-sm" x-show="step >= 1" x-transition.opacity.duration.300ms>
                        <span x-show="step >= 2" class="text-emerald-500">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                        </span>
                        <span x-show="step < 2" class="text-blue-500">
                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </span>
                        <span class="text-slate-700 dark:text-slate-300">Detecting food items...</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm" x-show="step >= 2" x-transition.opacity.duration.300ms>
                        <span x-show="step >= 3" class="text-emerald-500">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                        </span>
                        <span x-show="step < 3" class="text-blue-500">
                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </span>
                        <span class="text-slate-700 dark:text-slate-300">Estimating portions...</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm" x-show="step >= 3" x-transition.opacity.duration.300ms>
                        <span class="text-blue-500">
                            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </span>
                        <span class="text-slate-700 dark:text-slate-300">Calculating nutrition...</span>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-center dark:border-amber-900/50 dark:bg-amber-900/20">
                <p class="text-xs font-medium text-amber-700 dark:text-amber-300">This is a demo with sample data</p>
            </div>
        @elseif ($demoComplete)
            {{-- Demo Photo Preview --}}
            <div class="relative overflow-hidden rounded-xl">
                <div class="flex h-48 w-full items-center justify-center bg-gradient-to-br from-amber-100 to-orange-100 dark:from-amber-900/30 dark:to-orange-900/30">
                    <div class="text-center">
                        <div class="mx-auto flex h-28 w-28 items-center justify-center rounded-full border-4 border-white bg-slate-50 shadow-inner dark:border-slate-300 dark:bg-slate-200">
                            <div class="flex gap-1.5">
                                <div class="h-8 w-5 rounded-md bg-amber-700/80"></div>
                                <div class="h-8 w-5 rounded-md bg-amber-100 ring-1 ring-amber-200"></div>
                                <div class="h-8 w-5 rounded-md bg-emerald-500/70"></div>
                            </div>
                        </div>
                        <p class="mt-2 text-xs font-medium text-amber-700 dark:text-amber-300">Sample meal photo</p>
                    </div>
                </div>
            </div>

            {{-- Demo Results --}}
            <div
                x-data
                x-init="$el.classList.remove('opacity-0', 'translate-y-4')"
                class="overflow-hidden rounded-2xl border border-slate-100 bg-white opacity-0 translate-y-4 shadow-lg transition-all duration-500 ease-out dark:border-slate-700 dark:bg-slate-800"
            >

                {{-- Demo Badge --}}
                <div class="bg-amber-50 px-4 py-2 text-center dark:bg-amber-900/20">
                    <span class="text-xs font-bold text-amber-700 dark:text-amber-300">Interactive Demo — Sample Results</span>
                </div>

                {{-- Total Macros Header --}}
                <div class="bg-slate-50 p-6 dark:bg-slate-800/50">
                    <div class="mb-3 flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Nutrition</span>
                        <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                            {{ $demoResult['confidence'] }}% confident
                        </span>
                    </div>

                    {{-- Calorie Display --}}
                    <div class="mb-4 text-center">
                        <span class="text-5xl font-black text-slate-900 dark:text-white">{{ number_format($demoResult['totalCalories'], 0) }}</span>
                        <span class="ml-1 text-lg font-medium text-slate-400">kcal</span>
                        <div class="mt-2">
                            <p class="text-xs text-slate-500 dark:text-slate-400">~{{ round($demoResult['totalCalories'] / 2000 * 100) }}% of a 2,000 kcal daily goal</p>
                            <div class="mx-auto mt-1 h-1.5 w-3/4 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                <div class="h-full rounded-full bg-blue-500" style="width: {{ min(100, round($demoResult['totalCalories'] / 2000 * 100)) }}%"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Macro Bars --}}
                    <div class="grid grid-cols-3 gap-4">
                        {{-- Protein --}}
                        <div class="text-center">
                            <div class="mx-auto mb-2 h-2 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                <div class="h-full rounded-full bg-blue-500" style="width: {{ min(100, ($demoResult['totalProtein'] / max(1, $demoResult['totalProtein'] + $demoResult['totalCarbs'] + $demoResult['totalFat'])) * 100) }}%"></div>
                            </div>
                            <span class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ number_format($demoResult['totalProtein'], 1) }}g</span>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Protein</p>
                        </div>
                        {{-- Carbs --}}
                        <div class="text-center">
                            <div class="mx-auto mb-2 h-2 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                <div class="h-full rounded-full bg-amber-500" style="width: {{ min(100, ($demoResult['totalCarbs'] / max(1, $demoResult['totalProtein'] + $demoResult['totalCarbs'] + $demoResult['totalFat'])) * 100) }}%"></div>
                            </div>
                            <span class="text-lg font-bold text-amber-600 dark:text-amber-400">{{ number_format($demoResult['totalCarbs'], 1) }}g</span>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Carbs</p>
                        </div>
                        {{-- Fat --}}
                        <div class="text-center">
                            <div class="mx-auto mb-2 h-2 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                <div class="h-full rounded-full bg-rose-500" style="width: {{ min(100, ($demoResult['totalFat'] / max(1, $demoResult['totalProtein'] + $demoResult['totalCarbs'] + $demoResult['totalFat'])) * 100) }}%"></div>
                            </div>
                            <span class="text-lg font-bold text-rose-600 dark:text-rose-400">{{ number_format($demoResult['totalFat'], 1) }}g</span>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Fat</p>
                        </div>
                    </div>
                </div>

                {{-- Individual Items --}}
                <div class="border-t border-slate-100 p-4 dark:border-slate-700">
                    <h3 class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-400">Food Items Detected</h3>
                    <div class="space-y-3">
                        @foreach ($demoResult['items'] as $item)
                            <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-900/50">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h4 class="font-semibold text-slate-900 dark:text-white">{{ $item['name'] }}</h4>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $item['portion'] }}</p>
                                    </div>
                                    <span class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ number_format($item['calories'], 0) }} kcal</span>
                                </div>
                                <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 font-medium text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                                        <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                                        P {{ number_format($item['protein'], 1) }}g
                                    </span>
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                        C {{ number_format($item['carbs'], 1) }}g
                                    </span>
                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2 py-0.5 font-medium text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">
                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                        F {{ number_format($item['fat'], 1) }}g
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- CTA Button --}}
                <div class="border-t border-slate-100 p-4 dark:border-slate-700">
                    <a
                        href="{{ route('register') }}"
                        class="block w-full rounded-xl bg-blue-600 py-3.5 text-center text-sm font-bold text-white transition-all hover:bg-blue-700 hover:scale-[1.02] active:scale-[0.98]"
                    >
                        Sign up to analyze your own meals →
                    </a>
                    <p class="mt-2 text-center text-xs text-slate-400 dark:text-slate-500">
                        Already have an account? <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">Log in</a>
                    </p>
                </div>
            </div>

            {{-- Try Demo Again --}}
            <button
                type="button"
                wire:click="resetDemo"
                class="w-full rounded-xl border-2 border-slate-200 py-3 text-center text-sm font-medium text-slate-600 transition-colors hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-400 dark:hover:border-slate-600 dark:hover:bg-slate-800"
            >
                Restart demo
            </button>
        @endif

        {{-- How It Works Section --}}
        <div class="space-y-3">
            <h2 class="text-center text-sm font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">How it works</h2>
            <div class="grid grid-cols-3 gap-3 text-center">
                <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-900/50">
                    <div class="mx-auto mb-2 flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-600 dark:bg-blue-900/50 dark:text-blue-400">1</div>
                    <p class="text-xs font-medium text-slate-700 dark:text-slate-300">Snap a photo of your meal</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-900/50">
                    <div class="mx-auto mb-2 flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-600 dark:bg-blue-900/50 dark:text-blue-400">2</div>
                    <p class="text-xs font-medium text-slate-700 dark:text-slate-300">AI identifies each food item</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-900/50">
                    <div class="mx-auto mb-2 flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-600 dark:bg-blue-900/50 dark:text-blue-400">3</div>
                    <p class="text-xs font-medium text-slate-700 dark:text-slate-300">Get instant macro breakdown</p>
                </div>
            </div>
        </div>

        {{-- Sign Up CTA --}}
        @if (!$demoComplete)
            <a
                href="{{ route('register') }}"
                class="block w-full rounded-xl bg-blue-600 py-3.5 text-center text-sm font-bold text-white transition-all hover:bg-blue-700 hover:scale-[1.02] active:scale-[0.98]"
            >
                Sign up to start analyzing →
            </a>
            <p class="text-center text-xs text-slate-400 dark:text-slate-500">
                Already have an account? <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">Log in</a>
            </p>
        @endif

        <p class="text-center text-xs text-slate-400 dark:text-slate-500">
            <strong>Disclaimer:</strong> These are AI estimates. Actual nutrition depends on how the food was made.
        </p>

    </main>

    {{-- FAQ Section --}}
    <section class="relative z-10 mt-8 w-full max-w-md" aria-labelledby="faq-heading">
        <h2 id="faq-heading" class="mb-4 text-center text-lg font-bold text-slate-900 dark:text-white">
            Frequently Asked Questions
        </h2>

        <div class="space-y-3" x-data="{ openFaq: null }">
            {{-- FAQ 1 --}}
            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-slate-800">
                <button
                    type="button"
                    @click="openFaq = openFaq === 1 ? null : 1"
                    class="flex w-full items-center justify-between p-4 text-left font-medium text-slate-900 transition-colors hover:bg-slate-50 dark:text-white dark:hover:bg-slate-700/50"
                    aria-expanded="false"
                >
                    <span class="speakable-how-it-works">How does the food photo analyzer work?</span>
                    <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform" :class="{ 'rotate-180': openFaq === 1 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="openFaq === 1" x-collapse class="border-t border-slate-100 px-4 pb-4 pt-2 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
                    <p class="speakable-how-it-works">Upload a photo in the Altani assistant and our AI identifies each food item, estimates portions, and calculates the calories, protein, carbs, and fat for your entire meal.</p>
                </div>
            </div>

            {{-- FAQ 2 --}}
            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-slate-800">
                <button
                    type="button"
                    @click="openFaq = openFaq === 2 ? null : 2"
                    class="flex w-full items-center justify-between p-4 text-left font-medium text-slate-900 transition-colors hover:bg-slate-50 dark:text-white dark:hover:bg-slate-700/50"
                    aria-expanded="false"
                >
                    <span>How accurate are the calorie estimates?</span>
                    <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform" :class="{ 'rotate-180': openFaq === 2 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="openFaq === 2" x-collapse class="border-t border-slate-100 px-4 pb-4 pt-2 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
                    <p>Estimates work best when the photo is clear. Lighting matters. If we can see the food clearly, the numbers will be more accurate. The confidence score tells you how sure we are.</p>
                </div>
            </div>

            {{-- FAQ 3 --}}
            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-slate-800">
                <button
                    type="button"
                    @click="openFaq = openFaq === 3 ? null : 3"
                    class="flex w-full items-center justify-between p-4 text-left font-medium text-slate-900 transition-colors hover:bg-slate-50 dark:text-white dark:hover:bg-slate-700/50"
                    aria-expanded="false"
                >
                    <span>What types of food can be recognized?</span>
                    <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform" :class="{ 'rotate-180': openFaq === 3 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="openFaq === 3" x-collapse class="border-t border-slate-100 px-4 pb-4 pt-2 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
                    <p>We recognize most common foods. This includes fruits, vegetables, meats, and grains. Snacks and drinks work too. Make sure the food is easy to see.</p>
                </div>
            </div>

            {{-- FAQ 4 --}}
            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-slate-800">
                <button
                    type="button"
                    @click="openFaq = openFaq === 4 ? null : 4"
                    class="flex w-full items-center justify-between p-4 text-left font-medium text-slate-900 transition-colors hover:bg-slate-50 dark:text-white dark:hover:bg-slate-700/50"
                    aria-expanded="false"
                >
                    <span>Is my photo data kept private?</span>
                    <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform" :class="{ 'rotate-180': openFaq === 4 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="openFaq === 4" x-collapse class="border-t border-slate-100 px-4 pb-4 pt-2 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
                    <p>Yes. We process your photo to get the data, then we delete it. We do not keep your images.</p>
                </div>
            </div>

            {{-- FAQ 5 (new) --}}
            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-slate-800">
                <button
                    type="button"
                    @click="openFaq = openFaq === 5 ? null : 5"
                    class="flex w-full items-center justify-between p-4 text-left font-medium text-slate-900 transition-colors hover:bg-slate-50 dark:text-white dark:hover:bg-slate-700/50"
                    aria-expanded="false"
                >
                    <span>How do I use Snap to Track?</span>
                    <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform" :class="{ 'rotate-180': openFaq === 5 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="openFaq === 5" x-collapse class="border-t border-slate-100 px-4 pb-4 pt-2 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
                    <p>Create a free account, then open the Altani assistant. Upload a photo of your meal in the chat, and Altani will instantly analyze it and give you a full calorie and macro breakdown.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Main App Promo --}}
    <section class="relative z-10 mt-8 w-full max-w-md">
        <div class="overflow-hidden rounded-2xl bg-slate-900 px-6 py-8 text-center shadow-xl shadow-slate-900/10 dark:bg-slate-800 dark:ring-1 dark:ring-white/10">
            <div class="mb-4 flex justify-center">
                <span class="text-4xl">🥗</span>
            </div>
            <h2 class="mb-3 text-xl font-bold text-white">
                Need more than just tracking?
            </h2>
            <p class="mb-6 text-sm leading-relaxed text-slate-300">
                Get personalized meal plans tailored to your glucose levels and taste preferences.
            </p>
            <a href="{{ route('register') }}"
               class="inline-flex w-full items-center justify-center rounded-xl bg-white py-3.5 text-sm font-bold text-slate-900 transition-transform hover:scale-[1.02] hover:bg-slate-50">
                Get Started
            </a>
        </div>
    </section>

    {{-- More Free Tools --}}
    <section class="relative z-10 mt-12 mb-8 w-full max-w-md">
        <h2 class="mb-4 text-center text-lg font-bold text-slate-900 dark:text-white">
            Explore More Tools
        </h2>
        <a href="{{ route('tools.index') }}" class="group flex flex-col items-center rounded-xl bg-white p-6 text-center shadow-sm transition-all hover:shadow-md dark:bg-slate-800">
            <span class="mb-2 text-3xl">🛠️</span>
            <h3 class="font-bold text-slate-900 dark:text-white">View All Tools</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Discover health trackers, calculators, and nutrition tools</p>
        </a>
    </section>

    <x-footer />
</div>
