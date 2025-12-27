@section('title', 'AI Food Photo Calorie Counter | Snap & Track Macros for Free')
@section('meta_description', 'Instantly analyze your meal\'s calories and macros by snapping a photo. Free AI food scanner for easy diabetes & nutrition tracking. Try it now!')
@section('meta_keywords', 'food photo calorie counter, snap to track calories, AI food recognition, meal photo analyzer, instant macro breakdown, calorie tracking app, food image analysis, nutrition scanner')

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
        "name": "Food Photo Analyzer",
        "item": "{{ url()->current() }}"
    }]
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
        {
            "@@type": "Question",
            "name": "How does the food photo analyzer work?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "The tool looks at your photo to find food items. It guesses how much food is there. Then, it calculates the calories, protein, carbs, and fat for you."
            }
        },
        {
            "@@type": "Question",
            "name": "How accurate is the calorie estimation from photos?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Estimates work best when the photo is clear. Lighting matters. If we can see the food clearly, the numbers will be more accurate. The confidence score tells you how sure we are."
            }
        },
        {
            "@@type": "Question",
            "name": "What types of food can the AI recognize?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "We recognize most common foods. This includes fruits, vegetables, meats, and grains. Snacks and drinks work too. Make sure the food is easy to see."
            }
        }
    ]
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "WebApplication",
    "name": "Snap to Track - Food Photo Calorie Counter",
    "description": "Analyze food photos to get instant calorie and macro breakdown.",
    "url": "{{ url()->current() }}",
    "applicationCategory": "HealthApplication",
    "operatingSystem": "Any",
    "offers": {
        "@@type": "Offer",
        "price": "0",
        "priceCurrency": "USD"
    },
    "author": {
        "@@type": "Organization",
        "name": "Acara Plate",
        "url": "{{ url('/') }}"
    },
    "aggregateRating": {
        "@@type": "AggregateRating",
        "ratingValue": "4.9",
        "ratingCount": "120"
    }
}
</script>
{{-- Speakable Structured Data for Voice Search --}}
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "WebPage",
    "name": "Free Food Photo Analyzer",
    "speakable": {
        "@@type": "SpeakableSpecification",
        "cssSelector": [".speakable-intro", ".speakable-how-it-works"]
    },
    "url": "{{ url()->current() }}"
}
</script>
@endsection

<div
    class="relative flex min-h-screen flex-col items-center overflow-hidden bg-linear-to-br from-slate-50 via-white to-orange-50 p-4 text-slate-900 lg:justify-center lg:p-8 dark:from-slate-950 dark:via-slate-900 dark:to-orange-950 dark:text-slate-50"
