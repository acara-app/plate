<?php

declare(strict_types=1);

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts.mini-app', ['metaDescription' => 'Free diabetes and nutrition tools: glucose spike calculator, food photo analyzer, USDA daily servings calculator, diabetes log book, and more.', 'metaKeywords' => 'diabetes tools, free nutrition calculator, glucose spike checker, food analyzer, USDA dietary guidelines, diabetes management, blood sugar tools'])]
#[Title('Free Diabetes & Nutrition Tools | Acara Plate')]
class extends Component
{
    /**
     * @return array<int, array{name: string, description: string, icon: string, route: string, badge: string|null, features: array<string>}>
     */
    public function getTools(): array
    {
        return [
            [
                'name' => 'Glucose Spike Calculator',
                'description' => 'Check if foods will spike your blood sugar. Get instant risk analysis and smart food swap suggestions.',
                'icon' => '⚡',
                'route' => route('spike-calculator'),
                'badge' => 'AI Powered',
                'features' => [
                    'Instant glucose impact prediction',
                    'Smart food swap suggestions',
                    'Risk level analysis (Low/Medium/High)',
                ],
            ],
            [
                'name' => 'Telegram Health Logger',
                'description' => 'Log glucose, insulin, carbs, and more via Telegram. Hands-free health tracking using AI-powered natural language.',
                'icon' => '💬',
                'route' => route('telegram-health-logging'),
                'badge' => 'New',
                'features' => [
                    'Log health data via messaging',
                    'AI understands natural language',
                    'Works with 6+ data types',
                ],
            ],
            [
                'name' => 'Food Photo Analyzer',
                'description' => 'Snap a photo of your meal and get instant macro breakdown with AI-powered nutrition analysis.',
                'icon' => '📸',
                'route' => route('snap-to-track'),
                'badge' => 'AI Powered',
                'features' => [
                    'Photo-to-nutrition analysis',
                    'Macro breakdown (carbs, protein, fat)',
                    'Portion size estimation',
                ],
            ],
            [
                'name' => 'USDA Daily Servings Calculator',
                'description' => 'Calculate your daily food servings based on official USDA 2025-2030 Dietary Guidelines. Includes diabetic-friendly adjustments.',
                'icon' => '🥗',
                'route' => route('usda-servings-calculator'),
                'badge' => 'New',
                'features' => [
                    'Calorie-based serving recommendations',
                    'Low-carb diabetic mode',
                    'FDA added sugar limits',
                ],
            ],
            [
                'name' => 'Diabetic Food Database',
                'description' => 'Search our database of foods with glycemic index, glycemic load, and diabetic-friendly ratings.',
                'icon' => '🔍',
                'route' => route('food.index'),
                'badge' => null,
                'features' => [
                    'Glycemic index & load data',
                    'Diabetic safety ratings',
                    'Nutrition facts',
                ],
            ],
            [
                'name' => 'Diabetes Log Book',
                'description' => 'Free printable diabetes log book to track your blood sugar, meals, medications, and more.',
                'icon' => '📖',
                'route' => route('diabetes-log-book-info'),
                'badge' => 'Printable',
                'features' => [
                    'Blood sugar tracking',
                    'Meal logging',
                    'Medication reminders',
                ],
            ],
            [
                'name' => 'AI Meal Planner',
                'description' => 'Get personalized 7-day meal plans tailored to your diabetes type, diet preferences, and glucose goals.',
                'icon' => '📅',
                'route' => route('meal-planner'),
                'badge' => 'AI Powered',
                'features' => [
                    '8 diet types supported',
                    'Personalized to your goals',
                    'Glucose-friendly recipes',
                ],
            ],
        ];
    }
};
?>

<x-slot:jsonLd>
    <x-json-ld.tools-index />
</x-slot:jsonLd>

<div
    class="relative flex min-h-screen flex-col items-center overflow-hidden bg-linear-to-br from-slate-50 via-white to-emerald-50 p-4 text-slate-900 lg:p-8 dark:from-slate-950 dark:via-slate-900 dark:to-emerald-950 dark:text-slate-50"
