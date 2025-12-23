@section('title', 'AI Diabetes Meal Planner & Glucose Tracker | Personalized Nutrition')
@section('meta_description', 'Manage Type 2 diabetes with Acara Plate\'s AI nutritionist. Get personalized meal plans that match your glucose levels. Start your free plan today!')

<x-default-layout>
    @section('head')
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "WebSite",
        "name": "Acara Plate",
        "url": "{{ url('/') }}",
        "potentialAction": {
            "@@type": "SearchAction",
            "target": "{{ url('/') }}/?s={search_term_string}",
            "query-input": "required name=search_term_string"
        }
    }
    </script>
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "Organization",
        "name": "Acara Plate",
        "url": "{{ url('/') }}",
        "logo": "{{ asset('apple-touch-icon/apple-touch-icon-180x180.png') }}",
        "sameAs": [
            "https://github.com/acara-app/plate"
        ]
    }
    </script>
    @endsection
    {{-- Hero Section with Fizzy-style design --}}
    <div class="relative min-h-screen bg-white">
        {{-- Gradient overlay at top --}}
        <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 h-150 bg-linear-to-b from-fuchsia-100/60 via-emerald-100/40 to-transparent"></div>
        <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 h-100 bg-linear-to-br from-pink-100/40 via-transparent to-transparent"></div>

        <header class="sticky top-0 z-50 w-full border-b border-slate-200 bg-white/80 backdrop-blur-md">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                {{-- Logo --}}
                <a href="/" class="flex items-center gap-2 text-xl font-bold text-slate-900 transition-opacity hover:opacity-80">
                    <span class="text-2xl" role="img" aria-label="strawberry">🍓</span>
                    <span>Acara Plate</span>
                </a>

                {{-- Center promo banner --}}
                <div class="hidden text-center text-sm text-slate-600 lg:block">
                    Stop guessing what to eat. Personalized meal plans, <span class="font-semibold text-emerald-600">made simple</span>
                </div>

                {{-- Auth buttons --}}
                <div class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}"
                            class="inline-flex items-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition-all duration-200 hover:bg-slate-800">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                            class="hidden items-center px-4 py-2 text-sm font-medium text-slate-600 transition-all duration-200 hover:text-slate-900 sm:inline-flex">
                            Sign in
                        </a>
                        <a href="{{ route('register') }}"
                            class="inline-flex items-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition-all duration-200 hover:bg-slate-800">
                            Start for Free
                        </a>
                    @endauth
                </div>
            </div>
        </header>

        <main class="relative z-10 flex flex-col items-center gap-24 px-4 pb-24 pt-6 sm:px-6 lg:px-8">
            {{-- Hero Section --}}
            <section aria-label="Hero" class="flex w-full max-w-7xl flex-col items-center gap-12 lg:gap-16">
                
                {{-- Screenshot Container --}}
                <figure class="w-full">
                    <div class="relative overflow-hidden rounded-t-2xl shadow-2xl shadow-slate-200 ring-1 ring-slate-900/5">
                        <picture>
                            <source srcset="{{ asset('meal-plan-hero-section.webp') }}" type="image/webp">
                            <img src="{{ asset('meal-plan-hero-section.webp') }}" alt="AI-personalized meal planning dashboard showing personalized nutrition recommendations" class="w-full">
                        </picture>
                        <div class="absolute inset-0 bg-linear-to-b from-transparent via-transparent to-slate-900"></div>
                    </div>
                </figure>

                {{-- Headline Section --}}
                <div class="flex w-full max-w-4xl flex-col items-center text-center">
                    <h1 class="text-4xl font-bold leading-tight tracking-tight text-slate-900 sm:text-5xl lg:text-6xl">
                        Manage Blood Sugar
                        <span class="block bg-linear-to-r from-emerald-600 via-teal-600 to-cyan-600 bg-clip-text text-transparent">
                            with AI Nutrition Specialist
                        </span>
                    </h1>

                    <p class="mt-6 max-w-2xl text-lg leading-relaxed text-slate-600 sm:text-xl">
                        Eat better without guessing. AI-powered <strong class="text-slate-900">meal plans</strong> that match your glucose levels. Designed for adults with Type 2 diabetes or prediabetes.
                    </p>

                    <div class="mt-8 flex flex-col items-center justify-center gap-4 sm:flex-row">
                        <a href="{{ route('register') }}"
                            class="group/btn inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-8 py-4 text-base font-semibold text-white shadow-lg transition-all duration-300 hover:bg-slate-800 hover:shadow-xl sm:text-lg">
                            Start Your Free Plan
                            <svg class="h-5 w-5 transition-transform duration-300 group-hover/btn:translate-x-1"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </a>

                        {{-- GitHub Trust Button --}}
                        <a href="https://github.com/acara-app/plate" target="_blank"
                            class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-6 py-4 text-base font-medium text-slate-600 transition-all duration-300 hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 sm:text-lg">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill-rule="evenodd"
                                    d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"
                                    clip-rule="evenodd" />
                            </svg>
                            Star on GitHub
                        </a>
                    </div>

                    {{-- Beta Badge --}}
                    <div class="mt-8 inline-flex items-center gap-2 rounded-full bg-amber-50 px-4 py-2 text-sm font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                clip-rule="evenodd" />
                        </svg>
                        Beta Version — Open Source & Free
                    </div>
                </div>
            </section>

            {{-- Spike Calculator Tool Promo --}}
            <section class="w-full max-w-6xl">
                <div class="relative overflow-hidden rounded-3xl bg-slate-900 border border-slate-800 px-6 py-12 shadow-2xl sm:px-12 sm:py-16 lg:px-16">
                    {{-- Background Effects --}}
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,var(--tw-gradient-stops))] from-orange-500/20 via-slate-900 to-slate-900"></div>
                    <div class="absolute -left-12 -top-12 h-64 w-64 rounded-full bg-orange-500/10 blur-3xl"></div>
                    <div class="absolute -bottom-12 -right-12 h-64 w-64 rounded-full bg-amber-500/10 blur-3xl"></div>

                    <div class="relative grid gap-12 lg:grid-cols-2 lg:items-center">
                        {{-- Content --}}
                        <div class="space-y-8 text-center lg:text-left">
                            <div class="inline-flex items-center gap-2 rounded-full bg-orange-500/10 px-3 py-1 text-sm font-medium text-orange-400 ring-1 ring-inset ring-orange-500/20">
                                <span class="relative flex h-2 w-2">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-orange-400 opacity-75"></span>
                                    <span class="relative inline-flex h-2 w-2 rounded-full bg-orange-500"></span>
                                </span>
                                Free Tool — No Registration Required
                            </div>

                            <div class="space-y-4">
                                <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">
                                    Check if this food spikes your <span class="text-transparent bg-clip-text bg-linear-to-r from-orange-400 to-amber-400">blood sugar</span>
                                </h2>

                                <p class="text-lg leading-relaxed text-slate-400">
                                    Type a food name. We check the nutrition facts and tell you the likely glucose impact immediately.
                                </p>
                            </div>

                            <div class="flex flex-col gap-4 sm:flex-row sm:justify-center lg:justify-start">
                                <a href="{{ route('spike-calculator') }}"
                                   class="group inline-flex items-center justify-center gap-2 rounded-xl bg-linear-to-r from-orange-500 to-amber-500 px-8 py-4 text-base font-bold text-white shadow-lg shadow-orange-500/25 transition-all hover:scale-105 hover:shadow-orange-500/40 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 focus:ring-offset-slate-900">
                                    Try Spike Calculator
                                    <svg class="h-5 w-5 transition-transform duration-300 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                </a>
                            </div>
                        </div>

                        {{-- Visual/Interactive Element --}}
                        <div class="relative mx-auto w-full max-w-md lg:mx-0">
                            {{-- Mock Interface --}}
                            <div class="relative rounded-2xl bg-slate-800/50 border border-slate-700/50 p-2 backdrop-blur-xl shadow-2xl">
                                {{-- Floating Badges --}}
                                <div class="absolute -top-6 -right-6 animate-bounce delay-700 duration-3000">
                                    <div class="flex items-center gap-2 rounded-lg bg-slate-900/90 border border-red-500/30 px-4 py-2 text-sm font-semibold text-red-400 shadow-lg backdrop-blur-md">
                                        <span class="h-2 w-2 rounded-full bg-red-500"></span>
                                        High Spike
                                    </div>
                                </div>
                                <div class="absolute -bottom-4 -left-4 animate-bounce delay-1000 duration-4000">
                                    <div class="flex items-center gap-2 rounded-lg bg-slate-900/90 border border-emerald-500/30 px-4 py-2 text-sm font-semibold text-emerald-400 shadow-lg backdrop-blur-md">
                                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                        Stable Glucose
                                    </div>
                                </div>

                                {{-- Window Chrome --}}
                                <div class="space-y-4 rounded-xl bg-slate-900/80 p-6 border border-slate-800">
                                    <div class="flex items-center gap-3 border-b border-slate-800 pb-4">
                                        <div class="flex gap-1.5">
                                            <div class="h-3 w-3 rounded-full bg-slate-700"></div>
                                            <div class="h-3 w-3 rounded-full bg-slate-700"></div>
                                            <div class="h-3 w-3 rounded-full bg-slate-700"></div>
                                        </div>
                                        <div class="ml-auto text-xs font-medium text-slate-500">AI Analysis</div>
                                    </div>

                                    {{-- Chat/Input Area --}}
                                    <div class="space-y-4">
                                        {{-- User Message --}}
                                        <div class="flex justify-end">
                                            <div class="rounded-2xl rounded-tr-sm bg-orange-500 px-4 py-2 text-sm font-medium text-white">
                                                Is a bagel healthy?
                                            </div>
                                        </div>

                                        {{-- AI Response --}}
                                        <div class="flex gap-3">
                                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-linear-to-br from-orange-500 to-amber-500 text-white">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                </svg>
                                            </div>
                                            <div class="space-y-2">
                                                <div class="rounded-2xl rounded-tl-sm bg-slate-800 px-4 py-3 text-sm text-slate-300">
                                                    <p class="font-medium text-white mb-1">High Glucose Spike Risk ⚠️</p>
                                                    <p class="text-xs leading-relaxed text-slate-400">Bagels are dense in refined carbs. Pair with protein or fat to reduce the spike.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Input Field Mock --}}
                                    <div class="relative mt-2">
                                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                            <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                        </div>
                                        <div class="block w-full rounded-lg border border-slate-700 bg-slate-800 py-2.5 pl-10 pr-3 text-sm text-slate-400">
                                            Type a food...
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Features Section --}}
            <section class="w-full max-w-6xl">
                <div class="space-y-4 lg:space-y-6">
                    <div class="text-center">
                        <h2 class="text-2xl font-bold text-slate-900 lg:text-3xl">Data-Driven Glucose Control</h2>
                        <p class="mt-2 text-sm text-slate-600 lg:text-base">AI-powered precision for effortless diabetes diet management</p>
                    </div>

                    <div class="grid grid-cols-1 gap-3 lg:grid-cols-4 lg:gap-4">
                        <div
                            class="group/card rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-300 hover:border-emerald-500 hover:bg-slate-50 hover:shadow-md">
                            <div class="flex flex-col items-center text-center">
                                <div
                                    class="mb-3 rounded-lg bg-emerald-100 p-3 transition-transform duration-300 group-hover/card:scale-110">
                                    <svg class="h-6 w-6 text-emerald-600" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                </div>
                                <h3 class="text-sm font-semibold text-slate-900 lg:text-base">Build For You</h3>
                                <p class="mt-2 text-xs text-slate-600 lg:text-sm">Meal plans tailored to your glucose responses, not generic advice</p>
                            </div>
                        </div>

                        <div
                            class="group/card rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-300 hover:border-teal-500 hover:bg-slate-50 hover:shadow-md">
                            <div class="flex flex-col items-center text-center">
                                <div
                                    class="mb-3 rounded-lg bg-teal-100 p-3 transition-transform duration-300 group-hover/card:scale-110">
                                    <svg class="h-6 w-6 text-teal-600" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                    </svg>
                                </div>
                                <h3 class="text-sm font-semibold text-slate-900 lg:text-base">Simple
                                    Choices</h3>
                                <p class="mt-2 text-xs text-slate-600 lg:text-sm">Clear food suggestions help you decide what to eat daily</p>
                            </div>
                        </div>

                        <div
                            class="group/card rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-300 hover:border-cyan-500 hover:bg-slate-50 hover:shadow-md">
                            <div class="flex flex-col items-center text-center">
                                <div
                                    class="mb-3 rounded-lg bg-cyan-100 p-3 transition-transform duration-300 group-hover/card:scale-110">
                                    <svg class="h-6 w-6 text-cyan-600" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                               <h3 class="text-sm font-semibold text-slate-900 lg:text-base">
                                    Stay Ahead</h3>
                                <p class="mt-2 text-xs text-slate-600 lg:text-sm">Pick foods that help keep your blood sugar stable</p>
                            </div>
                        </div>

                        <div
                            class="group/card rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-300 hover:border-purple-500 hover:bg-slate-50 hover:shadow-md">
                            <div class="flex flex-col items-center text-center">
                                <div
                                    class="mb-3 rounded-lg bg-purple-100 p-3 transition-transform duration-300 group-hover/card:scale-110">
                                    <svg class="h-6 w-6 text-purple-600" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                    </svg>
                                </div>
                               <h3 class="text-sm font-semibold text-slate-900 lg:text-base">Know
                                    More</h3>
                                <p class="mt-2 text-xs text-slate-600 lg:text-sm">Learn how food affects you so you can eat with confidence</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- How It Works Section --}}
            <section class="w-full max-w-6xl">
                <div class="space-y-4 lg:space-y-6">
                    <div class="text-center">
                        <h2 class="text-2xl font-bold text-slate-900 lg:text-3xl">How It Works</h2>
                        <p class="mt-2 text-sm text-slate-600 lg:text-base">Your AI-powered glucose navigator in three simple steps</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3 lg:gap-6">
                        {{-- Step 1 --}}
                        <div class="relative rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="absolute -top-3 left-5 flex h-7 w-7 items-center justify-center rounded-full bg-emerald-600 text-sm font-bold text-white">1</div>
                            <div class="pt-2">
                                <h3 class="text-base font-semibold text-slate-900 lg:text-lg">Set Up Your Profile</h3>
                                <p class="mt-2 text-xs leading-relaxed text-slate-600 lg:text-sm">
                                    Tell us about your glucose readings, goals, and foods you like. We keep your data private and use it to build your plan.
                                </p>
                            </div>
                        </div>

                        {{-- Step 2 --}}
                        <div class="relative rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="absolute -top-3 left-5 flex h-7 w-7 items-center justify-center rounded-full bg-teal-600 text-sm font-bold text-white">2</div>
                            <div class="pt-2">
                                <h3 class="text-base font-semibold text-slate-900 lg:text-lg">AI Analyzes Patterns</h3>
                                <p class="mt-2 text-xs leading-relaxed text-slate-600 lg:text-sm">
                                    Our AI identifies how your body responds to different foods and creates a nutrition strategy optimized for <strong class="text-slate-800">your</strong> glucose stability.
                                </p>
                            </div>
                        </div>

                        {{-- Step 3 --}}
                        <div class="relative rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="absolute -top-3 left-5 flex h-7 w-7 items-center justify-center rounded-full bg-cyan-600 text-sm font-bold text-white">3</div>
                            <div class="pt-2">
                                <h3 class="text-base font-semibold text-slate-900 lg:text-lg">Eat, Track, Improve</h3>
                                <p class="mt-2 text-xs leading-relaxed text-slate-600 lg:text-sm">
                                    Follow your personalized meal plan, log your glucose, and watch your plan adapt in real-time. <strong class="text-emerald-600">See measurable results</strong>.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Open Source Section --}}
            <section class="w-full max-w-6xl">
                <div class="relative overflow-hidden rounded-3xl bg-slate-900 px-6 py-12 shadow-2xl sm:px-12 sm:py-16 lg:px-16">
                    {{-- Background Effects --}}
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,var(--tw-gradient-stops))] from-emerald-500/20 via-slate-900 to-slate-900"></div>
                    <div class="absolute -left-12 -top-12 h-64 w-64 rounded-full bg-emerald-500/10 blur-3xl"></div>
                    <div class="absolute -bottom-12 -right-12 h-64 w-64 rounded-full bg-teal-500/10 blur-3xl"></div>

                    <div class="relative flex flex-col items-center text-center">
                        {{-- Badge --}}
                        <div class="mb-8 inline-flex items-center gap-2 rounded-full bg-emerald-500/10 px-3 py-1 text-sm font-medium text-emerald-400 ring-1 ring-inset ring-emerald-500/20">
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                            </span>
                            One more thing...
                        </div>

                        {{-- Content --}}
                        <h2 class="mb-6 text-3xl font-bold tracking-tight text-white sm:text-4xl">
                            Acara Plate is <span class="text-transparent bg-clip-text bg-linear-to-r from-emerald-400 to-teal-400">Open Source</span>
                        </h2>

                        <p class="mb-10 max-w-2xl text-lg leading-relaxed text-slate-400">
                            Acara Plate is open source and 100% free to self-host. Whether you want to customize the platform for your specific needs or simply prefer running your own instance, the choice is yours. Have a feature in mind? We welcome pull requests to improve the product for everyone.
                        </p>

                        {{-- CTA Button --}}
                        <a href="https://github.com/acara-app/plate" target="_blank"
                           class="group inline-flex items-center justify-center gap-2 rounded-xl bg-white px-8 py-4 text-base font-bold text-slate-900 shadow-lg shadow-emerald-500/10 transition-all hover:scale-105 hover:bg-emerald-50 hover:shadow-emerald-500/20 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-slate-900">
                            <svg class="h-5 w-5 transition-transform duration-300 group-hover:scale-110" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
                            </svg>
                            View on GitHub
                        </a>
                    </div>
                </div>
            </section>

            {{-- FAQ Section --}}
            <section class="w-full max-w-6xl">
                <div class="space-y-4 lg:space-y-6">
                    <div class="text-center">
                        <h2 class="text-2xl font-bold text-slate-900 lg:text-3xl">Common Questions</h2>
                        <p class="mt-2 text-sm text-slate-600 lg:text-base">Learn more about Acara Plate</p>
                    </div>

                    <div class="space-y-3">
                        <details
                            class="group rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-300 hover:border-emerald-500 hover:bg-slate-50">
                            <summary
                                class="flex cursor-pointer items-start justify-between gap-3 text-sm font-semibold text-slate-900 lg:text-base">
                                <h3 class="inline">How accurate are the nutritional values in meal plans?</h3>
                                <svg aria-hidden="true" class="mt-1 h-5 w-5 shrink-0 text-slate-400 transition-transform group-open:rotate-180"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </summary>
                            <p class="mt-3 text-xs leading-relaxed text-slate-600 lg:text-sm">
                                Acara Plate uses AI-generated meal plans with carefully selected ingredients from the
                                USDA FoodData Central database. We strive for accuracy by leveraging established
                                nutritional data sources. However, since meal plans are AI-generated, we recommend
                                verifying key nutritional information and consulting with your healthcare provider for
                                personalized dietary guidance.
                            </p>
                        </details>

                        <details
                            class="group rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-300 hover:border-emerald-500 hover:bg-slate-50">
                            <summary
                                class="flex cursor-pointer items-start justify-between gap-3 text-sm font-semibold text-slate-900 lg:text-base">
                                <h3 class="inline">Can AI hallucinate incorrect food information?</h3>
                                <svg aria-hidden="true" class="mt-1 h-5 w-5 shrink-0 text-slate-400 transition-transform group-open:rotate-180"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </summary>
                            <p class="mt-3 text-xs leading-relaxed text-slate-600 lg:text-sm">
                                Yes, AI can occasionally hallucinate or generate incorrect information about food,
                                ingredients, or nutritional values. This is a known limitation of language models. We
                                recommend always verifying key ingredients for allergens and consulting your healthcare
                                provider before making significant dietary changes based on meal plan suggestions.
                            </p>
                        </details>

                        <details
                            class="group rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-300 hover:border-emerald-500 hover:bg-slate-50">
                            <summary
                                class="flex cursor-pointer items-start justify-between gap-3 text-sm font-semibold text-slate-900 lg:text-base">
                                <h3 class="inline">Is this a medical app or diagnostic tool?</h3>
                                <svg aria-hidden="true" class="mt-1 h-5 w-5 shrink-0 text-slate-400 transition-transform group-open:rotate-180"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </summary>
                            <p class="mt-3 text-xs leading-relaxed text-slate-600 lg:text-sm">
                                No. Acara Plate is an informational and educational tool, not a medical device. It does
                                not diagnose, treat, or manage any medical condition. The glucose tracking feature helps
                                you monitor trends, but all meal plans and health decisions should be discussed with
                                your healthcare provider. This platform is intended for adults as a supplementary
                                nutrition planning tool.
                            </p>
                        </details>

                        <details
                            class="group rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-300 hover:border-emerald-500 hover:bg-slate-50">
                            <summary
                                class="flex cursor-pointer items-start justify-between gap-3 text-sm font-semibold text-slate-900 lg:text-base">
                                <h3 class="inline">Why is Acara Plate open source?</h3>
                                <svg aria-hidden="true" class="mt-1 h-5 w-5 shrink-0 text-slate-400 transition-transform group-open:rotate-180"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </summary>
                            <p class="mt-3 text-xs leading-relaxed text-slate-600 lg:text-sm">
                                Transparency is crucial for health-related tools. Being open source allows healthcare
                                professionals, developers, and users to inspect how meal plans are generated, how
                                nutritional data is verified, and how AI is used. You can review the code on <a
                                    href="https://github.com/acara-app/plate" target="_blank"
                                    class="font-semibold text-emerald-600 hover:underline">GitHub</a>,
                                contribute improvements, and verify that the platform operates as described.
                            </p>
                        </details>

                        <details
                            class="group rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-300 hover:border-emerald-500 hover:bg-slate-50">
                            <summary
                                class="flex cursor-pointer items-start justify-between gap-3 text-sm font-semibold text-slate-900 lg:text-base">
                                <h3 class="inline">How do you ensure nutritional accuracy?</h3>
                                <svg aria-hidden="true" class="mt-1 h-5 w-5 shrink-0 text-slate-400 transition-transform group-open:rotate-180"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </summary>
                            <p class="mt-3 text-xs leading-relaxed text-slate-600 lg:text-sm">
                                We reference the <a href="https://fdc.nal.usda.gov/" target="_blank"
                                    class="font-semibold text-emerald-600 hover:underline">USDA
                                    FoodData Central</a> database—the scientific gold standard for nutrition of whole
                                foods like bananas, chicken breast, and rice. However, as meal plans are AI-generated,
                                we recommend verifying nutritional information independently and consulting with your
                                healthcare provider for personalized guidance.
                            </p>
                        </details>

                        <details
                            class="group rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-300 hover:border-emerald-500 hover:bg-slate-50">
                            <summary
                                class="flex cursor-pointer items-start justify-between gap-3 text-sm font-semibold text-slate-900 lg:text-base">
                                <h3 class="inline">Who should use Plate?</h3>
                                <svg aria-hidden="true" class="mt-1 h-5 w-5 shrink-0 text-slate-400 transition-transform group-open:rotate-180"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </summary>
                            <p class="mt-3 text-xs leading-relaxed text-slate-600 lg:text-sm">
                                Acara Plate is designed for adults seeking personalized meal planning guidance,
                                particularly those managing Type 2 diabetes or prediabetes. It's useful for anyone
                                wanting structured nutrition plans based on their goals, dietary preferences, and health
                                conditions. However, it should complement—not replace—professional medical advice and
                                supervision from your healthcare team.
                            </p>
                        </details>

                        <details
                            class="group rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-300 hover:border-emerald-500 hover:bg-slate-50">
                            <summary
                                class="flex cursor-pointer items-start justify-between gap-3 text-sm font-semibold text-slate-900 lg:text-base">
                                <h3 class="inline">Is there a mobile app?</h3>
                                <svg aria-hidden="true" class="mt-1 h-5 w-5 shrink-0 text-slate-400 transition-transform group-open:rotate-180"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </summary>
                            <p class="mt-3 text-xs leading-relaxed text-slate-600 lg:text-sm">
                                Yes! Acara Plate is a Progressive Web App (PWA), which means you can install it directly
                                on your device without visiting an app store. Visit our <a
                                    href="{{ route('install-app') }}"
                                    class="font-semibold text-emerald-600 hover:underline">installation
                                    guide</a> to learn how to add it to your home screen for a native app-like
                                experience.
                            </p>
                        </details>
                    </div>
                </div>
            </section>

            {{-- Medical Disclaimer --}}
            <section class="w-full max-w-6xl">
                <div
                    class="rounded-xl border border-amber-200 bg-amber-50 p-4 lg:p-6">
                    <div class="flex items-start gap-3 lg:gap-4">
                        <div
                            class="shrink-0 rounded-full bg-amber-100 p-2 text-amber-600">
                            <svg class="h-5 w-5 lg:h-6 lg:w-6" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-amber-900 lg:text-base">Medical
                                Disclaimer</h3>
                            <p class="mt-1 text-xs leading-relaxed text-amber-700 lg:text-sm">
                                Acara Plate is an AI-powered tool designed for informational and educational purposes
                                only.
                                The meal plans, nutritional insights, and glucose tracking features are
                                <strong>not</strong> a substitute for professional medical advice, diagnosis, or
                                treatment. Always seek the advice of your physician or other qualified health provider
                                with any questions you may have regarding a medical condition.
                            </p>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
    <x-footer />

    <style>
        @keyframes float {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-20px);
            }
        }

        .animate-float {
            animation: float 6s ease-in-out infinite;
        }

        .animation-delay-1000 {
            animation-delay: 1s;
        }

        .animation-delay-2000 {
            animation-delay: 2s;
        }

        .animation-delay-4000 {
            animation-delay: 4s;
        }
    </style>
</x-default-layout>
