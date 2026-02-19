@section('title', 'AI Nutritionist for Blood Sugar | Acara Plate')
@section('meta_description', 'The future of metabolic health is here. Instant, AI-driven insights to optimize your blood sugar and energy. Your personal health intelligence.')
@section('meta_keywords', 'blood sugar app, glucose tracker, AI nutritionist, smart meal planner, diabetes friendly recipes')
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
            "name": "Is this a replacement for medical care?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "No. This is a tool for insight and education, not a medical device. It doesn't diagnose conditions or prescribe treatments. Always work with your healthcare provider for diabetes management."
            }
        },
        {
            "@@type": "Question",
            "name": "How does this help with diabetes management?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "By helping you understand how different foods impact your blood sugar, you can make more informed choices. Many users find it helpful for identifying triggers and planning meals that keep their glucose more stable."
            }
        },
        {
            "@@type": "Question",
            "name": "What's the difference between this and a glucose monitor?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "A continuous glucose monitor (CGM) tells you what happened after you ate. This tool helps you predict what might happen before you eat. Think of it as a planning tool versus a feedback tool—both are useful."
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
                            Open Science & Source
                        </div>
                        <h1 class="text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl">
                            I Ate "Healthy" Oatmeal Every Morning For a Year. My Glucose Told a Different Story.
                        </h1>
                        
                        <p class="mt-6 text-lg text-slate-600 leading-relaxed">
                            Turns out, the banana-and-honey "health" bowl I loved was spiking my blood sugar through the roof. That's the thing about nutrition—everyone's body responds differently. The tool I wish I'd had? Something that could look at <em>my</em> plate and tell me what <em>my</em> body would actually do with it.
                        </p>

                        <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                            <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-6 py-3 text-base font-semibold text-white shadow-sm hover:bg-slate-800 transition-all">
                                See What Your Next Meal Will Do
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
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900">Why Your Body Responds Differently to the Same Food</h2>
                    <p class="mt-4 text-lg text-slate-600">
                        Your friend can crush bagels for breakfast and feel fine. You take one bite and two hours later you're fighting a food coma. It's not in your head—it's science.
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
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Predict the Spike Before You Eat</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">
                            Forget generic calorie counts. This tells you whether that "healthy" granola is about to hijack your energy for the next three hours. Glycemic Load is the real story—most apps don't bother with it.
                        </p>
                    </div>

                    <div class="bg-white rounded-xl p-8 shadow-sm border border-slate-100">
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center text-indigo-600 mb-6">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">See Exactly How It Works</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">
                            The code's right there on GitHub. No black boxes, no mysterious algorithms—you can trace every calculation back to USDA data and published glycemic research. Science you can verify yourself.
                        </p>
                    </div>

                    <div class="bg-white rounded-xl p-8 shadow-sm border border-slate-100">
                        <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center text-emerald-600 mb-6">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">It Learns Your Patterns</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">
                            Track enough meals and it starts noticing what you might miss. Protein at breakfast matters more than you think. That afternoon snack might be the real culprit. The insights get better the more you use it.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-16 sm:py-24 bg-white">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900">Here's How It Actually Works</h2>
                    <p class="mt-4 text-lg text-slate-600">
                        No forms to fill out, no calorie counting. Just describe what you're eating and the system digs into the details.
                    </p>
                </div>

                <div class="grid gap-8 md:grid-cols-3">
                    <div class="relative">
                        <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-slate-100 text-slate-900 font-bold text-xl mb-6">
                            01
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Just Tell It What You're Eating</h3>
                        <p class="text-slate-600 leading-relaxed">
                            Type "oatmeal with banana and honey" or paste a recipe. You can even snap a photo of a restaurant menu. The system figures out the ingredients and nutritional profile automatically.
                        </p>
                    </div>

                    <div class="relative">
                        <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-slate-100 text-slate-900 font-bold text-xl mb-6 border-2 border-rose-500">
                            02
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">It Runs the Numbers</h3>
                        <p class="text-slate-600 leading-relaxed">
                            Here's where it gets interesting. The system models how your body processes those specific carbs, proteins, and fats together—accounting for fiber, meal timing, and what you've eaten earlier.
                        </p>
                    </div>

                    <div class="relative">
                        <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-slate-100 text-slate-900 font-bold text-xl mb-6">
                            03
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Actionable Suggestions Pop Out</h3>
                        <p class="text-slate-600 leading-relaxed">
                            Instead of just numbers, you get practical swaps. "Swap honey for blueberries, add chia seeds, and you could reduce that spike by about 40%." Concrete changes you can actually make.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-slate-50 py-16 sm:py-24">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-slate-900">Questions People Actually Ask</h2>
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
                            It looks at how fiber, protein, and fats interact in your body to slow down sugar absorption. Think of it like modeling your digestion—carb + fiber + protein = different blood sugar outcome than carb alone. That's the simplified version; the actual math pulls from USDA FoodData Central and glycemic research.
                        </p>
                    </details>

                    <details class="group rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <summary class="flex cursor-pointer items-center justify-between font-semibold text-slate-900">
                            Is this really open source?
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="mt-4 text-slate-600">
                            Yes. The whole codebase lives on GitHub. You can see exactly how the analysis works, verify the privacy controls, and even contribute if you're a developer. Health tools shouldn't be black boxes—that's the whole point.
                        </p>
                    </details>

                    <details class="group rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <summary class="flex cursor-pointer items-center justify-between font-semibold text-slate-900">
                            How accurate are the glucose predictions?
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="mt-4 text-slate-600">
                            They're estimates based on established research—not medical advice. Individual responses vary based on factors like sleep, stress, medications, and your metabolic history. The tool gives you a well-informed prediction, not a diagnosis.
                        </p>
                    </details>

                    <details class="group rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <summary class="flex cursor-pointer items-center justify-between font-semibold text-slate-900">
                            Is this a replacement for medical care?
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="mt-4 text-slate-600">
                            Absolutely not. This is a tool for insight and education, not a medical device. It doesn't diagnose conditions or prescribe treatments. Always work with your healthcare provider for diabetes management.
                        </p>
                    </details>

                    <details class="group rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <summary class="flex cursor-pointer items-center justify-between font-semibold text-slate-900">
                            How does this help with diabetes management?
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="mt-4 text-slate-600">
                            By helping you understand how different foods impact your blood sugar, you can make more informed choices. Many users find it helpful for identifying triggers and planning meals that keep their glucose more stable. It's not a CGM replacement, but it gives you predictions before you eat.
                        </p>
                    </details>

                    <details class="group rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <summary class="flex cursor-pointer items-center justify-between font-semibold text-slate-900">
                            What's the difference between this and a glucose monitor?
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="mt-4 text-slate-600">
                            A continuous glucose monitor (CGM) tells you what happened after you ate. This tool helps you predict what might happen before you eat. Think of it as planning tool vs. feedback tool—both are useful for different reasons.
                        </p>
                    </details>

                    <details class="group rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <summary class="flex cursor-pointer items-center justify-between font-semibold text-slate-900">
                            Can I analyze restaurant meals?
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="mt-4 text-slate-600">
                            Absolutely. Describe what you ordered or paste the ingredient list if you have it. The system estimates nutritional content and glycemic impact based on similar foods in its database. It's not perfect for complex restaurant dishes, but it gets you a solid ballpark.
                        </p>
                    </details>
                </div>
            </div>
        </section>

        <section class="py-24 px-4 bg-white border-t border-slate-100">
            <div class="max-w-3xl mx-auto text-center">
                <h2 class="text-2xl font-bold text-slate-900 sm:text-3xl">
                    Part of Something Bigger
                </h2>
                <p class="mt-4 text-slate-600">
                    This is one tool in an open science health stack. We're building it because we got tired of health data being locked away in proprietary apps. Your metabolic health belongs to you.
                </p>
                <div class="mt-8 flex justify-center">
                    <a href="{{ route('register') }}" class="text-base font-semibold text-rose-600 hover:text-rose-500 border-b-2 border-rose-100 hover:border-rose-500 transition-colors pb-1">
                        Try it on your next meal →
                    </a>
                </div>
            </div>
        </section>

    </div>
    <x-cta-block
        title="Meet Altani, Your AI Health Coach"
        description="Get personalized guidance for diabetes management, nutrition planning, and daily wellness support."
        button-text="Learn More"
    />
    <x-footer />
</x-default-layout>