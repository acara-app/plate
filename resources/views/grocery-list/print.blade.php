@section('title', 'Grocery List - ' . ($mealPlan->name ?? 'Meal Plan') . ' | Acara Plate')
@section('meta_description', 'Printable grocery list for your personalized meal plan.')

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
            .checkbox {
                width: 14px;
                height: 14px;
                border: 1.5px solid #000;
                border-radius: 2px;
                display: inline-block;
                flex-shrink: 0;
            }
        }
        .checkbox {
            width: 16px;
            height: 16px;
            border: 2px solid currentColor;
            border-radius: 3px;
            display: inline-block;
            flex-shrink: 0;
        }
    </style>

    {{-- Screen-only Header --}}
    <div class="print:hidden">
        <div class="mx-auto max-w-4xl px-6 py-8">
            <a
                href="{{ route('meal-plans.grocery-list.show', $mealPlan) }}"
                class="mb-6 flex items-center gap-1 text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100"
            >
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                <span>Back to Grocery List</span>
            </a>

            <div class="mb-6">
                <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100">
                    Grocery List
                </h1>
                <p class="mt-2 text-slate-600 dark:text-slate-400">
                    Printable grocery list for your {{ $mealPlan->duration_days }}-day meal plan with {{ $groceryList->items->count() }} items.
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
                <p class="mb-4 text-center text-sm text-slate-600 dark:text-slate-400">Preview (scroll to see all categories)</p>
            </div>
        </div>
    </div>

    {{-- Printable Content --}}
    <article
        class="mx-auto max-w-4xl bg-white px-6 py-8 text-black print:m-0 print:max-w-none print:p-0 dark:bg-slate-900 dark:text-white print:dark:bg-white print:dark:text-black"
    >
        {{-- Header with Branding --}}
        <header class="mb-6 border-b-2 border-black pb-4 print:border-black print:dark:border-black">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-slate-600 print:text-slate-600 print:dark:text-slate-600">
                        Acara Plate
                    </p>
                    <h1 class="text-2xl font-bold print:text-xl">
                        🛒 Grocery List
                    </h1>
                    <p class="text-sm text-slate-600 print:text-slate-600 print:dark:text-slate-600">
                        {{ $mealPlan->name ?? 'Meal Plan' }}
                    </p>
                </div>
                <div class="text-right text-sm text-slate-600 print:text-slate-600 print:dark:text-slate-600">
                    <p>{{ $mealPlan->duration_days }} Day Plan</p>
                    <p>{{ $groceryList->items->count() }} Items</p>
                    <p>Generated {{ $groceryList->created_at->format('M j, Y') }}</p>
                </div>
            </div>
        </header>

        {{-- Grocery Items by Category --}}
        <div class="grid grid-cols-2 gap-6 print:grid-cols-2 print:gap-4">
            @php
                $categoryEmojis = [
                    'Produce' => '🥬',
                    'Dairy' => '🥛',
                    'Meat & Seafood' => '🥩',
                    'Pantry' => '🥫',
                    'Frozen' => '🧊',
                    'Bakery' => '🍞',
                    'Beverages' => '🥤',
                    'Condiments & Sauces' => '🧴',
                    'Herbs & Spices' => '🌿',
                    'Other' => '📦',
                ];
                $categoryOrder = [
                    'Produce',
                    'Meat & Seafood',
                    'Dairy',
                    'Bakery',
                    'Pantry',
                    'Frozen',
                    'Beverages',
                    'Condiments & Sauces',
                    'Herbs & Spices',
                    'Other',
                ];
                $sortedCategories = $itemsByCategory->keys()->sort(function ($a, $b) use ($categoryOrder) {
                    $aIndex = array_search($a, $categoryOrder);
                    $bIndex = array_search($b, $categoryOrder);
                    if ($aIndex === false && $bIndex === false) return strcmp($a, $b);
                    if ($aIndex === false) return 1;
                    if ($bIndex === false) return -1;
                    return $aIndex - $bIndex;
                });
            @endphp

            @foreach($sortedCategories as $category)
                @php $items = $itemsByCategory[$category]; @endphp
                <section class="avoid-break">
                    <h2 class="mb-3 flex items-center gap-2 border-b border-slate-300 pb-2 text-lg font-bold print:text-base print:border-slate-400 print:dark:border-slate-400">
                        <span>{{ $categoryEmojis[$category] ?? '📦' }}</span>
                        <span>{{ $category }}</span>
                        <span class="ml-auto text-sm font-normal text-slate-500">({{ $items->count() }})</span>
                    </h2>
                    <ul class="space-y-2 print:space-y-1">
                        @foreach($items as $item)
                            <li class="flex items-start gap-2 text-sm print:text-xs">
                                <span class="checkbox mt-0.5"></span>
                                <span class="flex-1">
                                    <span class="font-medium">{{ $item->name }}</span>
                                    <span class="text-slate-600 print:text-slate-600 print:dark:text-slate-600">— {{ $item->quantity }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>

        {{-- Footer --}}
        <footer class="mt-8 border-t border-slate-300 pt-4 text-center text-xs text-slate-500 print:mt-6 print:border-slate-400 print:dark:border-slate-400">
            <p>Generated by Acara Plate • {{ now()->format('F j, Y') }}</p>
            <p class="mt-1">Your personalized AI-powered nutrition assistant</p>
        </footer>
    </article>
</x-default-layout>
