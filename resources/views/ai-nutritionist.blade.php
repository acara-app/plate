@section('title', 'AI Nutritionist for Diabetes | Virtual Nutrition Coach Online')
@section('meta_description', 'Get personalized nutrition advice from your AI nutritionist. Designed for Type 2 diabetes & prediabetes. Ask anything, get instant answers. Try free today!')
@section('meta_keywords', 'AI nutritionist, virtual nutritionist, online nutritionist diabetes, AI nutrition coach, diabetes nutritionist online, virtual dietitian, online dietitian for diabetics')

@section('head')
{{-- Open Graph / Facebook --}}
<meta property="og:type" content="website">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:title" content="AI Nutritionist for Diabetes | Virtual Nutrition Coach Online">
<meta property="og:description" content="Get personalized nutrition advice from your AI nutritionist. Designed for Type 2 diabetes & prediabetes. Ask anything, get instant answers. Try free today!">
<meta property="og:image" content="{{ asset('screenshots/og-ai-nutritionist.webp') }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="Acara Plate AI Nutritionist - Personal nutrition guidance for diabetes management">

{{-- Twitter Card --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:url" content="{{ url()->current() }}">
<meta name="twitter:title" content="AI Nutritionist for Diabetes | Virtual Nutrition Coach Online">
<meta name="twitter:description" content="Get personalized nutrition advice from your AI nutritionist. Designed for Type 2 diabetes & prediabetes. Ask anything, get instant answers. Try free today!">
<meta name="twitter:image" content="{{ asset('screenshots/og-ai-nutritionist.webp') }}">
<meta name="twitter:image:alt" content="Acara Plate AI Nutritionist - Personal nutrition guidance for diabetes management">

<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Product",
    "name": "Acara Plate AI Nutritionist",
    "description": "AI-powered nutritionist for diabetes management. Get personalized meal advice, glucose predictions, and dietary guidance.",
    "image": "{{ asset('screenshots/og-ai-nutritionist.webp') }}",
    "brand": {
        "@@type": "Brand",
        "name": "Acara Plate"
    },
    "offers": {
        "@@type": "Offer",
        "price": "0",
        "priceCurrency": "USD",
        "availability": "https://schema.org/InStock",
        "priceValidUntil": "2026-12-31",
        "description": "7-Day Free Trial"
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
            "name": "What is an AI nutritionist?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "An AI nutritionist is an artificial intelligence system trained on nutrition science and diabetes management. It provides personalized dietary advice, answers questions about food and glucose, and helps you make better eating decisions in real-time."
            }
        },
        {
            "@@type": "Question",
            "name": "Can an AI nutritionist help with diabetes?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Yes, this AI nutritionist specializes in diabetes and prediabetes management. It understands glycemic index, carbohydrate counting, and how different foods affect blood sugar. It provides personalized recommendations based on your health goals."
            }
        },
        {
            "@@type": "Question",
            "name": "Is an AI nutritionist better than a human nutritionist?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "AI nutritionists excel at instant availability, cost-effectiveness, and handling routine questions. They're available 24/7 and can analyze vast databases of nutritional information instantly. For complex medical conditions or eating disorders, a human nutritionist may be more appropriate. Many people use both — AI for daily decisions, humans for periodic check-ins."
            }
        },
        {
            "@@type": "Question",
            "name": "How much does a virtual nutritionist cost?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Traditional virtual nutritionists typically charge $100-300 per session. Our AI nutritionist is available with a free trial, making personalized nutrition guidance accessible to everyone. Subscription plans are significantly more affordable than human nutritionist services."
            }
        }
    ]
}
</script>
@endsection

