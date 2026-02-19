@section('title', 'AI Nutritionist for Blood Sugar | Acara Plate')
@section('meta_description', 'The future of metabolic health is here. Instant, AI-driven insights to optimize your blood sugar and energy. Your personal health intelligence.')
@section('meta_keywords', 'blood sugar app, glucose tracker, AI nutritionist, smart meal planner, diabetes friendly recipes')
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
    <div class="bg-[#FFFBF5]">
        <header class="sticky top-0 z-50 w-full py-4 px-4 sm:px-6 lg:px-8 flex justify-between items-center bg-white backdrop-blur-md border-b border-slate-100">
            <a href="/" class="flex items-center gap-2 text-xl font-bold text-slate-900">
                <span class="text-2xl" role="img" aria-label="strawberry">🍓</span>
                <span>Acara Plate</span>
            </a>
            <div class="flex items-center gap-4">
                <a href="{{ route('login') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Log in</a>
                <a href="{{ route('register') }}" class="rounded-full bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition-all">
                    Get Started
                </a>
            </div>
        </header>
        
        <!-- Hero Section - F-Pattern Design -->
        <section class="relative pt-16 pb-20 sm:pt-24 sm:pb-32 overflow-hidden">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="lg:grid lg:grid-cols-12 lg:gap-16 items-center">
                    <!-- Left Column - F-Pattern Reading (Text First) -->
                    <div class="lg:col-span-6 text-center lg:text-left">
                        <div class="mb-6 flex justify-center lg:justify-start">
                            <span class="inline-flex items-center rounded-full bg-rose-100 px-4 py-1.5 text-sm font-semibold text-rose-700 ring-1 ring-rose-200">
                                <svg class="h-4 w-4 text-rose-600 mr-2" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
                                </svg>
                                Open Science & Source
                            </span>
                        </div>
                        
                        <h1 class="font-display text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl lg:text-6xl mb-6">
                            I Ate "Healthy" Oatmeal Every Morning For a Year.
                            <span class="text-[#FF6B4A]">My Glucose Told a Different Story.</span>
                        </h1>
                        
                        <p class="mt-6 text-lg leading-8 text-slate-600 max-w-2xl mx-auto lg:mx-0">
                            Turns out, the banana-and-honey "health" bowl I loved was spiking my blood sugar through the roof. That's the thing about nutrition—everyone's body responds differently. The tool I wish I'd had? Something that could look at <em>my</em> plate and tell me what <em>my</em> body would actually do with it.
                        </p>
                        
                        <div class="mt-10 flex flex-col sm:flex-row gap-4 items-center justify-center lg:justify-start">
                            <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-full bg-[#FF6B4A] px-8 py-3.5 text-base font-semibold text-white shadow-sm hover:bg-[#E85A3A] transition-all focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#FF6B4A]">
                                See What Your Next Meal Will Do
                            </a>
                            <a href="https://github.com/acara-app/plate" target="_blank" class="text-sm font-semibold leading-6 text-slate-900">
                                View Source Code <span aria-hidden="true">→</span>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Right Column - Hero Image -->
                    <div class="lg:col-span-6 mt-12 lg:mt-0 relative">
                        <div class="relative">
                            <!-- Decorative Background Shape -->
                            <div class="absolute -inset-4 bg-[#FFEFE5] rounded-3xl transform rotate-2 -z-10"></div>
                            
                            <!-- Main Hero Image -->
                            <div class="relative rounded-3xl overflow-hidden shadow-2xl">
                                <img 
                                    src="https://pub-plate-assets.acara.app/images/diet.png" 
                                    alt="AI Nutritionist analyzing a healthy meal with vegetables, fruits, and grains"
                                    class="w-full h-auto object-cover"
                                    width="800"
                                    height="600"
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Why Your Body Responds Differently Section (Dark Green) -->
        <section class="bg-[#0E3F3B] py-20 sm:py-32">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="lg:grid lg:grid-cols-12 lg:gap-16">
                    <div class="lg:col-span-5 text-white mb-12 lg:mb-0">
                        <h2 class="font-display text-4xl font-bold tracking-tight sm:text-5xl mb-6 text-[#FF6B4A]">
                            Why Your Body<br>Responds Differently
                        </h2>
                        
                        <p class="text-lg leading-relaxed text-emerald-100/90 mb-8">
                            Your friend can crush bagels for breakfast and feel fine. You take one bite and two hours later you're fighting a food coma. It's not in your head—it's science.
                        </p>
                        
                        <div class="inline-flex items-center gap-2 text-white font-medium border-b border-[#FF6B4A] pb-1">
                            <span>Explore features</span>
                            <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </div>
                    </div>

                    <div class="lg:col-span-7 grid md:grid-cols-2 gap-6">
                        <!-- Card 1 -->
                        <div class="bg-white rounded-2xl p-6 shadow-lg transform md:translate-y-8">
                            <span class="text-[#FF6B4A] text-xl font-bold block mb-2">01</span>
                            <h3 class="font-bold text-slate-900 text-lg mb-3">Predict the Spike Before You Eat</h3>
                            <p class="text-slate-600 text-sm leading-relaxed">
                                Forget generic calorie counts. This tells you whether that "healthy" granola is about to hijack your energy for the next three hours. Glycemic Load is the real story—most apps don't bother with it.
                            </p>
                        </div>
                        
                        <!-- Card 2 -->
                        <div class="bg-white rounded-2xl p-6 shadow-lg">
                            <span class="text-[#FF6B4A] text-xl font-bold block mb-2">02</span>
                            <h3 class="font-bold text-slate-900 text-lg mb-3">See Exactly How It Works</h3>
                            <p class="text-slate-600 text-sm leading-relaxed">
                                The code's right there on GitHub. No black boxes, no mysterious algorithms—you can trace every calculation back to USDA data and published glycemic research. Science you can verify yourself.
                            </p>
                        </div>

                        <!-- Card 3 -->
                        <div class="bg-white rounded-2xl p-6 shadow-lg transform md:translate-y-8">
                            <span class="text-[#FF6B4A] text-xl font-bold block mb-2">03</span>
                            <h3 class="font-bold text-slate-900 text-lg mb-3">It Learns Your Patterns</h3>
                            <p class="text-slate-600 text-sm leading-relaxed">
                                Track enough meals and it starts noticing what you might miss. Protein at breakfast matters more than you think. That afternoon snack might be the real culprit. The insights get better the more you use it.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- How It Works Section (Sticky Notes) -->
        <section class="bg-[#FFFBF5] py-20 sm:py-32 overflow-hidden">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 text-center">
                <span class="text-[#FF6B4A] font-bold tracking-wider uppercase text-sm">How It Works</span>
                
                <h2 class="font-display text-4xl font-bold text-slate-900 sm:text-5xl mt-3 mb-6">
                    Here's How It Actually Works
                </h2>
                
                <p class="text-lg text-slate-600 max-w-2xl mx-auto mb-16">
                    No forms to fill out, no calorie counting. Just describe what you're eating and the system digs into the details.
                </p>

                <div class="relative max-w-5xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-8 md:gap-12">
                     <!-- Sticky Note 1 (Orange) -->
                    <div class="md:-rotate-3 hover:rotate-0 transition-transform duration-300">
                        <div class="bg-[#FF6B4A] p-8 text-left shadow-lg h-full min-h-[280px] flex flex-col justify-between" style="clip-path: polygon(100% 0, 100% 85%, 85% 100%, 0 100%, 0 0);">
                             <div>
                                <h3 class="text-white font-bold text-xl mb-4">01<br>Just Tell It What You're Eating</h3>
                                <p class="text-white/90 leading-relaxed font-medium">
                                    Type "oatmeal with banana and honey" or paste a recipe. You can even snap a photo of a restaurant menu. The system figures out the ingredients and nutritional profile automatically.
                                </p>
                             </div>
                             <div class="mt-4 w-8 h-8 rounded-full bg-white/20 flex items-center justify-center text-white">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                             </div>
                        </div>
                    </div>

                    <!-- Sticky Note 2 (Pink) -->
                    <div class="md:rotate-2 hover:rotate-0 transition-transform duration-300 md:-translate-y-8">
                        <div class="bg-[#F8D4D8] p-8 text-left shadow-lg h-full min-h-[280px] flex flex-col justify-between" style="clip-path: polygon(100% 0, 100% 85%, 85% 100%, 0 100%, 0 0);">
                            <div>
                                <h3 class="text-slate-900 font-bold text-xl mb-4">02<br>It Runs the Numbers</h3>
                                <p class="text-slate-800/90 leading-relaxed font-medium">
                                    Here's where it gets interesting. The system models how your body processes those specific carbs, proteins, and fats together—accounting for fiber, meal timing, and what you've eaten earlier.
                                </p>
                            </div>
                            <div class="mt-4 w-8 h-8 rounded-full bg-slate-900/10 flex items-center justify-center text-slate-900">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                            </div>
                        </div>
                    </div>

                    <!-- Sticky Note 3 (Blue/Purple) -->
                    <div class="md:-rotate-2 hover:rotate-0 transition-transform duration-300">
                        <div class="bg-[#6B71F3] p-8 text-left shadow-lg h-full min-h-[280px] flex flex-col justify-between" style="clip-path: polygon(100% 0, 100% 85%, 85% 100%, 0 100%, 0 0);">
                            <div>
                                <h3 class="text-white font-bold text-xl mb-4">03<br>Actionable Suggestions Pop Out</h3>
                                <p class="text-white/90 leading-relaxed font-medium">
                                    Instead of just numbers, you get practical swaps. "Swap honey for blueberries, add chia seeds, and you could reduce that spike by about 40%." Concrete changes you can actually make.
                                </p>
                            </div>
                            <div class="mt-4 w-8 h-8 rounded-full bg-white/20 flex items-center justify-center text-white">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            </div>
                        </div>
                    </div>

                     <div class="hidden md:block absolute -right-12 top-10 md:rotate-6 w-48 opacity-20 pointer-events-none">
                        <div class="bg-[#0E3F3B] p-4 h-48"></div>
                     </div>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="bg-slate-50 py-16 sm:py-24">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-slate-900">Questions People Actually Ask</h2>
                </div>

                <div class="space-y-4">
                    <details class="group rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <summary class="flex cursor-pointer items-center justify-between font-semibold text-slate-900">
                            How does the analysis engine work?
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
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
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
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
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
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
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
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
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
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
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="mt-4 text-slate-600">
                            A continuous glucose monitor (CGM) tells you what happened after you ate. This tool helps you predict what might happen before you eat. Think of it as planning tool vs. feedback tool—both are useful for different reasons.
                        </p>
                    </details>

                    <details class="group rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <summary class="flex cursor-pointer items-center justify-between font-semibold text-slate-900">
                            Do I need to create an account to use this?
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="mt-4 text-slate-600">
                            No! You can analyze meals instantly without signing up. Create an account if you want to save your meal history and get recommendations over time.
                        </p>
                    </details>

                    <details class="group rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <summary class="flex cursor-pointer items-center justify-between font-semibold text-slate-900">
                            Can I analyze restaurant meals?
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
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

        <!-- Part of Something Bigger Section (Peach Background) -->
        <section class="bg-[#FFEFE5] py-20 sm:py-32">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="font-display text-3xl font-bold text-slate-900 sm:text-4xl">
                    Part of Something Bigger
                </h2>
                
                <p class="mt-4 text-lg text-slate-600">
                    This is one tool in an open science health stack. We're building it because we got tired of health data being locked away in proprietary apps. Your metabolic health belongs to you.
                </p>
                
                <div class="mt-8 flex justify-center">
                    <a href="{{ route('register') }}" class="text-base font-semibold text-[#FF6B4A] hover:text-[#E85A3A] border-b-2 border-[#FF6B4A]/30 hover:border-[#FF6B4A] transition-colors pb-1">
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
