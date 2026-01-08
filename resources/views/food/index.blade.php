@section('title', 'Food Database - Glycemic Index & Nutrition for Diabetics | Acara Plate')
@section('meta_description', "Explore our comprehensive diabetic food list and glycemic index database. Find safe foods for your blood sugar, nutrition facts, and detailed insulin spike predictions.")
@section('meta_keywords', 'food database, glycemic index database, diabetes food list, nutrition facts, diabetic food guide, blood sugar friendly foods, low glycemic foods list')
@section('canonical_url', $canonicalUrl)

@section('head')
@if(request()->hasAny(['search', 'assessment', 'category']))
<meta name="robots" content="noindex, follow">
@endif

<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [{
        "@@type": "ListItem",
        "position": 1,
        "name": "Home",
        "item": "{{ url('/') }}"
    },{
        "@@type": "ListItem",
        "position": 2,
        "name": "Food Database",
        "item": "{{ route('food.index') }}"
    }]
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "CollectionPage",
    "name": "Food Database - Glycemic Index & Nutrition for Diabetics",
    "description": "Browse our comprehensive food database with glycemic index information, nutrition facts, and diabetes safety assessments.",
    "url": "{{ route('food.index') }}",
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
    "name": "Diabetic Food Database",
    "numberOfItems": {{ $foods->total() }},
    "itemListElement": [
        @foreach($foods as $food)
        {
            "@@type": "ListItem",
            "position": {{ $loop->iteration + (($foods->currentPage() - 1) * $foods->perPage()) }},
            "url": "{{ route('food.show', $food->slug) }}",
            "name": "{{ $food->display_name }}"
        }@unless($loop->last),@endunless
        @endforeach
    ]
}
</script>
@endsection