>
    {{-- Animated background elements --}}
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 overflow-hidden">
        <div class="absolute -left-4 top-0 h-72 w-72 animate-pulse rounded-full bg-orange-300/20 blur-3xl dark:bg-orange-500/10"></div>
        <div class="absolute -right-4 bottom-0 h-96 w-96 animate-pulse rounded-full bg-amber-300/20 blur-3xl dark:bg-amber-500/10"></div>
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
    <main class="relative z-10 w-full max-w-md space-y-6 rounded-3xl bg-white p-6 shadow-xl shadow-orange-500/10 dark:bg-slate-800 dark:shadow-orange-900/20">

        {{-- Header Section --}}
        <div class="text-center speakable-intro">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-orange-100 text-2xl dark:bg-orange-900/50">📸</div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Snap to Track: AI Food Calorie Counter</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Instant macro breakdown with AI</p>
        </div>

        {{-- Upload Section --}}
        <form wire:submit="analyze" class="space-y-4">
            @if (!$photo)
                {{-- Upload Area --}}
                <div class="relative">
                    <input 
                        type="file" 
                        wire:model="photo"
                        accept="image/*"
                        capture="environment"
                        class="hidden"
                        id="photo-upload"
                        @disabled($loading)
                    >
                    <label 
                        for="photo-upload"
                        class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 p-8 transition-colors hover:border-orange-500 hover:bg-orange-50/50 dark:border-slate-600 dark:bg-slate-900 dark:hover:border-orange-500 dark:hover:bg-orange-900/20"
                    >
                        <div class="mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-900/50">
                            <svg class="h-8 w-8 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Tap to take photo or upload</span>
                        <span class="mt-1 text-xs text-slate-500 dark:text-slate-400">JPG, PNG up to 10MB</span>
                    </label>
                </div>
            @else
                {{-- Photo Preview --}}
                <div class="relative overflow-hidden rounded-xl">
                    <img 
                        src="{{ $photo->temporaryUrl() }}" 
                        alt="Food photo preview" 
                        class="h-48 w-full object-cover"
                    >
                    <button 
                        type="button"
                        wire:click="clearPhoto"
                        class="absolute right-2 top-2 rounded-full bg-slate-900/70 p-2 text-white transition-colors hover:bg-slate-900"
                        title="Remove photo"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Analyze Button --}}
                <button 
                    type="submit"
                    class="w-full rounded-xl bg-orange-600 py-4 text-center font-bold text-white transition-all hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-50"
                    @disabled($loading)
                >
                    <span wire:loading.remove wire:target="analyze" class="flex items-center justify-center gap-2">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                        Analyze Food
                    </span>
                    <span wire:loading wire:target="analyze" class="flex items-center justify-center gap-2">
                        <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Analyzing your meal...
                    </span>
                </button>
            @endif

            @if (App::environment(['production', 'testing']))
                <div class="flex justify-center">
                    <x-turnstile wire:model="turnstileToken" data-theme="auto" />
                </div>
            @endif

            @error('photo')
                <p class="text-center text-sm text-red-500">{{ $message }}</p>
            @enderror
        </form>

        {{-- Loading indicator for file upload --}}
        <div wire:loading wire:target="photo" class="text-center">
            <div class="inline-flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Uploading photo...
            </div>
        </div>

        {{-- Error Message --}}
        @if ($error)
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-400">
                <p>{{ $error }}</p>
            </div>
        @endif

        {{-- Results Section --}}
        @if ($result)
            <div class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-800">
                
                {{-- Total Macros Header --}}
                <div class="bg-slate-50 p-6 dark:bg-slate-800/50">
                    <div class="mb-3 flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Nutrition</span>
                        <span class="rounded-full bg-orange-100 px-2 py-0.5 text-xs font-bold text-orange-700 dark:bg-orange-900/50 dark:text-orange-300">
                            {{ $result['confidence'] }}% confident
                        </span>
                    </div>
                    
                    {{-- Calorie Display --}}
                    <div class="mb-4 text-center">
                        <span class="text-5xl font-black text-slate-900 dark:text-white">{{ number_format($result['totalCalories'], 0) }}</span>
                        <span class="ml-1 text-lg font-medium text-slate-400">kcal</span>
                    </div>

                    {{-- Macro Bars --}}
                    <div class="grid grid-cols-3 gap-4">
                        {{-- Protein --}}
                        <div class="text-center">
                            <div class="mx-auto mb-2 h-2 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                <div class="h-full rounded-full bg-blue-500" style="width: {{ min(100, ($result['totalProtein'] / max(1, $result['totalProtein'] + $result['totalCarbs'] + $result['totalFat'])) * 100) }}%"></div>
                            </div>
                            <span class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ number_format($result['totalProtein'], 1) }}g</span>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Protein</p>
                        </div>
                        {{-- Carbs --}}
                        <div class="text-center">
                            <div class="mx-auto mb-2 h-2 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                <div class="h-full rounded-full bg-amber-500" style="width: {{ min(100, ($result['totalCarbs'] / max(1, $result['totalProtein'] + $result['totalCarbs'] + $result['totalFat'])) * 100) }}%"></div>
                            </div>
                            <span class="text-lg font-bold text-amber-600 dark:text-amber-400">{{ number_format($result['totalCarbs'], 1) }}g</span>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Carbs</p>
                        </div>
                        {{-- Fat --}}
                        <div class="text-center">
                            <div class="mx-auto mb-2 h-2 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                <div class="h-full rounded-full bg-rose-500" style="width: {{ min(100, ($result['totalFat'] / max(1, $result['totalProtein'] + $result['totalCarbs'] + $result['totalFat'])) * 100) }}%"></div>
                            </div>
                            <span class="text-lg font-bold text-rose-600 dark:text-rose-400">{{ number_format($result['totalFat'], 1) }}g</span>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Fat</p>
                        </div>
                    </div>
                </div>

                {{-- Individual Items --}}
                @if (count($result['items']) > 0)
                    <div class="border-t border-slate-100 p-4 dark:border-slate-700">
                        <h3 class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-400">Food Items Detected</h3>
                        <div class="space-y-3">
                            @foreach ($result['items'] as $item)
                                <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-900/50">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <h4 class="font-semibold text-slate-900 dark:text-white">{{ $item['name'] }}</h4>
                                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $item['portion'] }}</p>
                                        </div>
                                        <span class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ number_format($item['calories'], 0) }} kcal</span>
                                    </div>
                                    <div class="mt-2 flex gap-3 text-xs">
                                        <span class="text-blue-600 dark:text-blue-400">P: {{ number_format($item['protein'], 1) }}g</span>
                                        <span class="text-amber-600 dark:text-amber-400">C: {{ number_format($item['carbs'], 1) }}g</span>
                                        <span class="text-rose-600 dark:text-rose-400">F: {{ number_format($item['fat'], 1) }}g</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                
                {{-- CTA Button --}}
                <div class="border-t border-slate-100 p-4 dark:border-slate-700">
                    <a 
                        href="{{ route('register') }}"
                        class="block w-full rounded-xl bg-slate-900 py-3 text-center text-sm font-bold text-white transition-transform hover:scale-[1.02] dark:bg-white dark:text-slate-900"
                    >
                        Start tracking your meals →
                    </a>
                </div>
            </div>

            {{-- Try Another Photo --}}
            <button 
                type="button"
                wire:click="clearPhoto"
                class="w-full rounded-xl border-2 border-slate-200 py-3 text-center text-sm font-medium text-slate-600 transition-colors hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-400 dark:hover:border-slate-600 dark:hover:bg-slate-800"
            >
                Analyze another photo
            </button>
        @endif

        {{-- Empty State Tips --}}
        @if (!$result && !$loading && !$error && !$photo)
            <div class="rounded-xl border border-orange-200 bg-orange-50 p-4 dark:border-orange-900/50 dark:bg-orange-900/20">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-lg">💡</span>
                    <span class="text-xs font-bold uppercase tracking-wider text-orange-700 dark:text-orange-400">Tips for best results</span>
                </div>
                <ul class="space-y-1 text-sm text-orange-800 dark:text-orange-200">
                    <li>• Take photo in good lighting</li>
                    <li>• Make sure all food is visible</li>
                    <li>• Capture from directly above</li>
                    <li>• Include a reference for scale (optional)</li>
                </ul>
            </div>
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
                    <p class="speakable-how-it-works">The tool looks at your photo to find food items. It guesses how much food is there. Then, it calculates the calories, protein, carbs, and fat for you.</p>
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
                Create Free Meal Plan
            </a>
        </div>
    </section>

    {{-- More Free Tools --}}
    <section class="relative z-10 mt-8 w-full max-w-md">
        <h2 class="mb-4 text-center text-lg font-bold text-slate-900 dark:text-white">
            More Free Tools
        </h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <a href="{{ route('spike-calculator') }}" class="group flex flex-col items-center rounded-xl bg-white p-4 text-center shadow-sm transition-all hover:shadow-md dark:bg-slate-800">
                <span class="mb-2 text-2xl">⚡️</span>
                <h3 class="font-bold text-slate-900 dark:text-white">Spike Calculator</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Check if foods will spike your blood sugar</p>
            </a>
            <a href="{{ route('diabetes-log-book-info') }}" class="group flex flex-col items-center rounded-xl bg-white p-4 text-center shadow-sm transition-all hover:shadow-md dark:bg-slate-800">
                <span class="mb-2 text-2xl">📖</span>
                <h3 class="font-bold text-slate-900 dark:text-white">Diabetes Log Book</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Free printable log book to track your levels</p>
            </a>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="relative z-10 mt-6 text-center text-xs text-slate-500 dark:text-slate-400">
        <p>
            <strong>Disclaimer:</strong> AI estimates are for guidance only, not medical advice.
        </p>
        <p class="mt-2">
            <a href="{{ route('home') }}" class="underline hover:text-orange-600">Back to Home</a>
            ·
            <a href="{{ route('register') }}" class="underline hover:text-orange-600">Create Free Account</a>
        </p>
    </footer>
</div>
