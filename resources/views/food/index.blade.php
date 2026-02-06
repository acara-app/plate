@section('title', ($categoryTitle ?? 'Food Database - Glycemic Index & Nutrition for Diabetics') . ' | Acara Plate')
@section('meta_description',
    $categoryDescription ??
    'Explore our comprehensive diabetic food list and glycemic index
    database. Find safe foods for your blood sugar, nutrition facts, and detailed insulin spike predictions.')
@section('meta_keywords',
    'food database, glycemic index database, diabetes food list, nutrition facts, diabetic food
    guide, blood sugar friendly foods, low glycemic foods list')
@section('canonical_url', $canonicalUrl)

@section('head')
    @if (request()->hasAny(['search', 'assessment', 'category']))
        <meta name="robots" content="noindex, follow">
    @endif

    <script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        {
            "@@type": "ListItem",
            "position": 1,
            "name": "Home",
            "item": "{{ url('/') }}"
        }@if($currentCategory),{
            "@@type": "ListItem",
            "position": 2,
            "name": "Food",
            "item": "{{ route('food.index') }}"
        },{
            "@@type": "ListItem",
            "position": 3,
            "name": "{{ \App\Enums\FoodCategory::tryFrom($currentCategory)?->label() }}",
            "item": "{{ route('food.category', ['category' => $currentCategory]) }}"
        }@else,{
            "@@type": "ListItem",
            "position": 2,
            "name": "Food Database",
            "item": "{{ route('food.index') }}"
        }@endif
    ]
}
</script>
    <script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "CollectionPage",
    "name": "{{ $categoryTitle ?? 'Food Database - Glycemic Index & Nutrition for Diabetics' }}",
    "description": "{{ $categoryDescription ?? 'Browse our comprehensive food database with glycemic index information, nutrition facts, and diabetes safety assessments.' }}",
    "url": "{{ $canonicalUrl }}",
    "isPartOf": {
        "@@type": "WebSite",
        "name": "Acara Plate",
        "url": "{{ url('/') }}"
    }
}
</script>
    <script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "ItemList",
    "name": "{{ $currentCategory ? \App\Enums\FoodCategory::tryFrom($currentCategory)?->label() . ' Database' : 'Diabetic Food Database' }}",
    "numberOfItems": {{ $foods->count() }},
    "itemListElement": [
        @foreach($foods as $food)
        {
            "@@type": "ListItem",
            "position": {{ $loop->iteration + (($foods->currentPage() - 1) * $foods->perPage()) }},
            "url": "{{ route('food.show', $food->slug) }}",
            "name": "{{ $food->display_name }}"
        }@unless ($loop->last),@endunless
        @endforeach
    ]
}
</script>
@endsection

