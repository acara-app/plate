@section('title', 'Open Source AI Nutritionist | Acara Plate')
@section('meta_description', 'Analyze your meals for glycemic impact. An open-source, AI-powered tool to understand how food affects your blood sugar and metabolic health.')
@section('meta_keywords', 'open source nutritionist, AI nutrition analysis, glycemic load calculator, metabolic health tool')
@section('canonical_url', strtok(url()->current(), '?'))
@section('og_image', asset('screenshots/og-ai-nutritionist.webp'))
@section('og_image_width', '1920')
@section('og_image_height', '1096')
@section('og_image_alt', 'AI Nutritionist analyzing oatmeal meal showing predicted glucose spike and recommendations')

@section('head')

<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "WebApplication",
    "name": "Acara Plate AI Nutritionist",
    "description": "Open source tool for analyzing the glycemic impact of meals and providing metabolic health guidance.",
    "applicationCategory": "HealthApplication",
    "operatingSystem": "All",
    "offers": {
        "@@type": "Offer",
        "price": "0",
        "priceCurrency": "USD"
    }
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
        {
            "@@type": "Question",
            "name": "How does the analysis engine work?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "The engine uses metabolic modeling trained on nutritional datasets and glycemic research. It analyzes the interaction between carbohydrates, fiber, protein, and fat to estimate how a specific food item will impact blood glucose levels."
            }
        },
        {
            "@@type": "Question",
            "name": "Is this tool really open source?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Yes. We believe health utilities should be transparent. You can view our entire codebase on GitHub, verify our privacy controls, and see exactly how we calculate nutritional values."
            }
        },
        {
            "@@type": "Question",
            "name": "How accurate is the glucose prediction?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "The tool uses USDA FoodData Central as its primary data source, which is the scientific gold standard for nutritional information. Glucose predictions are estimates based on established glycemic research, but individual responses can vary."
            }
        },
        {
            "@@type": "Question",
            "name": "Do I need to create an account to use this?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "No! You can analyze meals instantly without signing up. Create an account if you want to save your meal history and get recommendations over time."
            }
        },
        {
            "@@type": "Question",
            "name": "Can I analyze restaurant meals?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Yes. Simply describe what you ordered or paste the ingredient list. The AI will estimate the nutritional content and glycemic impact based on similar foods in our database."
            }
        }
    ]
}
</script>
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
        },
        {
            "@@type": "ListItem",
            "position": 2,
            "name": "AI Nutritionist"
        }
    ]
}
</script>
@endsection

