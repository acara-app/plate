@section('title', $content->meta_title)
@section('meta_description', $content->meta_description)
@section('meta_keywords', 'glycemic index ' . $displayName . ', ' . $displayName . ' diabetes, ' . $displayName . ' blood sugar, ' . $displayName . ' nutrition, can diabetics eat ' . $displayName . ', ' . $displayName . ' carbs, ' . $displayName . ' sugar content')

@section('head')
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
    },{
        "@@type": "ListItem",
        "position": 3,
        "name": "{{ $displayName }}",
        "item": "{{ url()->current() }}"
    }]
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Article",
    "headline": "{{ $content->title }}",
    "description": "{{ $content->meta_description }}",
    "image": "{{ $content->image_url ?? asset('banner-acara-plate.webp') }}",
    "author": {
        "@@type": "Organization",
        "name": "Acara Plate",
        "url": "{{ url('/') }}"
    },
    "publisher": {
        "@@type": "Organization",
        "name": "Acara Plate",
        "logo": {
            "@@type": "ImageObject",
            "url": "{{ asset('apple-touch-icon/apple-touch-icon-180x180.png') }}"
        }
    },
    "datePublished": "{{ $content->created_at->toIso8601String() }}",
    "dateModified": "{{ $content->updated_at->toIso8601String() }}"
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "NutritionInformation",
    "name": "{{ $displayName }} Nutrition Facts (per 100g)",
    "calories": "{{ $nutrition['calories'] ?? 0 }} calories",
    "carbohydrateContent": "{{ $nutrition['carbs'] ?? 0 }} g",
    "proteinContent": "{{ $nutrition['protein'] ?? 0 }} g",
    "fatContent": "{{ $nutrition['fat'] ?? 0 }} g",
    "fiberContent": "{{ $nutrition['fiber'] ?? 0 }} g",
    "sugarContent": "{{ $nutrition['sugar'] ?? 0 }} g"
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
        {
            "@@type": "Question",
            "name": "Is {{ $displayName }} good for diabetics?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "{{ $diabeticInsight }}"
            }
        },
        {
            "@@type": "Question",
            "name": "What is the glycemic impact of {{ $displayName }}?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "{{ $displayName }} has a {{ $glycemicAssessment }} glycemic impact{{ $glycemicLoad ? ' with a ' . $glycemicLoad . ' glycemic load (GL)' : '' }}. Per 100g, it contains {{ $nutrition['carbs'] ?? 0 }}g of carbohydrates and {{ $nutrition['sugar'] ?? 0 }}g of sugar, with {{ $nutrition['fiber'] ?? 0 }}g of fiber to help moderate blood sugar response."
            }
        },
        {
            "@@type": "Question",
            "name": "What is the glycemic load of {{ $displayName }}?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "{{ $displayName }} has a {{ $glycemicLoad }} glycemic load (GL). Per 100g serving, it contains {{ $nutrition['carbs'] ?? 0 }}g of carbohydrates with {{ $nutrition['fiber'] ?? 0 }}g of fiber, resulting in {{ number_format(($nutrition['carbs'] ?? 0) - ($nutrition['fiber'] ?? 0), 1) }}g of net carbs. Glycemic Load accounts for both the quality (GI) and quantity of carbohydrates, making it a more accurate predictor of blood sugar response than GI alone. Low GL is 0-10, Medium is 11-19, and High is 20+."
            }
        },
        {
            "@@type": "Question",
            "name": "How many carbs are in {{ $displayName }}?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "{{ $displayName }} contains {{ $nutrition['carbs'] ?? 0 }}g of carbohydrates per 100g serving. This includes {{ $nutrition['sugar'] ?? 0 }}g of sugar and {{ $nutrition['fiber'] ?? 0 }}g of dietary fiber."
            }
        }
    ]
}
</script>
@endsection