<x-mini-app-layout>
    <div class="mx-auto my-16 max-w-7xl px-6 lg:px-8">
        {{-- Breadcrumb --}}
        <nav aria-label="Breadcrumb" class="-mt-10 mb-12 relative z-50">
            <a href="{{ url('/') }}" class="flex items-center dark:text-slate-400 text-slate-600 hover:underline"
                wire:navigate>
                <x-icons.chevron-left class="size-4" />
                <span>Home</span>
            </a>
        </nav>

        {{-- Hero Section --}}
        <div class="mt-6">
            <h1 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-4">
                {{ $categoryTitle ?? 'Diabetic Food Database & Glycemic Index' }}
            </h1>
            <p class="text-lg text-slate-600 dark:text-slate-300 mb-8 max-w-3xl">
                @if ($currentCategory)
                    {{ $categoryDescription }}
                @else
                    So, what can you actually eat? We've built a USDA-verified database containing proper nutrition info
                    and safety checks—so you can instantly spot what triggers a spike without all the guesswork.
                @endif
            </p>

            {{-- Nutrition Advisor Banner --}}
            <div class="mb-8 bg-gradient-to-r from-rose-50 to-pink-50 dark:from-rose-950/30 dark:to-pink-950/30 rounded-2xl p-6 border border-rose-200 dark:border-rose-800">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-rose-500 to-pink-500 text-white shadow-lg">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                Not sure what to eat?
                                <span class="inline-flex items-center rounded-full bg-rose-100 dark:bg-rose-900/50 px-2.5 py-0.5 text-xs font-medium text-rose-700 dark:text-rose-300">New</span>
                            </h2>
                            <p class="mt-1 text-slate-600 dark:text-slate-300">
                                Ask our AI nutritionist. Get personalized advice for any situation — restaurants, grocery stores, or your own kitchen.
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('chat') }}" 
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition-all hover:bg-rose-700 hover:shadow-lg shrink-0">
                        Ask AI Nutritionist
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>

            {{-- ======================= --}}
            {{-- SEARCH & FILTER BAR --}}
            {{-- ======================= --}}
            <div
                class="mb-8 p-6 bg-white/80 dark:bg-slate-800/80 backdrop-blur-sm rounded-2xl shadow-lg border border-slate-200/50 dark:border-slate-700/50">
                <form method="GET" action="{{ route('food.index') }}" class="space-y-4">
                    {{-- Search Input --}}
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="size-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" name="search" value="{{ $currentSearch }}"
                            placeholder="What are you thinking of eating? (e.g., Brown Rice, Apple)"
                            class="w-full pl-12 pr-4 py-3 text-lg bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary dark:text-white placeholder-slate-400 transition-all" />
                    </div>

                    {{-- Filter Row --}}
                    <div class="flex flex-wrap gap-4 items-center">
                        {{-- Glycemic Impact Filter --}}
                        <div class="flex-1 min-w-50">
                            <label class="block text-sm font-medium text-slate-600 dark:text-slate-400 mb-2">Glycemic
                                Impact</label>
                            <div class="flex flex-wrap gap-2">
                                <button type="submit" name="assessment" value=""
                                    class="px-4 py-2 rounded-full text-sm font-medium transition-all {{ !$currentAssessment ? 'bg-primary text-primary-foreground shadow-md' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600' }}">
                                    All
                                </button>
                                <button type="submit" name="assessment" value="low"
                                    class="px-4 py-2 rounded-full text-sm font-medium transition-all flex items-center gap-1.5 {{ $currentAssessment === 'low' ? 'bg-green-500 text-white shadow-md ring-2 ring-green-300' : 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300 hover:bg-green-200 dark:hover:bg-green-800/50' }}">
                                    <span class="size-2 rounded-full bg-current opacity-60"></span>
                                    Low GI
                                </button>
                                <button type="submit" name="assessment" value="medium"
                                    class="px-4 py-2 rounded-full text-sm font-medium transition-all flex items-center gap-1.5 {{ $currentAssessment === 'medium' ? 'bg-yellow-500 text-white shadow-md ring-2 ring-yellow-300' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300 hover:bg-yellow-200 dark:hover:bg-yellow-800/50' }}">
                                    <span class="size-2 rounded-full bg-current opacity-60"></span>
                                    Medium GI
                                </button>
                                <button type="submit" name="assessment" value="high"
                                    class="px-4 py-2 rounded-full text-sm font-medium transition-all flex items-center gap-1.5 {{ $currentAssessment === 'high' ? 'bg-red-500 text-white shadow-md ring-2 ring-red-300' : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300 hover:bg-red-200 dark:hover:bg-red-800/50' }}">
                                    <span class="size-2 rounded-full bg-current opacity-60"></span>
                                    High GI
                                </button>
                            </div>
                        </div>

                        {{-- Category Filter --}}
                        @if ($categories->isNotEmpty())
                            <div class="min-w-45">
                                <label
                                    class="block text-sm font-medium text-slate-600 dark:text-slate-400 mb-2">Category</label>
                                <select name="category" onchange="this.form.submit()"
                                    class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary dark:text-white transition-all">
                                    <option value="">All Categories</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->value }}"
                                            {{ $currentCategory === $cat->value ? 'selected' : '' }}>
                                            {{ $cat->label() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        {{-- Clear Filters --}}
                        @if ($currentSearch || $currentAssessment || $currentCategory)
                            <div class="flex items-end">
                                <a href="{{ route('food.index') }}"
                                    class="px-4 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white flex items-center gap-1.5 transition-colors">
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    Clear Filters
                                </a>
                            </div>
                        @endif
                    </div>

                    {{-- Hidden fields to preserve other filters when clicking assessment buttons --}}
                    @if ($currentSearch)
                        <input type="hidden" name="search" value="{{ $currentSearch }}" />
                    @endif
                    @if ($currentCategory)
                        <input type="hidden" name="category" value="{{ $currentCategory }}" />
                    @endif
                </form>
            </div>

            <div
                class="mb-8 p-6 bg-linear-to-r from-violet-500/10 via-purple-500/10 to-fuchsia-500/10 dark:from-violet-500/20 dark:via-purple-500/20 dark:to-fuchsia-500/20 rounded-2xl border border-violet-200/50 dark:border-violet-700/50">
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                    <div
                        class="shrink-0 w-14 h-14 bg-linear-to-br from-violet-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="size-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">
                            Still Can't Find It?
                        </h3>
                        <p class="text-slate-600 dark:text-slate-300 text-sm">
                            AI tool can actually take a look at pretty much any food you're curious about—whether it's
                            from a restaurant or just some brand you like.
                        </p>
                    </div>
                    <button disabled
                        class="shrink-0 px-5 py-2.5 bg-slate-300 dark:bg-slate-600 text-slate-500 dark:text-slate-400 rounded-xl font-medium cursor-not-allowed flex items-center gap-2">
                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        Coming Soon
                    </button>
                </div>
            </div>

            {{-- Result Count --}}
            @if ($foods->total() > 0)
                <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
                    Showing {{ $foods->firstItem() }}–{{ $foods->lastItem() }} of {{ $foods->total() }} foods
                    @if ($currentSearch || $currentAssessment || $currentCategory)
                        <span class="text-primary">(filtered)</span>
                    @endif
                </p>
            @endif

            {{-- ======================= --}}
            {{-- FOOD GRID --}}
            {{-- ======================= --}}
            @if ($foodsByCategory && !$currentSearch && !$currentAssessment && !$currentCategory)
                {{-- Grouped by Category View --}}
                @foreach ($foodsByCategory as $categoryValue => $categoryFoods)
                    @php
                        $categoryEnum = \App\Enums\FoodCategory::tryFrom($categoryValue);
                        $categoryLabel = $categoryEnum?->label() ?? 'Uncategorized';
                    @endphp
                    <div class="mb-12">

                        <h2
                            class="text-2xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-3 group-hover:text-primary transition-colors">
                            <span class="w-1.5 h-8 bg-linear-to-b from-primary to-primary/60 rounded-full"></span>
                            <a href="{{ route('food.category', ['category' => $categoryValue]) }}"
                                class="block group">
                                {{ $categoryLabel }}
                            </a>
                            <span
                                class="text-sm font-normal text-slate-400">({{ $categoryCounts[$categoryValue] ?? $categoryFoods->count() }})</span>
                        </h2>

                        <div class="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                            @foreach ($categoryFoods as $food)
                                @include('food._card', ['food' => $food])
                            @endforeach
                        </div>
                        @php
                            $totalInCategory = $categoryCounts[$categoryValue] ?? $categoryFoods->count();
                            $displayedCount = $categoryFoods->count();
                        @endphp
                        @if ($totalInCategory > $displayedCount)
                            <div class="mt-6 text-center">
                                <a href="{{ route('food.category', ['category' => $categoryValue]) }}"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-primary hover:text-primary/80 bg-primary/5 hover:bg-primary/10 rounded-xl transition-all">
                                    View all {{ $totalInCategory }} {{ $categoryLabel }} foods
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                </a>
                            </div>
                        @endif
                    </div>
                @endforeach
            @else
                {{-- Flat Paginated Grid --}}
                <div class="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 mb-10">
                    @forelse($foods as $food)
                        @include('food._card', ['food' => $food])
                    @empty
                        <div class="col-span-full text-center py-16">
                            <svg class="mx-auto size-16 text-slate-300 dark:text-slate-600 mb-4" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Honestly, We Couldn't
                                Find That</h3>
                            <p class="text-slate-500 dark:text-slate-400 mb-4">
                                @if ($currentSearch || $currentAssessment || $currentCategory)
                                    Maybe try changing your search or filters just a little bit?
                                @else
                                    We're actually adding new stuff all the time, so you can check back later.
                                @endif
                            </p>
                            @if ($currentSearch || $currentAssessment || $currentCategory)
                                <a href="{{ route('food.index') }}"
                                    class="inline-flex items-center text-primary hover:underline">
                                    <svg class="size-4 mr-1" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                    </svg>
                                    Just Show Everything
                                </a>
                            @endif
                        </div>
                    @endforelse
                </div>
            @endif

            {{-- Pagination (only shown for filtered/paginated view, not grouped category view) --}}
            @if ($foods->hasPages() && !$foodsByCategory)
                <div class="mt-8">
                    {{ $foods->links() }}
                </div>
            @endif

            {{-- ======================= --}}
            {{-- POPULAR COMPARISONS --}}
            {{-- ======================= --}}
            @if ($comparisons)
                <div class="mt-16 pt-10 border-t border-slate-200 dark:border-slate-700">
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">
                        Which One Is Actually Better?
                    </h2>
                    <p class="text-slate-600 dark:text-slate-400 mb-6 max-w-2xl">
                        It's kinda tricky knowing which choice will spike your blood sugar more, isn't it? We've put
                        these comparisons together so you can see which one is actually safer for you.
                    </p>
                    <div class="flex flex-wrap gap-3">
                        @foreach ($comparisons as $comparison)
                            <a href="{{ route('spike-calculator', ['compare' => $comparison['name1'] . ' vs ' . $comparison['name2']]) }}"
                                class="group px-4 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl hover:border-emerald-500 dark:hover:border-emerald-500 hover:shadow-md transition-all flex items-center gap-2">
                                <span
                                    class="font-medium text-slate-700 dark:text-slate-200 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">{{ $comparison['name1'] }}</span>
                                <span class="text-slate-400 text-sm">vs</span>
                                <span
                                    class="font-medium text-slate-700 dark:text-slate-200 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">{{ $comparison['name2'] }}</span>
                                <svg class="size-4 text-slate-400 group-hover:text-emerald-500 transition-colors"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ======================= --}}
            {{-- CTA Section --}}
            {{-- ======================= --}}
            <div
                class="mt-16 bg-linear-to-r from-primary/10 to-primary/5 dark:from-primary/20 dark:to-primary/10 rounded-2xl p-8">
                <div class="max-w-2xl">
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">
                        {{ $currentCategory ? 'Check Specific ' . \App\Enums\FoodCategory::tryFrom($currentCategory)?->label() . ' for Blood Sugar Impact?' : 'Curious How This Meal Might Affect You?' }}
                    </h2>
                    <p class="text-slate-600 dark:text-slate-300 mb-6">
                        @if ($currentCategory)
                            Not sure which foods in the
                            {{ \App\Enums\FoodCategory::tryFrom($currentCategory)?->label() }} category might spike
                            your glucose? Use our AI-powered Spike Calculator to check any food's glycemic impact
                            instantly and discover safer alternatives.
                        @else
                            You should really try our Spike Calculator—it basically guesses how your specific meal and
                            portion size might change your numbers.
                        @endif
                    </p>
                    <a href="{{ route('spike-calculator') }}"
                        class="inline-flex items-center px-6 py-3 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors font-semibold shadow-lg hover:shadow-xl">
                        <svg class="size-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        {{ $currentCategory ? 'Check ' . \App\Enums\FoodCategory::tryFrom($currentCategory)?->label() . ' Foods' : 'Try the Spike Calculator' }}
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="bg-slate-50 dark:bg-slate-900 py-12 border-t border-slate-200 dark:border-slate-800"
        aria-labelledby="gi-guide-title">
        <div class="mx-auto max-w-4xl px-6">
            <h2 id="gi-guide-title" class="text-2xl font-bold text-slate-900 dark:text-white mb-6">
                Understanding Glycemic Index (GI) vs. Glycemic Load (GL)
            </h2>

            <div class="prose dark:prose-invert text-slate-600 dark:text-slate-400 max-w-none">
                <p class="mb-6 leading-relaxed">
                    When managing Type 2 Diabetes, it's crucial to distinguish between <strong>quality</strong> and
                    <strong>quantity</strong>.
                    While <strong>Glycemic Index (GI)</strong> measures how quickly a food raises blood sugar,
                    <strong>Glycemic Load (GL)</strong> tells the full story by factoring in portion size.
                </p>

                <div class="grid md:grid-cols-2 gap-8 mb-8">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Glycemic Index (Speed)
                        </h3>
                        <ul class="space-y-2 text-sm">
                            <li>
                                <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 mr-2"></span>
                                <strong>Low (0-55):</strong> Digested slowly. Best for consistency.
                            </li>
                            <li>
                                <span class="inline-block w-2 h-2 rounded-full bg-yellow-500 mr-2"></span>
                                <strong>Medium (56-69):</strong> Moderate impact.
                            </li>
                            <li>
                                <span class="inline-block w-2 h-2 rounded-full bg-red-500 mr-2"></span>
                                <strong>High (70+):</strong> Cause rapid spikes.
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-3">Glycemic Load (Impact)
                        </h3>
                        <ul class="space-y-2 text-sm">
                            <li>
                                <span class="inline-block w-2 h-2 rounded-full bg-emerald-500 mr-2"></span>
                                <strong>Low (&lt;10):</strong> Minimal blood sugar impact.
                            </li>
                            <li>
                                <span class="inline-block w-2 h-2 rounded-full bg-yellow-500 mr-2"></span>
                                <strong>Medium (11-19):</strong> Use portion control.
                            </li>
                            <li>
                                <span class="inline-block w-2 h-2 rounded-full bg-red-500 mr-2"></span>
                                <strong>High (20+):</strong> Significantly raises glucose.
                            </li>
                        </ul>
                    </div>
                </div>

                <div
                    class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700 mb-6 shadow-xs">
                    <h4 class="text-base font-bold text-slate-900 dark:text-white mb-2 flex items-center">
                        <svg class="size-5 text-amber-500 mr-2" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Watch Out for "Hidden Spikers"
                    </h4>
                    <p class="text-sm mb-0">
                        Some foods like <strong>Watermelon</strong> have a high GI (~72) but a very low GL (~5 per
                        serving) because they are mostly water. Conversely, <strong>Brown Rice</strong> is healthy but
                        can have a high GL if portions are too large.
                        For the best results, pair carbs with <strong>fiber, protein, and healthy fats</strong> to blunt
                        the spike.
                    </p>
                </div>

                <p
                    class="text-xs text-slate-500 dark:text-slate-500 border-t border-slate-200 dark:border-slate-800 pt-4 mt-8">
                    Data sources: University of Sydney GI Database and USDA FoodData Central. Consult your healthcare
                    provider before making significant diet changes.
                </p>
            </div>
        </div>
    </div>

    <x-footer />
</x-mini-app-layout>
