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
                'name' => 'Acara Health Sync',
                'description' => 'iPhone app that pipes Apple Health data into Plate — glucose, sleep, activity, and 100+ types — encrypted on-device with AES-256-GCM.',
                'icon' => '📱',
                'route' => route('health-sync'),
                'badge' => 'iOS App',
                'features' => [
                    'Apple Health to Plate sync',
                    'End-to-end encrypted',
                    '100+ health types',
                ],
            ],
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
                'name' => 'Caffeine Calculator',
                'description' => 'Find how much caffeine is too much based on height, sensitivity, and optional personal context.',
                'icon' => '☕',
                'route' => route('caffeine-calculator'),
                'badge' => 'New',
                'features' => [
                    'Height-adjusted daily limit',
                    'Sensitivity adjustment',
                    'AI-written guidance',
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

@php
    $tools = $this->getTools();
    $toolCount = count($tools);
@endphp

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
            <span aria-current="page" class="text-[#1A1814]">Tools</span>
        </nav>

        {{-- Hero --}}
        <header class="speakable-intro mx-auto mt-6 max-w-7xl lg:px-8">
            <div class="inline-flex items-center gap-2 border border-[#D9CFBC] bg-[#EBE2D0] px-3 py-1.5">
                <span class="size-1.5 rounded-full bg-[#C4623A] shadow-[0_0_0_3px_rgba(196,98,58,0.18)]" aria-hidden="true"></span>
                <span class="font-mono text-[10px] uppercase tracking-[0.18em] text-[#3D3833]">The toolkit</span>
            </div>

            <h1 class="mt-5 max-w-4xl text-balance font-bold text-[clamp(40px,5vw,72px)] leading-[1.02] tracking-[-0.02em] text-[#1A1814]">
                Free Diabetes &amp; Nutrition Tools
            </h1>
            <p class="mt-4 max-w-2xl text-base leading-relaxed text-[#3D3833] sm:text-lg">
                Science-based tools to help you manage blood sugar, make smarter food choices, and live healthier — no signup required.
            </p>
        </header>

        {{-- Directory --}}
        <section class="mx-auto mt-14 max-w-7xl lg:px-8" aria-labelledby="directory-heading">
            <div class="flex flex-wrap items-baseline justify-between gap-3 border-t border-[#1A1814] pt-5">
                <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">
                    <span class="font-bold text-[#1A1814]">{{ str_pad((string) $toolCount, 2, '0', STR_PAD_LEFT) }}</span>
                    <span> · Free · No signup required</span>
                </p>
                <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">
                    Updated <time datetime="{{ now()->toDateString() }}">{{ now()->format('F Y') }}</time>
                </p>
            </div>
            <h2 id="directory-heading" class="sr-only">Tool directory</h2>

            <ol class="grid border-t border-[#D9CFBC] sm:grid-cols-2 sm:divide-x sm:divide-[#D9CFBC]">
                @foreach ($tools as $i => $tool)
                    @php $position = $i + 1; @endphp
                    <li class="border-b border-[#D9CFBC] last:border-b-0 sm:[&:nth-last-child(-n+2)]:border-b-0">
                        <a
                            href="{{ $tool['route'] }}"
                            data-umami-event="tool_card_click"
                            data-umami-event-tool="{{ Str::slug($tool['name']) }}"
                            data-umami-event-location="tools_index"
                            class="group flex h-full flex-col px-2 py-8 transition-colors hover:bg-[#EBE2D0]/60 sm:px-7 sm:py-10 focus:outline-none focus-visible:bg-[#EBE2D0]"
                        >
                            <div class="flex items-baseline justify-between gap-4">
                                <div class="flex items-baseline gap-3">
                                    <span class="font-bold text-3xl italic leading-none text-[#C4623A]" aria-hidden="true">
                                        {{ str_pad((string) $position, 2, '0', STR_PAD_LEFT) }}
                                    </span>
                                    @if ($tool['badge'])
                                        <span class="border border-[#D9CFBC] bg-[#F2EBDD] px-2 py-0.5 font-mono text-[10px] uppercase tracking-[0.18em] text-[#3D3833]">
                                            {{ $tool['badge'] }}
                                        </span>
                                    @endif
                                </div>
                                <span class="text-3xl leading-none transition-transform duration-300 group-hover:scale-110" aria-hidden="true">
                                    {{ $tool['icon'] }}
                                </span>
                            </div>

                            <h3 class="mt-6 font-bold text-xl leading-tight tracking-[-0.01em] text-[#1A1814] transition-colors group-hover:text-[#C4623A] sm:text-[26px]">
                                {{ $tool['name'] }}
                            </h3>

                            <p class="mt-3 text-sm leading-relaxed text-[#3D3833]">
                                {{ $tool['description'] }}
                            </p>

                            <ul class="mt-5 space-y-1.5">
                                @foreach ($tool['features'] as $feature)
                                    <li class="flex items-start gap-2 font-mono text-[10px] uppercase tracking-[0.16em] text-[#6E665C]">
                                        <span class="mt-[5px] inline-block h-px w-2.5 shrink-0 bg-[#C4623A]" aria-hidden="true"></span>
                                        <span>{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>

                            <span class="mt-auto inline-flex items-baseline gap-2 pt-6 font-mono text-[11px] uppercase tracking-[0.16em] text-[#C4623A]">
                                Open this tool
                                <svg class="size-3 translate-y-px transition-transform duration-300 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ol>
        </section>

        {{-- Field manual --}}
        <section class="mx-auto mt-24 max-w-7xl lg:px-8">
            <div class="grid gap-6 border-t border-[#D9CFBC] pt-10 sm:grid-cols-[1fr_2fr] sm:gap-12">
                <div>
                    <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">Why these tools</p>
                </div>
                <p class="max-w-3xl text-base leading-relaxed text-[#3D3833] sm:text-lg">
                    Managing diabetes is challenging. These tools are designed to make everyday decisions easier — from checking if a snack will spike your blood sugar to planning balanced meals — all built on scientific research and the most current dietary guidelines.
                </p>
            </div>
        </section>

        {{-- CTA: Personalized meal plan --}}
        <section class="mx-auto mt-16 max-w-7xl lg:px-8">
            <article class="border border-[#1A1814] bg-[#1A1814] p-8 text-[#F2EBDD] sm:p-12">
                <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#C4623A]">Beyond the toolkit</p>
                <div class="mt-3 grid gap-8 sm:grid-cols-[2fr_1fr] sm:items-center">
                    <div>
                        <h2 class="font-bold text-2xl leading-tight tracking-[-0.02em] sm:text-3xl">
                            Want personalized meal plans?
                        </h2>
                        <p class="mt-3 max-w-xl text-sm leading-relaxed text-[#F2EBDD]/80 sm:text-base">
                            Get AI-generated, diabetic-friendly meals tailored to your preferences and health goals — built on the same science as the tools above.
                        </p>
                    </div>
                    <a
                        href="{{ route('register') }}"
                        data-umami-event="signup_cta_click"
                        data-umami-event-location="tools_index_bottom"
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
    </div>

    <x-footer class="bg-[#F2EBDD]! border-[#D9CFBC]!" />
</div>