<x-mini-app-layout>
    <div class="mx-auto my-16 max-w-4xl px-6 lg:px-8">
        <a
            href="{{ url()->previous() === request()->url() ? route('food.index') : url()->previous() }}"
            class="-mt-10 mb-12 flex items-center dark:text-slate-400 text-slate-600 hover:underline z-50 relative"
            wire:navigate
        >
            <x-icons.chevron-left class="size-4" />
            <span>Back</span>
        </a>

        <article class="mt-6">
            {{-- Hero Section with Image --}}
            @if($content->image_url)
            <div class="mb-8 rounded-2xl overflow-hidden shadow-lg">
                <img 
                    src="{{ $content->image_url }}" 
                    alt="{{ $displayName }} nutritional information infographic"
                    class="w-full h-auto"
                    loading="eager"
                />
            </div>
            @endif

            {{-- H1 Title --}}
            <h1 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-6">
                {{ $content->title }}
            </h1>

            {{-- Glycemic Assessment Badge --}}
            <div class="mb-8">
                @php
                    $badgeColors = [
                        'low' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 border-green-200 dark:border-green-800',
                        'medium' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 border-yellow-200 dark:border-yellow-800',
                        'high' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 border-red-200 dark:border-red-800',
                    ];
                    $badgeColor = $badgeColors[$glycemicAssessment] ?? $badgeColors['medium'];
                @endphp
                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold border {{ $badgeColor }}">
                    <span class="mr-2">
                        @if($glycemicAssessment === 'low')
                            ✓
                        @elseif($glycemicAssessment === 'medium')
                            ⚠
                        @else
                            ⚡
                        @endif
                    </span>
                    {{ ucfirst($glycemicAssessment) }} Glycemic Impact
                </span>
                @if($glycemicLoad)
                @php
                    $glBadgeColors = [
                        'low' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200 border-emerald-200 dark:border-emerald-800',
                        'medium' => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200 border-amber-200 dark:border-amber-800',
                        'high' => 'bg-rose-100 text-rose-800 dark:bg-rose-900 dark:text-rose-200 border-rose-200 dark:border-rose-800',
                    ];
                    $glBadgeColor = $glBadgeColors[$glycemicLoad] ?? $glBadgeColors['medium'];
                @endphp
                <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold border {{ $glBadgeColor }} ml-2">
                    <span class="mr-2">📊</span>
                    {{ ucfirst($glycemicLoad) }} GL
                </span>
                @endif
            </div>

            {{-- Diabetic Insight --}}
            <div class="prose prose-slate dark:prose-invert max-w-none mb-10">
                <div class="bg-blue-50 dark:bg-blue-950 border-l-4 border-blue-500 p-6 rounded-r-lg">
                    <p class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-2">
                        💡 Diabetic Safety Assessment
                    </p>
                    <p class="text-blue-800 dark:text-blue-200 mb-0">
                        {{ $diabeticInsight }}
                    </p>
                </div>
            </div>

            {{-- Nutrition Facts Table --}}
            <div class="mb-10">
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">
                    Nutrition Facts (per 100g)
                </h2>
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg overflow-hidden border border-slate-200 dark:border-slate-700">
                    <table class="w-full">
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                <td class="px-6 py-4 text-slate-600 dark:text-slate-400 font-medium">Calories</td>
                                <td class="px-6 py-4 text-right text-slate-900 dark:text-white font-semibold">{{ number_format($nutrition['calories'] ?? 0, 0) }} kcal</td>
                            </tr>
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                <td class="px-6 py-4 text-slate-600 dark:text-slate-400 font-medium">Protein</td>
                                <td class="px-6 py-4 text-right text-slate-900 dark:text-white font-semibold">{{ number_format($nutrition['protein'] ?? 0, 1) }}g</td>
                            </tr>
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                <td class="px-6 py-4 text-slate-600 dark:text-slate-400 font-medium">Carbohydrates</td>
                                <td class="px-6 py-4 text-right text-slate-900 dark:text-white font-semibold">{{ number_format($nutrition['carbs'] ?? 0, 1) }}g</td>
                            </tr>
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                <td class="px-6 py-4 text-slate-600 dark:text-slate-400 font-medium">
                                    <span class="ml-4">↳ Sugar</span>
                                </td>
                                <td class="px-6 py-4 text-right text-slate-900 dark:text-white font-semibold">{{ number_format($nutrition['sugar'] ?? 0, 1) }}g</td>
                            </tr>
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                <td class="px-6 py-4 text-slate-600 dark:text-slate-400 font-medium">
                                    <span class="ml-4">↳ Fiber</span>
                                </td>
                                <td class="px-6 py-4 text-right text-slate-900 dark:text-white font-semibold">{{ number_format($nutrition['fiber'] ?? 0, 1) }}g</td>
                            </tr>
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                <td class="px-6 py-4 text-slate-600 dark:text-slate-400 font-medium">Fat</td>
                                <td class="px-6 py-4 text-right text-slate-900 dark:text-white font-semibold">{{ number_format($nutrition['fat'] ?? 0, 1) }}g</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">
                    Source: USDA FoodData Central
                </p>
            </div>

            {{-- Compare With Section (SEO Cross-Links) --}}
            @if(isset($comparisonLinks) && count($comparisonLinks) > 0)
            <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-6 mb-10">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
                    Compare With Similar Foods
                </h2>
                <div class="space-y-3">
                    @foreach($comparisonLinks as $link)
                        @if($link['content'])
                        <p class="text-slate-600 dark:text-slate-300">
                            <x-food-link :slug="$link['slug']" :anchor="$link['anchor']" />
                        </p>
                        @endif
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Related Foods Section --}}
            @if(isset($relatedFoods) && $relatedFoods->count() > 0)
            <div class="mb-10">
                <h2 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
                    More {{ $content->category?->label() ?? 'Foods' }} to Explore
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    @foreach($relatedFoods as $relatedFood)
                        @include('food._card', ['food' => $relatedFood])
                    @endforeach
                </div>
            </div>
            @endif

            {{-- CTA Section --}}
            <div class="bg-linear-to-r from-primary/10 to-primary/5 dark:from-primary/20 dark:to-primary/10 rounded-2xl p-8 mb-10">
                <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">
                    Calculate Your Glucose Spike for {{ $displayName }}
                </h2>
                <p class="text-slate-600 dark:text-slate-300 mb-6">
                    Want to know exactly how {{ $displayName }} will affect your blood sugar based on your portion size and meal combination? Try our free Spike Calculator.
                </p>
                <a
                    href="{{ route('spike-calculator') }}"
                    class="inline-flex items-center px-8 py-4 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors font-semibold text-lg shadow-lg hover:shadow-xl"
                >
                    <svg class="size-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    Calculate My Spike →
                </a>
            </div>

            {{-- Additional Resources --}}
            <div class="border-t border-slate-200 dark:border-slate-700 pt-10">
                <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-6">
                    More Diabetes Tools
                </h2>
                <div class="grid md:grid-cols-2 gap-4">
                    <a href="{{ route('home') }}" class="group flex items-center p-4 bg-linear-to-r from-primary/5 to-primary/10 dark:from-primary/10 dark:to-primary/20 rounded-lg hover:from-primary/10 hover:to-primary/15 dark:hover:from-primary/15 dark:hover:to-primary/25 transition-all border border-primary/20">
                        <div class="shrink-0 w-12 h-12 bg-primary/20 rounded-lg flex items-center justify-center mr-4">
                            <svg class="size-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-900 dark:text-white group-hover:text-primary transition-colors">Meet Your AI Nutritionist</h3>
                            <p class="text-sm text-slate-600 dark:text-slate-400">Love {{ $displayName }}? Get a 7-day meal plan that fits it safely into your carb limit.</p>
                        </div>
                    </a>
                    <a href="{{ route('diabetes-log-book-info') }}" class="group flex items-center p-4 bg-slate-50 dark:bg-slate-800 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                        <div class="shrink-0 w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center mr-4">
                            <svg class="size-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-900 dark:text-white group-hover:text-primary transition-colors">Smart Glucose Tracker</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Visualize your spikes. Predict your A1C. Export for your doctor.</p>
                        </div>
                    </a>
                </div>
            </div>

            {{-- Medical Disclaimer --}}
            <div class="mt-10 bg-slate-50 dark:bg-slate-800 p-6 rounded-lg">
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-0">
                    <strong>Disclaimer:</strong> This information is for educational purposes only and should not replace professional medical advice. Always consult with your healthcare provider or registered dietitian before making changes to your diet, especially if you have diabetes or other health conditions.
                </p>
            </div>
        </article>
    </div>
</x-mini-app-layout>
