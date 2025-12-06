@section('title', ($mealPlan->name ?? 'Meal Plan') . ' - Printable Version | Acara Plate')
@section('meta_description', 'Printable version of your personalized meal plan with recipes, ingredients, and nutrition information.')

<x-default-layout>
    {{-- Print Styles --}}
    <style>
        @media print {
            @page {
                size: letter;
                margin: 0.5in 0.6in;
            }
            body {
                margin: 0 !important;
                padding: 0 !important;
                font-size: 10pt;
                line-height: 1.4;
            }
            .page-break {
                page-break-before: always;
            }
            .avoid-break {
                page-break-inside: avoid;
            }
        }
    </style>

    {{-- Screen-only Header --}}
    <div class="print:hidden">
        <div class="mx-auto max-w-4xl px-6 py-8">
            <a
                href="{{ route('meal-plans.index') }}"
                class="mb-6 flex items-center gap-1 text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100"
            >
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                <span>Back to Meal Plans</span>
            </a>

            <div class="mb-6">
                <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100">
                    {{ $mealPlan->name ?? 'Meal Plan' }}
                </h1>
                <p class="mt-2 text-slate-600 dark:text-slate-400">
                    Printable version of your {{ $mealPlan->duration_days }}-day meal plan with all recipes and nutrition information.
                </p>
            </div>

            <div class="mb-8 rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-950">
                <p class="mb-2 text-sm text-blue-900 dark:text-blue-100">
                    <strong>Desktop Only:</strong> This feature is optimized for desktop browsers.
                </p>
                <p class="text-sm text-blue-800 dark:text-blue-200">
                    <strong>To Download:</strong> Click the button below, then in the print dialog, select "Save as PDF" as your destination.
                </p>
            </div>

            <button
                onclick="window.print()"
                class="inline-flex items-center rounded-lg bg-slate-900 px-6 py-3 font-medium text-white transition-colors hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-slate-200"
            >
                <svg class="mr-2 size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Print / Download PDF
            </button>

            <div class="mt-8 rounded-lg bg-slate-100 p-4 dark:bg-slate-800">
                <p class="mb-4 text-center text-sm text-slate-600 dark:text-slate-400">Preview (scroll to see all days)</p>
            </div>
        </div>
    </div>

    {{-- Printable Content (visible on screen for preview and in print) --}}
    <article
        itemscope
        itemtype="https://schema.org/Diet"
        class="mx-auto max-w-4xl bg-white px-6 py-8 text-black print:m-0 print:max-w-none print:p-0 dark:bg-slate-900 dark:text-white print:dark:bg-white print:dark:text-black"
    >
        {{-- Header with Branding --}}
        <header class="mb-6 border-b-2 border-black pb-4 print:border-black print:dark:border-black">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-slate-600 print:text-slate-600 print:dark:text-slate-600">
                        Acara Plate
                    </p>
                    <h1 itemprop="name" class="text-2xl font-bold print:text-xl">
                        {{ $mealPlan->name ?? 'Personalized Meal Plan' }}
                    </h1>
                </div>
                <div class="text-right text-sm text-slate-600 print:text-slate-600 print:dark:text-slate-600">
                    <p>{{ $mealPlan->duration_days }} Day Plan</p>
                    @if($mealPlan->target_daily_calories)
                        <p>{{ number_format($mealPlan->target_daily_calories) }} kcal/day target</p>
                    @endif
                </div>
            </div>
            @if($mealPlan->description)
                <p itemprop="description" class="mt-2 text-sm text-slate-600 print:text-slate-600 print:dark:text-slate-600">
                    {{ $mealPlan->description }}
                </p>
            @endif
        </header>

        {{-- Meal Plan Days --}}
        @foreach($mealsByDay as $dayNumber => $meals)
            @php
                $dayName = $meals->first()?->getDayName() ?? "Day {$dayNumber}";
                $dailyCalories = $meals->sum('calories');
                $dailyProtein = $meals->sum('protein_grams');
                $dailyCarbs = $meals->sum('carbs_grams');
                $dailyFat = $meals->sum('fat_grams');
            @endphp

            <section
                aria-labelledby="day-{{ $dayNumber }}-heading"
                class="{{ $loop->first ? '' : 'page-break' }} mb-8 print:mb-6"
            >
                {{-- Day Header --}}
                <header class="mb-4 rounded-lg bg-slate-100 px-4 py-3 print:bg-slate-200 print:dark:bg-slate-200">
                    <div class="flex items-center justify-between">
                        <h2 id="day-{{ $dayNumber }}-heading" class="text-lg font-bold">
                            {{ $dayName }}
                        </h2>
                        <div class="text-sm">
                            <span class="font-semibold">{{ number_format($dailyCalories) }}</span> kcal
                            <span class="mx-2 text-slate-400">|</span>
                            <span>P: {{ number_format($dailyProtein) }}g</span>
                            <span class="mx-1">•</span>
                            <span>C: {{ number_format($dailyCarbs) }}g</span>
                            <span class="mx-1">•</span>
                            <span>F: {{ number_format($dailyFat) }}g</span>
                        </div>
                    </div>
                </header>

                {{-- Meals for the Day --}}
                <div class="space-y-4 print:space-y-3">
                    @foreach($meals as $meal)
                        <article
                            itemscope
                            itemtype="https://schema.org/Recipe"
                            class="avoid-break rounded-lg border border-slate-200 p-4 print:border-slate-300 print:p-3 print:dark:border-slate-300"
                        >
                            {{-- Meal Header --}}
                            <header class="mb-3 flex items-start justify-between">
                                <div>
                                    <span class="mb-1 inline-block rounded bg-slate-200 px-2 py-0.5 text-xs font-medium uppercase print:bg-slate-300 print:dark:bg-slate-300">
                                        @switch($meal->type->value)
                                            @case('breakfast')
                                                🌅 Breakfast
                                                @break
                                            @case('lunch')
                                                ☀️ Lunch
                                                @break
                                            @case('dinner')
                                                🌙 Dinner
                                                @break
                                            @case('snack')
                                                🍎 Snack
                                                @break
                                        @endswitch
                                    </span>
                                    <h3 itemprop="name" class="text-base font-semibold">
                                        {{ $meal->name }}
                                    </h3>
                                </div>
                                <div class="text-right text-sm">
                                    <div itemprop="nutrition" itemscope itemtype="https://schema.org/NutritionInformation">
                                        <span itemprop="calories" class="font-semibold">{{ number_format($meal->calories) }} kcal</span>
                                    </div>
                                    @if($meal->preparation_time_minutes)
                                        <time itemprop="prepTime" datetime="PT{{ $meal->preparation_time_minutes }}M" class="text-xs text-slate-600 print:text-slate-600 print:dark:text-slate-600">
                                            ⏱️ {{ $meal->preparation_time_minutes }} min
                                        </time>
                                    @endif
                                </div>
                            </header>

                            {{-- Nutrition Summary --}}
                            <dl class="mb-3 flex gap-4 text-xs text-slate-600 print:text-slate-600 print:dark:text-slate-600">
                                @if($meal->protein_grams)
                                    <div>
                                        <dt class="sr-only">Protein</dt>
                                        <dd>Protein: <strong class="text-black print:dark:text-black">{{ number_format($meal->protein_grams) }}g</strong></dd>
                                    </div>
                                @endif
                                @if($meal->carbs_grams)
                                    <div>
                                        <dt class="sr-only">Carbs</dt>
                                        <dd>Carbs: <strong class="text-black print:dark:text-black">{{ number_format($meal->carbs_grams) }}g</strong></dd>
                                    </div>
                                @endif
                                @if($meal->fat_grams)
                                    <div>
                                        <dt class="sr-only">Fat</dt>
                                        <dd>Fat: <strong class="text-black print:dark:text-black">{{ number_format($meal->fat_grams) }}g</strong></dd>
                                    </div>
                                @endif
                                @if($meal->portion_size)
                                    <div>
                                        <dt class="sr-only">Portion</dt>
                                        <dd>Portion: <strong class="text-black print:dark:text-black">{{ $meal->portion_size }}</strong></dd>
                                    </div>
                                @endif
                            </dl>

                            {{-- Description --}}
                            @if($meal->description)
                                <p itemprop="description" class="mb-3 text-sm text-slate-700 print:text-slate-700 print:dark:text-slate-700">
                                    {{ $meal->description }}
                                </p>
                            @endif

                            <div class="grid gap-4 text-sm md:grid-cols-2 print:grid-cols-2 print:gap-3">
                                {{-- Ingredients --}}
                                @if(is_array($meal->ingredients) && count($meal->ingredients) > 0)
                                    <section aria-label="Ingredients">
                                        <h4 class="mb-2 font-semibold">Ingredients</h4>
                                        <ul class="list-inside list-disc space-y-0.5 text-slate-700 print:text-slate-700 print:dark:text-slate-700">
                                            @foreach($meal->ingredients as $ingredient)
                                                <li itemprop="recipeIngredient">
                                                    <strong>{{ $ingredient['quantity'] ?? '' }}</strong>
                                                    {{ $ingredient['name'] ?? '' }}
                                                    @if(!empty($ingredient['specificity']))
                                                        <span class="text-slate-500 print:text-slate-500 print:dark:text-slate-500">({{ $ingredient['specificity'] }})</span>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </section>
                                @endif

                                {{-- Preparation Instructions --}}
                                @if($meal->preparation_instructions)
                                    <section aria-label="Instructions">
                                        <h4 class="mb-2 font-semibold">Instructions</h4>
                                        <div itemprop="recipeInstructions" class="text-slate-700 print:text-slate-700 print:dark:text-slate-700">
                                            {!! nl2br(e($meal->preparation_instructions)) !!}
                                        </div>
                                    </section>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endforeach

        {{-- Footer --}}
        <footer class="mt-8 border-t border-slate-200 pt-4 text-center text-xs text-slate-500 print:border-slate-300 print:text-slate-500 print:dark:border-slate-300 print:dark:text-slate-500">
            <p>Generated by <strong>Acara Plate</strong> • {{ now()->format('F j, Y') }}</p>
            <p class="mt-1">Visit <strong>plate.acara.app</strong> for personalized AI-powered meal planning</p>
        </footer>
    </article>
</x-default-layout>