<x-default-layout>
    <div class="relative overflow-hidden bg-white">
        {{-- Hero Section --}}
        <section class="relative px-4 pt-16 pb-24 sm:px-6 lg:px-8">
            {{-- Background gradient --}}
            <div aria-hidden="true" class="absolute inset-0 overflow-hidden">
                <div class="absolute -top-24 -right-24 h-96 w-96 rounded-full bg-rose-100 opacity-50 blur-3xl"></div>
                <div class="absolute top-1/2 -left-24 h-96 w-96 rounded-full bg-purple-100 opacity-50 blur-3xl"></div>
            </div>

            <div class="relative mx-auto max-w-7xl">
                <div class="grid gap-12 lg:grid-cols-2 lg:gap-8 items-center">
                    {{-- Content --}}
                    <div class="text-center lg:text-left">
                        <div class="inline-flex items-center gap-2 rounded-full bg-rose-50 px-4 py-1.5 text-sm font-medium text-rose-700 ring-1 ring-inset ring-rose-600/20 mb-6">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            Available 24/7 — Instant Answers
                        </div>

                        <h1 class="text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl lg:text-6xl">
                            Your Personal
                            <span class="block bg-gradient-to-r from-rose-600 to-purple-600 bg-clip-text text-transparent">
                                AI Nutritionist
                            </span>
                        </h1>

                        <p class="mt-6 text-lg leading-8 text-slate-600 max-w-2xl mx-auto lg:mx-0">
                            Get expert nutrition advice instantly. Ask anything about food, meal planning, 
                            or managing your blood sugar. No appointments, no waiting — just helpful answers 
                            whenever you need them.
                        </p>

                        <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                            <a href="{{ route('register') }}"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-8 py-4 text-base font-bold text-white shadow-lg transition-all hover:bg-slate-800 hover:shadow-xl">
                                Try AI Nutritionist Free
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </a>
                            <a href="#how-it-works"
                                class="inline-flex items-center justify-center gap-2 rounded-xl border-2 border-slate-200 bg-white px-8 py-4 text-base font-bold text-slate-700 transition-all hover:border-slate-300 hover:bg-slate-50">
                                See How It Works
                            </a>
                        </div>

                        {{-- Trust badges --}}
                        <div class="mt-8 flex flex-wrap items-center justify-center lg:justify-start gap-6 text-sm text-slate-500">
                            <span class="flex items-center gap-1.5">
                                <svg class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                No credit card required
                            </span>
                            <span class="flex items-center gap-1.5">
                                <svg class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Cancel anytime
                            </span>
                        </div>

                        {{-- Social Proof --}}
                        <div class="mt-6 flex items-center justify-center gap-4 lg:justify-start">
                            <div class="flex -space-x-2">
                                <img class="inline-block h-8 w-8 rounded-full ring-2 ring-white" src="https://ui-avatars.com/api/?name=Sarah+J&background=random" alt=""/>
                                <img class="inline-block h-8 w-8 rounded-full ring-2 ring-white" src="https://ui-avatars.com/api/?name=Mike+T&background=random" alt=""/>
                                <img class="inline-block h-8 w-8 rounded-full ring-2 ring-white" src="https://ui-avatars.com/api/?name=Ali+R&background=random" alt=""/>
                            </div>
                            <div class="text-sm font-medium text-slate-600">
                                Trusted by early adopters
                            </div>
                        </div>

                        {{-- The "Open Source" Trust Badge --}}
                        <div class="mt-12 border-t border-slate-100 pt-8">
                            <div class="flex flex-col items-center justify-between gap-6 sm:flex-row">
                                <div class="flex items-center gap-4">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-900">
                                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                                            <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="text-left">
                                        <p class="font-bold text-slate-900">Open Source & Transparent</p>
                                        <p class="text-sm text-slate-600">
                                            Your health data is sensitive. The code is public so you (or experts) 
                                            can verify exactly how your privacy is handled.
                                        </p>
                                    </div>
                                </div>
                                <a href="https://github.com/acara-app/plate" class="text-sm font-semibold text-rose-600 hover:text-rose-500 shrink-0">
                                    View Source Code →
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- Chat Preview --}}
                    <div class="relative mx-auto max-w-md lg:max-w-none">
                        <div class="relative rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/5 overflow-hidden">
                            {{-- Chat Header --}}
                            <div class="bg-gradient-to-r from-rose-500 to-purple-600 px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white/20 text-white">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                        </svg>
                                    </div>
                                    <div class="text-white">
                                        <div class="font-semibold">AI Nutritionist</div>
                                        <div class="flex items-center gap-1.5 text-xs text-white/80">
                                            <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                                            Online now
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Chat Messages --}}
                            <div class="bg-slate-50 p-6 space-y-4">
                                {{-- User Message --}}
                                <div class="flex justify-end">
                                    <div class="max-w-[85%] rounded-2xl rounded-tr-sm bg-slate-900 px-4 py-2.5 text-sm text-white">
                                        I'm at Chipotle. What should I order to keep my glucose stable?
                                    </div>
                                </div>

                                {{-- AI Response --}}
                                <div class="flex gap-3">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-rose-500 to-purple-600 text-white">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    </div>
                                    <div class="max-w-[85%] space-y-2">
                                        <div class="rounded-2xl rounded-tl-sm bg-white px-4 py-3 text-sm text-slate-700 shadow-sm">
                                            <p class="font-medium text-slate-900 mb-1">Go for a bowl with:</p>
                                            <ul class="space-y-1 text-slate-600 list-disc list-inside">
                                                <li>Brown rice (half portion)</li>
                                                <li>Chicken or sofritas</li>
                                                <li>Fajita veggies + guacamole</li>
                                                <li>Skip the corn salsa (+12g sugar)</li>
                                            </ul>
                                            <p class="mt-2 text-xs text-slate-500">Predicted spike: +35 mg/dL (low-medium)</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- User Message 2 --}}
                                <div class="flex justify-end">
                                    <div class="max-w-[85%] rounded-2xl rounded-tr-sm bg-slate-900 px-4 py-2.5 text-sm text-white">
                                        What about a bedtime snack that won't spike me?
                                    </div>
                                </div>

                                {{-- AI Response 2 --}}
                                <div class="flex gap-3">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-rose-500 to-purple-600 text-white">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    </div>
                                    <div class="max-w-[85%]">
                                        <div class="rounded-2xl rounded-tl-sm bg-white px-4 py-3 text-sm text-slate-700 shadow-sm">
                                            <p>Try Greek yogurt with cinnamon, or a small handful of almonds. Both have protein and healthy fats that digest slowly overnight. 🌙</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Input --}}
                            <div class="border-t border-slate-200 bg-white p-4">
                                <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                                    <span class="text-sm text-slate-400">Type your question...</span>
                                    <button class="ml-auto rounded-lg bg-rose-500 p-2 text-white opacity-50">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- How It Works Section --}}
        <section id="how-it-works" class="bg-slate-50 px-4 py-24 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-slate-900 sm:text-4xl">How Your AI Nutritionist Works</h2>
                    <p class="mt-4 text-lg text-slate-600">Simple, instant, personalized nutrition guidance</p>
                </div>

                <div class="grid gap-8 md:grid-cols-3">
                    {{-- Step 1 --}}
                    <div class="relative">
                        <div class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
                            <div class="mb-6 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-rose-100 text-rose-600 text-xl font-bold">
                                1
                            </div>
                            <h3 class="text-xl font-bold text-slate-900 mb-3">Ask Anything</h3>
                            <p class="text-slate-600">
                                Type your question naturally. "What should I eat for breakfast?" or 
                                "Is quinoa better than rice for my blood sugar?" No medical jargon needed.
                            </p>
                        </div>
                    </div>

                    {{-- Step 2 --}}
                    <div class="relative">
                        <div class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
                            <div class="mb-6 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-purple-100 text-purple-600 text-xl font-bold">
                                2
                            </div>
                            <h3 class="text-xl font-bold text-slate-900 mb-3">Get Personalized Advice</h3>
                            <p class="text-slate-600">
                                Your AI analyzes your profile, health goals, and the latest nutrition science 
                                to give you tailored recommendations — not generic advice.
                            </p>
                        </div>
                    </div>

                    {{-- Step 3 --}}
                    <div class="relative">
                        <div class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
                            <div class="mb-6 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 text-xl font-bold">
                                3
                            </div>
                            <h3 class="text-xl font-bold text-slate-900 mb-3">Make Better Choices</h3>
                            <p class="text-slate-600">
                                Use the guidance to build healthier habits. Track what works, learn from 
                                your patterns, and take control of your nutrition with confidence.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Features Section --}}
        <section class="px-4 py-24 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="grid gap-12 lg:grid-cols-2 lg:gap-16 items-center">
                    <div>
                        <h2 class="text-3xl font-bold text-slate-900 sm:text-4xl mb-6">
                            What Can You Ask Your AI Nutritionist?
                        </h2>
                        <p class="text-lg text-slate-600 mb-8">
                            From everyday meal decisions to long-term planning, get answers that actually help 
                            you manage your diabetes and enjoy your food.
                        </p>

                        <div class="space-y-4">
                            <div class="flex gap-4">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-rose-100 text-rose-600">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-slate-900">Restaurant Guidance</h3>
                                    <p class="text-slate-600 text-sm">"I'm at Olive Garden — what's the safest option?"</p>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-purple-100 text-purple-600">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-slate-900">Meal Planning</h3>
                                    <p class="text-slate-600 text-sm">"Plan my dinners this week with under 45g carbs each"</p>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-600">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-slate-900">Glucose Predictions</h3>
                                    <p class="text-slate-600 text-sm">"Will this meal spike my blood sugar?"</p>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-slate-900">Substitutions</h3>
                                    <p class="text-slate-600 text-sm">"What can I use instead of rice that tastes similar?"</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl bg-gradient-to-br from-slate-900 to-slate-800 p-8 text-white">
                        <h3 class="text-2xl font-bold mb-6">Example Conversations</h3>

                        <div class="space-y-4">
                            <div class="rounded-xl bg-white/10 p-4">
                                <p class="text-sm text-white/60 mb-2">You:</p>
                                <p class="font-medium">"I have 15g carbs left for dinner. What can I make?"</p>
                            </div>

                            <div class="rounded-xl bg-rose-500/20 p-4 border border-rose-500/30">
                                <p class="text-sm text-rose-200 mb-2">AI Nutritionist:</p>
                                <p class="font-medium">"Here are 3 quick options under 15g carbs: 🥗 Grilled salmon with roasted vegetables (8g), 🍳 Veggie omelet with cheese (6g), 🥩 Steak with cauliflower mash (4g)"</p>
                            </div>

                            <div class="rounded-xl bg-white/10 p-4">
                                <p class="text-sm text-white/60 mb-2">You:</p>
                                <p class="font-medium">"Why did my glucose spike after oatmeal? I thought it was healthy."</p>
                            </div>

                            <div class="rounded-xl bg-rose-500/20 p-4 border border-rose-500/30">
                                <p class="text-sm text-rose-200 mb-2">AI Nutritionist:</p>
                                <p class="font-medium">"Oatmeal has a medium GI (55). Try steel-cut oats instead of instant, add protein (nuts/greek yogurt), and keep the portion to 1/2 cup dry."</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Comparison Section --}}
        <section class="bg-slate-50 px-4 py-24 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-4xl">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-slate-900">AI vs Traditional Nutritionist</h2>
                    <p class="mt-4 text-lg text-slate-600">Both have their place. Here is how they compare:</p>
                </div>

                <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 overflow-x-auto">
                    <div class="min-w-[600px] text-sm sm:text-base">
                        
                        <div class="grid grid-cols-3 divide-x divide-slate-200">
                            <div class="bg-slate-50 p-4 font-semibold text-slate-900 flex items-center">Feature</div>
                            <div class="bg-rose-50 p-4 font-bold text-rose-900 text-center">AI Nutritionist</div>
                            <div class="bg-slate-50 p-4 font-semibold text-slate-900 text-center">Human Dietitian</div>
                        </div>

                        <div class="grid grid-cols-3 divide-x divide-slate-200 border-t border-slate-200">
                            <div class="p-4 text-slate-600 font-medium">Availability</div>
                            <div class="p-4 text-center text-emerald-600 font-bold">24/7 Instant</div>
                            <div class="p-4 text-center text-slate-600">By appointment</div>
                        </div>

                        <div class="grid grid-cols-3 divide-x divide-slate-200 border-t border-slate-200">
                            <div class="p-4 text-slate-600 font-medium">Cost</div>
                            <div class="p-4 text-center text-emerald-600 font-bold">Unlimited / Flat Fee</div>
                            <div class="p-4 text-center text-slate-600">$100-300 per session</div>
                        </div>

                        <div class="grid grid-cols-3 divide-x divide-slate-200 border-t border-slate-200">
                            <div class="p-4 text-slate-600 font-medium">Data Memory</div>
                            <div class="p-4 text-center text-emerald-600 font-bold">Recalls every meal instantly</div>
                            <div class="p-4 text-center text-slate-600">Relies on food logs</div>
                        </div>

                        <div class="grid grid-cols-3 divide-x divide-slate-200 border-t border-slate-200">
                            <div class="p-4 text-slate-600 font-medium">Best For</div>
                            <div class="p-4 text-center text-slate-900">Daily decisions & meal ideas</div>
                            <div class="p-4 text-center text-slate-900">Complex medical conditions</div>
                        </div>
                    </div>
                </div>

                <p class="mt-2 text-center text-xs text-slate-400 sm:hidden">← Scroll to compare →</p>

                <p class="mt-6 text-center text-sm text-slate-500">
                    Many people use both: AI for daily choices, and a human dietitian for quarterly check-ins.
                </p>
            </div>
    </section>

        {{-- FAQ Section --}}
        <section class="px-4 py-24 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-3xl">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-slate-900">Frequently Asked Questions</h2>
                </div>

                <div class="space-y-4">
                    <details class="group rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <summary class="flex cursor-pointer items-center justify-between font-semibold text-slate-900">
                            What is an AI nutritionist?
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="mt-4 text-slate-600">
                            An AI nutritionist is an artificial intelligence trained on nutrition science and diabetes management. 
                            It provides personalized dietary advice, answers questions about food and glucose, and helps you make 
                            better eating decisions in real-time. It's like having a nutrition expert in your pocket, available 
                            whenever you need guidance.
                        </p>
                    </details>

                    <details class="group rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <summary class="flex cursor-pointer items-center justify-between font-semibold text-slate-900">
                            Can an AI nutritionist really help with diabetes?
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="mt-4 text-slate-600">
                            Yes, this AI nutritionist specializes in Type 2 diabetes and prediabetes management. It understands 
                            glycemic index, carbohydrate counting, portion control, and how different foods affect blood sugar. 
                            It provides personalized recommendations based on your health goals, dietary preferences, and glucose patterns.
                        </p>
                    </details>

                    <details class="group rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <summary class="flex cursor-pointer items-center justify-between font-semibold text-slate-900">
                            Is this a replacement for my doctor or dietitian?
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="mt-4 text-slate-600">
                            No. Our AI nutritionist is a complementary tool for daily guidance, not a replacement for medical care. 
                            Always consult your healthcare provider for medical advice, medication adjustments, and treatment plans. 
                            Think of the AI as a smart assistant that helps you make better day-to-day food choices.
                        </p>
                    </details>

                    <details class="group rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <summary class="flex cursor-pointer items-center justify-between font-semibold text-slate-900">
                            How much does it cost?
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="mt-4 text-slate-600">
                            The AI nutritionist is included with your Acara Plate subscription. A free trial is available so you can 
                            test it out before committing. Compared to traditional nutritionists who charge $100-300 per session, 
                            it's an affordable way to get ongoing nutrition support.
                        </p>
                    </details>

                    <details class="group rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <summary class="flex cursor-pointer items-center justify-between font-semibold text-slate-900">
                            What kind of questions can I ask?
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="mt-4 text-slate-600">
                            Pretty much anything food-related! Popular questions include: "What should I order at [restaurant]?", 
                            "Will this food spike my blood sugar?", "Suggest low-carb snacks", "Help me plan meals for the week", 
                            "What can I substitute for rice?", "Why did my glucose spike after this meal?" — just ask naturally 
                            like you're texting a friend.
                        </p>
                    </details>
                </div>
            </div>
        </section>

        {{-- Final CTA Section --}}
        <section class="bg-slate-900 px-4 py-24 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-4xl text-center">
                <h2 class="text-3xl font-bold text-white sm:text-4xl">
                    Ready to Get Your Personal AI Nutritionist?
                </h2>

                <p class="mt-6 text-lg text-slate-300">
                    Experience the clarity of AI-powered guidance without the "black box." Open source, privacy-focused, and designed for real life.
                </p>

                <div class="mt-10 flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('register') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-8 py-4 text-base font-bold text-slate-900 shadow-lg transition-all hover:bg-slate-100 hover:shadow-xl"
                    >
                        Start Free Trial
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </a>

                    <a href="{{ route('login') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border-2 border-slate-600 bg-transparent px-8 py-4 text-base font-bold text-white transition-all hover:border-slate-500 hover:bg-slate-800"
                    >
                        Already have an account? Sign in
                    </a>
                </div>

                <p class="mt-8 text-sm text-slate-400">
                    Free 7-day trial • Cancel anytime • No credit card required
                </p>
            </div>
        </section>
    </div>
</x-default-layout>