<x-default-layout>
    <div class="bg-white">
        <header class="sticky top-0 z-50 w-full border-b border-slate-200 bg-white/80 backdrop-blur-md">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <a href="/" class="flex items-center gap-2 text-xl font-bold text-slate-900 transition-opacity hover:opacity-80">
                    <span class="text-2xl" role="img" aria-label="strawberry">🍓</span>
                    <span>Acara Plate</span>
                </a>
                <div class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition-all duration-200 hover:bg-slate-800">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="hidden items-center px-4 py-2 text-sm font-medium text-slate-600 transition-all duration-200 hover:text-slate-900 sm:inline-flex">
                            Sign in
                        </a>
                        <a href="{{ route('register') }}" class="inline-flex items-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition-all duration-200 hover:bg-slate-800">
                            Start for Free
                        </a>
                    @endauth
                </div>
            </div>
        </header>
        
        <section class="relative overflow-hidden pt-12 pb-16 sm:pt-20 sm:pb-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="lg:grid lg:grid-cols-12 lg:gap-16 items-center">
                    
                    <div class="lg:col-span-6 text-center lg:text-left">
                        <div class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-800 mb-6">
                            <svg class="h-4 w-4 text-slate-500" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
                            </svg>
                            Open Source Project
                        </div>
                        <h1 class="text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl">
                            An AI That Truly Understands You
                        </h1>
                        
                        <p class="mt-6 text-lg text-slate-600 leading-relaxed">
                            Meal guidance based on your goals, activity, and metabolic history. Tell Acara what you're eating—chat, paste a recipe, or upload a photo—and get recommendations that keep your glucose steady.
                        </p>

                        <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                            <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-6 py-3 text-base font-semibold text-white shadow-sm hover:bg-slate-800 transition-all">
                                Analyze Your First Meal
                                <span class="ml-2 text-slate-400">→</span>
                            </a>
                            <a href="https://github.com/acara-app/plate" target="_blank" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-6 py-3 text-base font-medium text-slate-700 hover:bg-slate-50 transition-all">
                                View Source Code
                            </a>
                        </div>
                    </div>

                    <div class="mt-12 lg:mt-0 lg:col-span-6">
                        <img 
                            src="{{ asset('screenshots/og-ai-nutritionist.webp') }}" 
                            alt="AI Nutritionist terminal interface analyzing oatmeal with banana and honey, showing ingredient breakdown with carbohydrate counts, predicted high glucose spike with glycemic load of 35, and recommendation to swap honey for blueberries and add chia seeds to reduce spike by 40 percent"
                            class="sr-only"
                            width="1200"
                            height="630"
                        >
                        
                        <div class="relative rounded-xl bg-slate-900 shadow-2xl ring-1 ring-white/10" aria-hidden="true">
                            <div class="flex items-center gap-2 border-b border-white/10 px-4 py-3 bg-white/5 rounded-t-xl">
                                <div class="flex gap-1.5">
                                    <div class="h-3 w-3 rounded-full bg-red-500"></div>
                                    <div class="h-3 w-3 rounded-full bg-yellow-500"></div>
                                    <div class="h-3 w-3 rounded-full bg-green-500"></div>
                                </div>
                                <div class="text-xs font-mono text-slate-400 ml-2">acara-ai — analysis</div>
                            </div>

                            <div class="p-6 font-mono text-sm space-y-4">
                                <div class="text-slate-400">
                                    <span class="text-rose-500">user@acara:~$</span> ask "I'm having oatmeal with banana and honey. Will this spike me?"
                                </div>
                                
                                <div class="space-y-2 border-l-2 border-slate-700 pl-4 py-2">
                                    <div class="text-emerald-400">✓ Ingredients identified</div>
                                    <div class="text-slate-300">
                                        • Oatmeal (Cup, cooked): <span class="text-yellow-400">27g Carbs</span><br>
                                        • Banana (Medium): <span class="text-yellow-400">27g Carbs</span><br>
                                        • Honey (1 tbsp): <span class="text-yellow-400">17g Carbs</span>
                                    </div>
                                </div>

                                <div class="bg-white/10 rounded p-3 text-slate-200">
                                    <span class="font-bold text-rose-400">⚠ PREDICTED SPIKE: HIGH</span><br>
                                    Total Glycemic Load: <span class="font-bold">35 (High)</span><br>
                                    <br>
                                    <span class="text-slate-400">Suggestion:</span> Swap instant oats for steel-cut, remove honey, and add 1 tbsp peanut butter to reduce spike by ~40%.
                                </div>
                                
                                <div class="animate-pulse text-slate-500">
                                    <span class="text-rose-500">user@acara:~$</span> <span class="inline-block w-2 h-4 bg-slate-500 align-middle"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-slate-50 py-16 sm:py-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900">Why calculate the spike?</h2>
                    <p class="mt-4 text-lg text-slate-600">
                        Blood sugar isn't just about sugar. It's about the complex interaction between fiber, protein, and carbohydrates.
                    </p>
                </div>

                <div class="grid gap-8 md:grid-cols-3">
                    {{-- Card 1 --}}
                    <div class="bg-white rounded-xl p-8 shadow-sm border border-slate-100">
                        <div class="w-12 h-12 bg-rose-100 rounded-lg flex items-center justify-center text-rose-600 mb-6">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Glycemic Load vs. Index</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">
                            Glycemic Index (GI) only tells half the story. We calculate Glycemic Load (GL), which accounts for portion size—a far more accurate predictor of real-world spikes.
                        </p>
                    </div>

                    <div class="bg-white rounded-xl p-8 shadow-sm border border-slate-100">
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center text-indigo-600 mb-6">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Transparent Data</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">
                            No "proprietary magic." Our food data comes from verified USDA databases and open scientific literature. You can audit our logic on GitHub.
                        </p>
                    </div>

                    <div class="bg-white rounded-xl p-8 shadow-sm border border-slate-100">
                        <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center text-emerald-600 mb-6">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Context Aware</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">
                            A banana affects a marathon runner differently than an office worker. The AI adjusts recommendations based on your activity context and history.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-16 sm:py-24 bg-white">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900">How it works</h2>
                    <p class="mt-4 text-lg text-slate-600">
                        Three steps to understand your food better
                    </p>
                </div>

                <div class="grid gap-8 md:grid-cols-3">
                    <div class="relative">
                        <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-slate-100 text-slate-900 font-bold text-xl mb-6">
                            01
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Conversational Interface</h3>
                        <p class="text-slate-600 leading-relaxed">
                            No strict forms or calorie counting. Just tell the AI what you're eating like you're texting a friend. It understands context, recipes, and restaurant menus.
                        </p>
                    </div>

                    <div class="relative">
                        <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-slate-100 text-slate-900 font-bold text-xl mb-6 border-2 border-rose-500">
                            02
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">AI Analysis</h3>
                        <p class="text-slate-600 leading-relaxed">
                            Our engine breaks down macronutrients, calculates glycemic load, and predicts metabolic impact based on scientific data.
                        </p>
                    </div>

                    <div class="relative">
                        <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-slate-100 text-slate-900 font-bold text-xl mb-6">
                            03
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Get Recommendations</h3>
                        <p class="text-slate-600 leading-relaxed">
                            Receive actionable suggestions to optimize your meal for stable energy and better metabolic health.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-slate-50 py-16 sm:py-24">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-slate-900">Common Questions</h2>
                </div>

                <div class="space-y-4">
                    <details class="group rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <summary class="flex cursor-pointer items-center justify-between font-semibold text-slate-900">
                            How does the analysis engine work?
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="mt-4 text-slate-600">
                            The engine simulates digestion. It calculates how the fiber, protein, and fats in your meal interact to slow down sugar absorption, giving you a realistic prediction of your blood sugar response.
                        </p>
                    </details>

                    <details class="group rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <summary class="flex cursor-pointer items-center justify-between font-semibold text-slate-900">
                            Is this tool really open source?
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="mt-4 text-slate-600">
                            Yes. We believe health utilities should be transparent. You can view our entire codebase on GitHub, verify our privacy controls, and see exactly how we calculate nutritional values. We welcome audits and contributions from the developer and health science communities.
                        </p>
                    </details>

                    <details class="group rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <summary class="flex cursor-pointer items-center justify-between font-semibold text-slate-900">
                            How is my privacy protected?
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="mt-4 text-slate-600">
                            We take a "Privacy-First" approach. We do not sell your data to third-party advertisers or insurance companies. Because our code is open source, these claims are verifiable by anyone. Your metabolic data is used solely to provide you with accurate analysis.
                        </p>
                    </details>

                    <details class="group rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <summary class="flex cursor-pointer items-center justify-between font-semibold text-slate-900">
                            Who is this for?
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="mt-4 text-slate-600">
                            Anyone interested in understanding how food affects their body. Whether you're managing diabetes, optimizing athletic performance, or simply want to make more informed food choices, this tool provides data-driven insights without the guesswork.
                        </p>
                    </details>
                </div>
            </div>
        </section>

        <section class="py-24 px-4 bg-white border-t border-slate-100">
            <div class="max-w-3xl mx-auto text-center">
                <h2 class="text-2xl font-bold text-slate-900 sm:text-3xl">
                    Part of the Open Source Health Stack
                </h2>
                <p class="mt-4 text-slate-600">
                    Acara Plate is built by developers and diabetics who believe health data should be accessible, not locked in a black box.
                </p>
                <div class="mt-8 flex justify-center">
                    <a href="{{ route('register') }}" class="text-base font-semibold text-rose-600 hover:text-rose-500 border-b-2 border-rose-100 hover:border-rose-500 transition-colors pb-1">
                        Try the tool for free →
                    </a>
                </div>
            </div>
        </section>

    </div>
    <x-footer />
</x-default-layout>