<x-mini-app-layout>
    <div class="mx-auto my-16 max-w-7xl px-6 lg:px-8">
        {{-- Breadcrumb --}}
        {{-- Breadcrumb --}}
        <nav aria-label="Breadcrumb" class="-mt-10 mb-12 relative z-50">
            <a
                href="{{ url('/') }}"
                class="flex items-center dark:text-slate-400 text-slate-600 hover:underline"
                wire:navigate
            >
                <x-icons.chevron-left class="size-4" />
                <span>Home</span>
            </a>
        </nav>

        {{-- Hero Section --}}
        <div class="mt-6">
            <h1 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-4">
                Diabetic Food Database & Glycemic Index
            </h1>
            <p class="text-lg text-slate-600 dark:text-slate-300 mb-8 max-w-3xl">
                So, what can you actually eat? We’ve built a USDA-verified database containing proper nutrition info and safety checks—so you can instantly spot what triggers a spike without all the guesswork.
            </p>

            {{-- ======================= --}}
            {{-- SEARCH & FILTER BAR --}}
            {{-- ======================= --}}
            <div class="mb-8 p-6 bg-white/80 dark:bg-slate-800/80 backdrop-blur-sm rounded-2xl shadow-lg border border-slate-200/50 dark:border-slate-700/50">
                <form method="GET" action="{{ route('food.index') }}" class="space-y-4">
                    {{-- Search Input --}}
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="size-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input
                            type="text"
                            name="search"
                            value="{{ $currentSearch }}"
                            placeholder="What are you thinking of eating? (e.g., Brown Rice, Apple)"
                            class="w-full pl-12 pr-4 py-3 text-lg bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary dark:text-white placeholder-slate-400 transition-all"
                        />
                    </div>

                    {{-- Filter Row --}}
                    <div class="flex flex-wrap gap-4 items-center">
                        {{-- Glycemic Impact Filter --}}
                        <div class="flex-1 min-w-50">
                            <label class="block text-sm font-medium text-slate-600 dark:text-slate-400 mb-2">Glycemic Impact</label>
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
                        @if($categories->isNotEmpty())
                        <div class="min-w-45">
                            <label class="block text-sm font-medium text-slate-600 dark:text-slate-400 mb-2">Category</label>
                            <select 
                                name="category" 
                                onchange="this.form.submit()"
                                class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary dark:text-white transition-all"
                            >
                                <option value="">All Categories</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->value }}" {{ $currentCategory === $cat->value ? 'selected' : '' }}>
                                        {{ $cat->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        {{-- Clear Filters --}}
                        @if($currentSearch || $currentAssessment || $currentCategory)
                        <div class="flex items-end">
                            <a 
                                href="{{ route('food.index') }}" 
                                class="px-4 py-2.5 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white flex items-center gap-1.5 transition-colors"
                            >
                                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Clear Filters
                            </a>
                        </div>
                        @endif
                    </div>

                    {{-- Hidden fields to preserve other filters when clicking assessment buttons --}}
                    @if($currentSearch)
                    <input type="hidden" name="search" value="{{ $currentSearch }}" />
                    @endif
                    @if($currentCategory)
                    <input type="hidden" name="category" value="{{ $currentCategory }}" />
                    @endif
                </form>
            </div>

            {{-- ======================= --}}
            {{-- AI MISSING FOOD CTA --}}
            {{-- ======================= --}}
            <div class="mb-8 p-6 bg-linear-to-r from-violet-500/10 via-purple-500/10 to-fuchsia-500/10 dark:from-violet-500/20 dark:via-purple-500/20 dark:to-fuchsia-500/20 rounded-2xl border border-violet-200/50 dark:border-violet-700/50">
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                    <div class="shrink-0 w-14 h-14 bg-linear-to-br from-violet-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="size-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-1">
                            Still Can't Find It?
                        </h3>
                        <p class="text-slate-600 dark:text-slate-300 text-sm">
                            AI tool can actually take a look at pretty much any food you're curious about—whether it's from a restaurant or just some brand you like.
                        </p>
                    </div>
                    <button 
                        disabled
                        class="shrink-0 px-5 py-2.5 bg-slate-300 dark:bg-slate-600 text-slate-500 dark:text-slate-400 rounded-xl font-medium cursor-not-allowed flex items-center gap-2"
                    >
                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        Coming Soon
                    </button>
                </div>
            </div>

            {{-- Result Count --}}
            @if($foods->total() > 0)
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6">
                Showing {{ $foods->firstItem() }}–{{ $foods->lastItem() }} of {{ $foods->total() }} foods
                @if($currentSearch || $currentAssessment || $currentCategory)
                    <span class="text-primary">(filtered)</span>
                @endif
            </p>
            @endif

            {{-- ======================= --}}
            {{-- FOOD GRID --}}
            {{-- ======================= --}}
            @if($foodsByCategory && !$currentSearch && !$currentAssessment && !$currentCategory)
                {{-- Grouped by Category View --}}
                @foreach($foodsByCategory as $categoryValue => $categoryFoods)
                    @php
                        $categoryEnum = \App\Enums\FoodCategory::tryFrom($categoryValue);
                        $categoryLabel = $categoryEnum?->label() ?? 'Uncategorized';
                    @endphp
                    <div class="mb-12">
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-3">
                            <span class="w-1.5 h-8 bg-linear-to-b from-primary to-primary/60 rounded-full"></span>
                            {{ $categoryLabel }}
                            <span class="text-sm font-normal text-slate-400">({{ $categoryCounts[$categoryValue] ?? $categoryFoods->count() }})</span>
                        </h2>
                        <div class="grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                            @foreach($categoryFoods as $food)
                                @include('food._card', ['food' => $food])
                            @endforeach
                        </div>
                        @php
                            $totalInCategory = $categoryCounts[$categoryValue] ?? $categoryFoods->count();
                            $displayedCount = $categoryFoods->count();
                        @endphp
                        @if($totalInCategory > $displayedCount)
                            <div class="mt-6 text-center">
                                <a 
                                    href="{{ route('food.index', ['category' => $categoryValue]) }}"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-primary hover:text-primary/80 bg-primary/5 hover:bg-primary/10 rounded-xl transition-all"
                                >
                                    View all {{ $totalInCategory }} {{ $categoryLabel }} foods
                                    <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
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
                            <svg class="mx-auto size-16 text-slate-300 dark:text-slate-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="text-lg font-medium text-slate-900 dark:text-white mb-2">Honestly, We Couldn't Find That</h3>
                            <p class="text-slate-500 dark:text-slate-400 mb-4">
                                @if($currentSearch || $currentAssessment || $currentCategory)
                                    Maybe try changing your search or filters just a little bit?
                                @else
                                    We're actually adding new stuff all the time, so you can check back later.
                                @endif
                            </p>
                            @if($currentSearch || $currentAssessment || $currentCategory)
                                <a href="{{ route('food.index') }}" class="inline-flex items-center text-primary hover:underline">
                                    <svg class="size-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                    </svg>
                                    Just Show Everything
                                </a>
                            @endif
                        </div>
                    @endforelse
                </div>
            @endif

            {{-- Pagination (only shown for filtered/paginated view, not grouped category view) --}}
            @if($foods->hasPages() && !$foodsByCategory)
                <div class="mt-8">
                    {{ $foods->links() }}
                </div>
            @endif

            {{-- ======================= --}}
            {{-- POPULAR COMPARISONS --}}
            {{-- ======================= --}}
            <div class="mt-16 pt-10 border-t border-slate-200 dark:border-slate-700">
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6">
                    Which One Is Actually Better?
                </h2>
                <p class="text-slate-600 dark:text-slate-400 mb-6 max-w-2xl">
                    It's kinda tricky knowing which choice will spike your blood sugar more, isn't it? We've put these comparisons together so you can see which one is actually safer for you.
                </p>
                <div class="flex flex-wrap gap-3">
                    @foreach($comparisons as $comparison)
                        <a 
                            href="{{ route('spike-calculator', ['compare' => $comparison['name1'] . ' vs ' . $comparison['name2']]) }}"
                            class="group px-4 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl hover:border-emerald-500 dark:hover:border-emerald-500 hover:shadow-md transition-all flex items-center gap-2"
                        >
                            <span class="font-medium text-slate-700 dark:text-slate-200 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">{{ $comparison['name1'] }}</span>
                            <span class="text-slate-400 text-sm">vs</span>
                            <span class="font-medium text-slate-700 dark:text-slate-200 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">{{ $comparison['name2'] }}</span>
                            <svg class="size-4 text-slate-400 group-hover:text-emerald-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- ======================= --}}
            {{-- CTA Section --}}
            {{-- ======================= --}}
            <div class="mt-16 bg-linear-to-r from-primary/10 to-primary/5 dark:from-primary/20 dark:to-primary/10 rounded-2xl p-8">
                <div class="max-w-2xl">
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">
                        Curious How This Meal Might Affect You?
                    </h2>
                    <p class="text-slate-600 dark:text-slate-300 mb-6">
                        You should really try our Spike Calculator—it basically guesses how your specific meal and portion size might change your numbers.
                    </p>
                    <a
                        href="{{ route('spike-calculator') }}"
                        class="inline-flex items-center px-6 py-3 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors font-semibold shadow-lg hover:shadow-xl"
                    >
                        <svg class="size-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        Try the Spike Calculator
                    </a>
                </div>
            </div>
        </div>
    </div>
    <x-footer />
</x-mini-app-layout>