>
    {{-- Animated background elements --}}
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 overflow-hidden">
        <div class="absolute -left-4 top-0 h-72 w-72 animate-pulse rounded-full bg-emerald-300/20 blur-3xl dark:bg-emerald-500/10"></div>
        <div class="absolute -right-4 bottom-0 h-96 w-96 animate-pulse rounded-full bg-teal-300/20 blur-3xl dark:bg-teal-500/10"></div>
        <div class="absolute left-1/2 top-1/3 h-64 w-64 animate-pulse rounded-full bg-amber-300/10 blur-3xl dark:bg-amber-500/5"></div>
    </div>

    {{-- Header --}}
    <header class="relative z-10 mb-8 w-full max-w-4xl lg:mb-12">
        <nav class="flex items-center justify-center">
            <a href="/" class="flex items-center gap-2 text-xl font-bold text-slate-900 transition-opacity hover:opacity-80 dark:text-white">
                <span class="text-2xl" role="img" aria-label="strawberry">🍓</span>
                Acara Plate
            </a>
        </nav>
    </header>

    {{-- Main Content --}}
    <main class="relative z-10 w-full max-w-4xl">

        {{-- Hero Section --}}
        <div class="mb-10 text-center speakable-intro">
            <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-100 text-3xl dark:bg-emerald-900/50">🛠️</div>
            <h1 class="text-3xl font-bold text-slate-900 dark:text-white lg:text-4xl">Free Diabetes & Nutrition Tools</h1>
            <p class="mx-auto mt-3 max-w-2xl text-lg text-slate-600 dark:text-slate-400">
                Science-based tools to help you manage blood sugar, make smarter food choices, and live healthier.
            </p>
        </div>

        {{-- Tools Grid --}}    
        <div class="grid gap-6 md:grid-cols-2">
            @foreach ($this->getTools() as $tool)
                <a 
                    href="{{ $tool['route'] }}" 
                    class="group relative overflow-hidden rounded-2xl bg-white p-6 shadow-lg transition-all hover:shadow-xl hover:ring-2 hover:ring-emerald-500 dark:bg-slate-800"
                >
                    {{-- Badge --}}
                    @if ($tool['badge'])
                        <div class="absolute right-4 top-4">
                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-400">
                                {{ $tool['badge'] }}
                            </span>
                        </div>
                    @endif

                    {{-- Icon & Title --}}
                    <div class="mb-4 flex items-center gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-slate-100 text-3xl transition-transform group-hover:scale-110 dark:bg-slate-700">
                            {{ $tool['icon'] }}
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ $tool['name'] }}</h2>
                        </div>
                    </div>

                    {{-- Description --}}
                    <p class="mb-4 text-sm text-slate-600 dark:text-slate-400">
                        {{ $tool['description'] }}
                    </p>

                    {{-- Features --}}
                    <ul class="space-y-1.5">
                        @foreach ($tool['features'] as $feature)
                            <li class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                                <svg class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ $feature }}
                            </li>
                        @endforeach
                    </ul>

                    {{-- Arrow indicator --}}
                    <div class="absolute bottom-6 right-6 text-slate-300 transition-transform group-hover:translate-x-1 dark:text-slate-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- CTA Section --}}
        <div class="mt-12 rounded-2xl bg-emerald-600 p-8 text-center text-white shadow-lg">
            <h2 class="text-2xl font-bold">Want personalized meal plans?</h2>
            <p class="mt-2 text-emerald-100">Get AI-generated diabetic-friendly meals tailored to your preferences and health goals.</p>
            <a
                href="{{ route('register') }}"
                class="mt-6 inline-block rounded-xl bg-white px-8 py-3 font-bold text-emerald-600 transition-transform hover:scale-105"
            >
                Create Free Account
            </a>
        </div>

        {{-- Info Section --}}
        <div class="mt-12 rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
            <div class="flex items-start gap-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xl dark:bg-blue-900/50">💡</div>
                <div>
                    <h3 class="font-bold text-slate-900 dark:text-white">Why These Tools?</h3>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">
                        Managing diabetes is challenging. These tools are designed to make everyday decisions easier - from checking if a snack will spike your blood sugar to planning balanced meals. All based on scientific research and dietary guidelines.
                    </p>
                </div>
            </div>
        </div>

        <x-footer />
    </main>
</div>
