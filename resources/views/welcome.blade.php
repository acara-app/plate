@section('title', 'AI Food & Health Tracker | Acara Plate')
@section('meta_description', 'Estimate calories and macros from a meal photo, then track meals, sleep, water, activity, and daily check-ins with Acara Plate.')
@section('meta_keywords', 'AI food tracker, health tracker, photo food log, calorie and macro tracker, daily health log, meal photo nutrition, sleep and water tracking')
@section('og_image_alt', 'Acara Plate - AI Food & Health Tracker')

@php
    $frequentlyAskedQuestions = [
        [
            'question' => 'How does Snap to Track work?',
            'answer' => 'Take or upload a meal photo to get AI estimates of calories, protein, carbs, fat, and fiber. Review the ingredients and portions, make any corrections, and save the meal to your Logbook with an account.',
        ],
        [
            'question' => 'Can I try it without signing up?',
            'answer' => 'Yes. Try one free scan with Snap to Track without creating an account or entering a card. Create an account to save meals to your Logbook. Further photo scans depend on your plan allowance.',
        ],
        [
            'question' => 'What can I track in Logbook?',
            'answer' => 'Keep meals, calories, protein, carbs, fat, fiber, water, sleep, activity, and daily check-ins together. You can also log readings such as weight, blood pressure, and glucose. Browse past days to revisit your entries.',
        ],
        [
            'question' => 'How accurate are photo nutrition estimates?',
            'answer' => 'Photo nutrition values are AI estimates, not measurements. Lighting, portion sizes, and hidden ingredients such as oils and sauces can affect the result. Review and correct the analysis before saving it, and verify important nutritional information independently.',
        ],
        [
            'question' => 'Who is Acara Plate for?',
            'answer' => 'Acara Plate is for adults who want a simpler way to record meals and keep everyday health habits in view. Use it to follow nutrition goals, log daily check-ins, and keep a personal history of your food and health entries.',
        ],
        [
            'question' => 'Is this a medical app or diagnostic tool?',
            'answer' => 'No. Acara Plate is an informational and educational wellness tool, not a medical device. It does not diagnose, treat, or manage medical conditions. Discuss health decisions and significant dietary changes with a qualified healthcare professional.',
        ],
        [
            'question' => 'Is there a mobile app?',
            'answer' => 'Use Plate in your mobile browser or install it on your home screen as a Progressive Web App. The separate Acara Health Sync iPhone app can bring Apple Health data, including steps and sleep, into Plate.',
        ],
        [
            'question' => 'Why is Acara Plate open source?',
            'answer' => 'Transparency matters when you record food and health information. You can inspect the code on GitHub, contribute improvements, or self-host Plate for control over your own instance.',
        ],
    ];
@endphp

<x-default-layout>
    @section('head')
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "WebSite",
        "name": "Acara Plate",
        "url": "{{ url('/') }}"
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
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "FAQPage",
        "mainEntity": [
            @foreach ($frequentlyAskedQuestions as $faq)
                {
                    "@@type": "Question",
                    "name": @json($faq['question']),
                    "acceptedAnswer": {
                        "@@type": "Answer",
                        "text": @json($faq['answer'])
                    }
                }@if (! $loop->last),@endif
            @endforeach
        ]
    }
    </script>
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "WebPage",
        "name": "AI Food & Health Tracker | Acara Plate",
        "description": "Estimate calories and macros from a meal photo, then track meals, sleep, water, activity, and daily check-ins with Acara Plate.",
        "url": "{{ url('/') }}",
        "speakable": {
            "@@type": "SpeakableSpecification",
            "cssSelector": [".speakable-intro"]
        },
        "isPartOf": {
            "@@type": "WebSite",
            "name": "Acara Plate",
            "url": "{{ url('/') }}"
        }
    }
    </script>
    @endsection
    <div class="relative min-h-screen bg-white">
        <x-tools-header />

        <section aria-labelledby="hero-heading" class="relative isolate overflow-hidden bg-[#FFFBF5] py-12 sm:py-16 lg:py-14">
            <div aria-hidden="true" class="pointer-events-none absolute -right-32 top-20 -z-10 size-[500px] rounded-full bg-[#FFDCCB]/40 blur-3xl"></div>
            <div class="mx-auto grid max-w-7xl items-center gap-12 px-4 sm:px-6 lg:grid-cols-[1fr_1.05fr] lg:gap-10 lg:px-8">
                <div class="speakable-intro min-w-0 text-center lg:text-left">
                    <p class="mb-6 inline-flex items-center gap-2 rounded-full border border-[#FF6B4A]/20 bg-white/80 px-4 py-2 font-mono text-[10px] uppercase tracking-[0.12em] text-[#9A3E29] sm:text-[11px]">
                        <span aria-hidden="true" class="size-1.5 rounded-full bg-[#FF6B4A]"></span>
                        AI food &amp; health tracking
                    </p>
                    <h1 id="hero-heading" class="font-display text-[2.625rem] font-bold leading-[1.12] tracking-tight text-slate-900 sm:text-6xl lg:text-5xl xl:text-[4rem]">
                        <span class="block">Snap your meals.</span>
                        <span class="mt-1 block text-[#C94F35]">Track your everyday health.</span>
                    </h1>
                    <p class="mx-auto mt-6 max-w-xl text-base leading-7 text-slate-600 sm:text-lg sm:leading-8 lg:mx-0">
                        Estimate calories and macros from a photo. Keep meals, sleep, water, activity, and daily check-ins together in your personal health log.
                    </p>
                    <div class="mt-8 flex flex-col items-stretch justify-center gap-3 sm:flex-row sm:flex-wrap lg:justify-start">
                        <div class="flex flex-col items-stretch gap-3">
                            <a href="{{ route('snap-to-track') }}"
                               data-umami-event="snap_to_track_cta_click"
                               data-umami-event-location="home_hero"
                               aria-describedby="hero-scan-note"
                               class="inline-flex min-h-12 items-center justify-center gap-2 rounded-full bg-[#C94F35] px-7 py-3.5 text-sm font-semibold text-white shadow-lg shadow-[#FF6B4A]/15 transition hover:bg-[#AF402A] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#C94F35] sm:text-base">
                                <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 0 1 2-2h2l2-2h6l2 2h2a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z" />
                                    <circle cx="12" cy="12" r="4" />
                                </svg>
                                Try Snap to Track
                            </a>
                            <p id="hero-scan-note" class="text-center text-xs leading-5 text-slate-500">Try one free scan. No signup required.</p>
                        </div>
                        <a href="{{ route('register') }}"
                           data-umami-event="signup_cta_click"
                           data-umami-event-location="home_hero"
                           class="inline-flex min-h-12 items-center justify-center self-stretch rounded-full border border-[#D9CFC3] bg-white px-7 py-3.5 text-sm font-semibold text-slate-800 transition hover:border-[#C94F35] hover:bg-orange-50 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#C94F35] sm:self-start sm:text-base">
                            Create My Health Log
                        </a>
                    </div>
                    <div class="mt-6 flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-xs text-slate-500 sm:text-sm lg:justify-start">
                        <span class="inline-flex items-center gap-1.5"><span aria-hidden="true" class="size-1.5 rounded-full bg-emerald-500"></span>Calories &amp; macros</span>
                        <span class="inline-flex items-center gap-1.5"><span aria-hidden="true" class="size-1.5 rounded-full bg-violet-400"></span>Daily check-ins</span>
                        <span class="inline-flex items-center gap-1.5"><span aria-hidden="true" class="size-1.5 rounded-full bg-blue-400"></span>Your personal logbook</span>
                    </div>
                </div>

                <figure class="mx-auto w-full min-w-0 max-w-[600px]" aria-labelledby="logbook-preview-caption">
                    <div class="relative isolate aspect-[1/1.5] sm:aspect-[1/1.06]">
                        <div aria-hidden="true" class="absolute inset-[12%] -z-10 rounded-full bg-[#F4DDC9]/40 blur-3xl"></div>

                        <img src="{{ asset('health-log-hero-wellness.webp') }}"
                             alt="Sample daily check-ins: 7.5 hours of sleep, 7,240 steps, and low stress."
                             width="1200" height="400"
                             loading="eager" decoding="async"
                             class="absolute right-[2%] top-0 z-20 w-[90%] rotate-3 drop-shadow-[0_12px_20px_rgba(72,45,27,0.09)] sm:w-[76%]">

                        <img src="{{ asset('health-log-hero-nutrition.webp') }}"
                             alt="Logbook nutrition overview with a sample 1,436 calorie total and protein, carbs, fat, and fiber goals."
                             width="1000" height="1310"
                             loading="eager" fetchpriority="high" decoding="async"
                             class="absolute left-0 top-[25%] z-10 w-[59%] -rotate-3 drop-shadow-[0_22px_28px_rgba(72,45,27,0.12)] sm:w-[55%]">

                        <div class="absolute right-[1%] top-[30%] w-[34%] -rotate-3 rounded-2xl border border-slate-200/80 bg-white p-3 shadow-[0_12px_24px_-10px_rgba(72,45,27,0.16)] sm:rounded-3xl sm:p-5">
                            <div class="flex items-center gap-1.5 text-[9px] text-slate-500 sm:gap-2 sm:text-xs">
                                <svg class="size-4 shrink-0 text-blue-500 sm:size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 3h14l-2 18H7L5 3Zm1 7c2-2 4 2 6 0s4 2 6 0" />
                                </svg>
                                Today's water
                            </div>
                            <p class="mt-3 flex flex-wrap items-baseline gap-x-0.5 sm:mt-4">
                                <span class="text-xl font-bold tracking-tight text-slate-900 tabular-nums sm:text-3xl">1,500</span>
                                <span class="text-[9px] text-slate-400 tabular-nums sm:text-xs">/2,000 ml</span>
                            </p>
                            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-blue-100 sm:mt-4 sm:h-2" aria-hidden="true">
                                <div class="h-full w-3/4 rounded-full bg-blue-500"></div>
                            </div>
                        </div>

                        <img src="{{ asset('health-log-hero-meal.webp') }}"
                             alt="Sample meal photo saved to Logbook: chicken quinoa salad with 606 calories and a macro breakdown."
                             width="1000" height="417"
                             loading="eager" decoding="async"
                             class="absolute right-[2%] top-[75%] z-20 w-[85%] rotate-3 drop-shadow-[0_16px_24px_rgba(72,45,27,0.12)] sm:-right-[2%] sm:top-[62%] sm:w-[50%]">
                    </div>
                    <figcaption id="logbook-preview-caption" class="mt-5 text-center font-mono text-[10px] uppercase tracking-[0.14em] text-slate-500">Sample day in Logbook</figcaption>
                </figure>
            </div>
        </section>

        <main class="relative z-10 flex flex-col items-center gap-24 px-4 pb-24 pt-16 sm:px-6 lg:px-8">
            <section class="w-full max-w-6xl">
                <x-cta-block
                    title="Meet Altani — Your Personal AI Health Coach"
                    description="Bring your food and health questions to Altani. Get help reviewing your logged meals, planning what to eat, and building everyday routines around your goals."
                    buttonText="Ask your questions"
                    buttonUrl="{{ route('meet-altani') }}"
                />
            </section>

            <section class="w-full max-w-6xl">
                <div class="overflow-hidden rounded-3xl border border-orange-100 bg-white shadow-sm">
                    <div class="grid gap-8 p-8 sm:p-12 lg:grid-cols-[1.1fr_1fr] lg:items-center">
                        <div>
                            <p class="text-sm font-bold uppercase tracking-[0.18em] text-[#FF6B4A]">Acara Cloud Memory</p>
                            <h2 class="mt-4 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                                Acara remembers what works for you.
                            </h2>
                            <p class="mt-4 text-lg leading-relaxed text-slate-600">
                                Acara Cloud keeps a saved health context across future chats: food preferences, goals, routines, household constraints, cooking limits, recurring patterns, and feedback you have already shared.
                            </p>
                            <p class="mt-4 text-sm leading-relaxed text-slate-500">
                                If you self-host Plate, you still get the transparent open-source foundation. If you use Acara Cloud, long-term memory is handled for you so guidance can feel more personal without making you repeat yourself.
                            </p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl bg-orange-50 p-5">
                                <h3 class="font-semibold text-slate-900">Stable preferences</h3>
                                <p class="mt-2 text-sm leading-relaxed text-slate-600">Meals you like, foods you avoid, and the cooking style you keep coming back to.</p>
                            </div>
                            <div class="rounded-2xl bg-emerald-50 p-5">
                                <h3 class="font-semibold text-slate-900">Goals in context</h3>
                                <p class="mt-2 text-sm leading-relaxed text-slate-600">Blood sugar, protein, hydration, activity, and routine targets can stay part of future chats.</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-5">
                                <h3 class="font-semibold text-slate-900">Less repeating</h3>
                                <p class="mt-2 text-sm leading-relaxed text-slate-600">Advice can start from what Acara already knows instead of asking the same questions again.</p>
                            </div>
                            <div class="rounded-2xl bg-rose-50 p-5">
                                <h3 class="font-semibold text-slate-900">Profile-safe</h3>
                                <p class="mt-2 text-sm leading-relaxed text-slate-600">Allergies, medications, conditions, biometrics, and household context stay structured.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Acara Health Sync Launch Announcement --}}
            <section aria-labelledby="health-sync-launch-heading" class="w-full max-w-6xl">
                <div class="relative overflow-hidden rounded-3xl border border-emerald-100 bg-linear-to-br from-emerald-50 via-white to-emerald-50/40 p-8 shadow-sm sm:p-12">
                    <div aria-hidden="true" class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-emerald-400/10 blur-3xl"></div>
                    <div aria-hidden="true" class="pointer-events-none absolute -left-16 -bottom-16 h-56 w-56 rounded-full bg-emerald-300/10 blur-3xl"></div>

                    <div class="relative grid gap-10 lg:grid-cols-5 lg:items-center">
                        <div class="lg:col-span-3">
                            <span class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-emerald-800">
                                <span class="relative flex h-2 w-2" aria-hidden="true">
                                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                                </span>
                                Just launched on iPhone
                            </span>
                            <h2 id="health-sync-launch-heading" class="mt-4 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">
                                Your Apple Health data, where you actually use it.
                            </h2>
                            <p class="mt-4 text-lg leading-relaxed text-slate-600">
                                <strong class="text-slate-900">Acara Health Sync</strong> reads glucose, weight, sleep, and 100+ other health types from Apple Health, encrypts them on your phone, and sends them straight to your Plate dashboard. No middleman, no manual entry, no cloud storage in between.
                            </p>
                            <div class="mt-6 flex flex-wrap items-center gap-4 text-sm text-slate-600">
                                <span class="inline-flex items-center gap-1.5">
                                    <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                    End-to-end encrypted
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                    100+ health types
                                </span>
                                <span class="inline-flex items-center gap-1.5">
                                    <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Open source
                                </span>
                            </div>
                        </div>
                        <div class="flex flex-col items-start gap-4 lg:col-span-2 lg:items-end">
                            <x-app-store-badge size="lg" />
                            <a href="{{ route('health-sync') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-emerald-700 hover:underline">
                                See how it works
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </a>
                            <p class="text-xs text-slate-500">Requires iOS {{ config('plate.health_sync.minimum_ios_version') }} or later. Free.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="w-full max-w-6xl">
                <div class="space-y-6">
                    <div class="text-center">
                        <h2 class="text-2xl font-bold text-slate-900 lg:text-3xl">Smart Tools for Better Choices</h2>
                        <p class="mt-2 text-slate-600">Quick, simple ways to understand your food and stay on track</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        {{-- AI Nutritionist Card --}}
                        <div class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition-all hover:border-rose-300 hover:shadow-md">
                            <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-rose-100 text-rose-600 transition-transform group-hover:scale-110">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-slate-900">AI Nutritionist</h3>
                            <p class="mt-2 text-sm text-slate-600">
                                Need meal ideas or help with a menu? Ask the AI for suggestions around your food preferences and nutrition goals.
                            </p>
                            <a href="{{ route('ai-nutritionist') }}"
                               class="mt-4 inline-flex items-center text-sm font-semibold text-rose-600 hover:text-rose-700"
                               aria-label="Meet Your AI Coach (AI Nutritionist)">
                                Meet Your AI Coach
                                <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>

                        {{-- Spike Calculator Card --}}
                        <div class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition-all hover:border-orange-300 hover:shadow-md">
                            <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-orange-100 text-orange-600 transition-transform group-hover:scale-110">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-slate-900">Spike Calculator</h3>
                            <p class="mt-2 text-sm text-slate-600">
                                Type any food and instantly see its glucose impact. No signup needed.
                            </p>
                            <a href="{{ route('spike-calculator') }}"
                               class="mt-4 inline-flex items-center text-sm font-semibold text-orange-600 hover:text-orange-700"
                               aria-label="Check a food (Spike Calculator)">
                                Check a food
                                <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>

                        {{-- Snap to Track Card --}}
                        <div class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition-all hover:border-blue-300 hover:shadow-md">
                            <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 text-blue-600 transition-transform group-hover:scale-110">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-slate-900">Snap to Track</h3>
                            <p class="mt-2 text-sm text-slate-600">
                                Estimate calories and macros from a photo, review the result, and save your meal to Logbook.
                            </p>
                            <a href="{{ route('snap-to-track') }}"
                               class="mt-4 inline-flex items-center text-sm font-semibold text-blue-600 hover:text-blue-700"
                               aria-label="Scan a meal (Snap to Track)">
                                Scan a meal
                                <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>


                    </div>
                </div>
            </section>

            <section class="w-full max-w-6xl">
                <div class="space-y-4 lg:space-y-6">
                    <div class="text-center">
                        <h2 class="text-2xl font-bold text-slate-900 lg:text-3xl">Your Food and Daily Health, Together</h2>
                        <p class="mt-2 text-sm text-slate-600 lg:text-base">A personal logbook for meals, nutrition goals, and everyday habits</p>
                    </div>

                    <div class="grid grid-cols-1 gap-3 lg:grid-cols-4 lg:gap-4">
                        <div
                            class="group/card rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-300 hover:border-orange-400 hover:bg-slate-50 hover:shadow-md">
                            <div class="flex flex-col items-center text-center">
                                <div
                                    class="mb-3 rounded-lg bg-orange-100 p-3 transition-transform duration-300 group-hover/card:scale-110">
                                    <svg class="h-6 w-6 text-orange-600" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                </div>
                                <h3 class="text-sm font-semibold text-slate-900 lg:text-base">Photo Food Logging</h3>
                                <p class="mt-2 text-xs text-slate-600 lg:text-sm">Scan a meal, review the ingredients, and save it to your day</p>
                            </div>
                        </div>

                        <div
                            class="group/card rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-300 hover:border-orange-400 hover:bg-slate-50 hover:shadow-md">
                            <div class="flex flex-col items-center text-center">
                                <div
                                    class="mb-3 rounded-lg bg-orange-100 p-3 transition-transform duration-300 group-hover/card:scale-110">
                                    <svg class="h-6 w-6 text-orange-600" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                    </svg>
                                </div>
                                <h3 class="text-sm font-semibold text-slate-900 lg:text-base">Calories &amp; Macros</h3>
                                <p class="mt-2 text-xs text-slate-600 lg:text-sm">See daily calorie totals and track protein, carbs, fat, and fiber against your goals</p>
                            </div>
                        </div>

                        <div
                            class="group/card rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-300 hover:border-orange-400 hover:bg-slate-50 hover:shadow-md">
                            <div class="flex flex-col items-center text-center">
                                <div
                                    class="mb-3 rounded-lg bg-orange-100 p-3 transition-transform duration-300 group-hover/card:scale-110">
                                    <svg class="h-6 w-6 text-orange-600" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                                <h3 class="text-sm font-semibold text-slate-900 lg:text-base">
                                    Daily Check-ins</h3>
                                <p class="mt-2 text-xs text-slate-600 lg:text-sm">Log sleep, stress, and water, with activity from your connected health data</p>
                            </div>
                        </div>

                        <div
                            class="group/card rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-300 hover:border-orange-400 hover:bg-slate-50 hover:shadow-md">
                            <div class="flex flex-col items-center text-center">
                                <div
                                    class="mb-3 rounded-lg bg-orange-100 p-3 transition-transform duration-300 group-hover/card:scale-110">
                                    <svg class="h-6 w-6 text-orange-600" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                    </svg>
                                </div>
                                <h3 class="text-sm font-semibold text-slate-900 lg:text-base">Your Daily History</h3>
                                <p class="mt-2 text-xs text-slate-600 lg:text-sm">Browse previous days and keep meals and health entries in one place</p>
                            </div>
                        </div>

                        <div
                            class="group/card rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-300 hover:border-indigo-500 hover:bg-slate-50 hover:shadow-md lg:col-span-2">
                            <div class="flex flex-col items-center text-center lg:flex-row lg:items-start lg:text-left lg:gap-6">
                                <div
                                    class="mb-3 rounded-lg bg-indigo-100 p-3 transition-transform duration-300 group-hover/card:scale-110 shrink-0">
                                    <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-sm font-semibold text-slate-900 lg:text-base">AI Health Coach</h3>
                                    <p class="mt-2 text-xs text-slate-600 lg:text-sm">
                                        Struggling with sleep, stress, or hydration? Your AI wellness coach provides personalized routines and guidance for a healthier lifestyle.
                                    </p>
                                    <a href="{{ route('ai-health-coach') }}"
                                        class="mt-3 inline-flex items-center text-xs font-medium text-indigo-600 hover:text-indigo-700 lg:text-sm">
                                        Meet Your Health Coach
                                        <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div
                            class="group/card rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-all duration-300 hover:border-orange-500 hover:bg-slate-50 hover:shadow-md lg:col-span-2">
                            <div class="flex flex-col items-center text-center lg:flex-row lg:items-start lg:text-left lg:gap-6">
                                <div
                                    class="mb-3 rounded-lg bg-orange-100 p-3 transition-transform duration-300 group-hover/card:scale-110 shrink-0">
                                    <svg class="h-6 w-6 text-orange-600" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-sm font-semibold text-slate-900 lg:text-base">AI Personal Trainer</h3>
                                    <p class="mt-2 text-xs text-slate-600 lg:text-sm">
                                        Want to build strength or improve cardio? Your AI trainer creates personalized workout plans tailored to your fitness level and goals.
                                    </p>
                                    <a href="{{ route('ai-personal-trainer') }}"
                                        class="mt-3 inline-flex items-center text-xs font-medium text-orange-600 hover:text-orange-700 lg:text-sm">
                                        Meet Your Trainer
                                        <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            @if(isset($featuredFoods) && $featuredFoods->count() > 0)
            <section class="w-full max-w-6xl">
                <div class="space-y-4 lg:space-y-6">
                    <div class="text-center">
                        <h2 class="text-2xl font-bold text-slate-900 lg:text-3xl">Trending Glycemic Data</h2>
                        <p class="mt-2 text-sm text-slate-600 lg:text-base">See the spike risk and USDA profiles for the most frequently analyzed foods this week</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
                        @foreach($featuredFoods as $food)
                            @include('food._card', ['food' => $food])
                        @endforeach
                    </div>

                    <div class="text-center">
                        <a href="{{ route('food.index') }}" class="inline-flex items-center text-sm font-medium text-primary hover:text-primary/80 transition-colors">
                            Search the Full 300+ USDA Database
                            <svg class="ml-1 size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                </div>
            </section>
            @endif

            <section class="w-full max-w-6xl">
                <div class="space-y-4 lg:space-y-6">
                    <div class="text-center">
                        <h2 class="text-2xl font-bold text-slate-900 lg:text-3xl">How It Works</h2>
                        <p class="mt-2 text-sm text-slate-600 lg:text-base">From your first meal photo to a daily tracking habit</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3 lg:gap-6">
                        {{-- Step 1 --}}
                        <div class="relative rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div
                                class="absolute -top-3 left-5 flex h-7 w-7 items-center justify-center rounded-full bg-[#FF6B4A] text-sm font-bold text-white">
                                1</div>
                            <div class="pt-2">
                                <h3 class="text-base font-semibold text-slate-900 lg:text-lg">Scan</h3>
                                <p class="mt-2 text-xs leading-relaxed text-slate-600 lg:text-sm">
                                    Take or upload a meal photo in Snap to Track. Try one free scan without signing up.
                                </p>
                            </div>
                        </div>

                        {{-- Step 2 --}}
                        <div class="relative rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div
                                class="absolute -top-3 left-5 flex h-7 w-7 items-center justify-center rounded-full bg-[#FF6B4A] text-sm font-bold text-white">
                                2</div>
                            <div class="pt-2">
                                <h3 class="text-base font-semibold text-slate-900 lg:text-lg">Review and save</h3>
                                <p class="mt-2 text-xs leading-relaxed text-slate-600 lg:text-sm">
                                    Check the estimated ingredients, portions, and macros. Make corrections and save the meal with your account.
                                </p>
                            </div>
                        </div>

                        {{-- Step 3 --}}
                        <div class="relative rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                            <div
                                class="absolute -top-3 left-5 flex h-7 w-7 items-center justify-center rounded-full bg-[#FF6B4A] text-sm font-bold text-white">
                                3</div>
                            <div class="pt-2">
                                <h3 class="text-base font-semibold text-slate-900 lg:text-lg">Track your day</h3>
                                <p class="mt-2 text-xs leading-relaxed text-slate-600 lg:text-sm">
                                    See your meals and nutrition totals in Logbook. Add water and daily check-ins, and revisit earlier days whenever you need.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="w-full max-w-6xl">
                <x-cta-block
                    title="Meet Altani — Your Health Helper"
                    description="Turn your food and health questions into practical next steps. Ask Altani about meal ideas, your logged history, and everyday habits that fit your goals."
                    buttonText="Start Chatting"
                    buttonUrl="{{ route('meet-altani') }}"
                />
            </section>

            <section class="w-full max-w-6xl">
                <div
                    class="relative overflow-hidden rounded-3xl bg-slate-900 px-6 py-12 shadow-2xl sm:px-12 sm:py-16 lg:px-16">
                    {{-- Background Effects --}}
                    <div
                        class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,var(--tw-gradient-stops))] from-[#FF6B4A]/20 via-slate-900 to-slate-900">
                    </div>
                    <div class="absolute -left-12 -top-12 h-64 w-64 rounded-full bg-[#FF6B4A]/10 blur-3xl"></div>
                    <div class="absolute -bottom-12 -right-12 h-64 w-64 rounded-full bg-[#FF6B4A]/5 blur-3xl"></div>

                    <div class="relative flex flex-col items-center text-center">
                        {{-- Badge --}}
                        <div
                            class="mb-8 inline-flex items-center gap-2 rounded-full bg-[#FF6B4A]/10 px-3 py-1 text-sm font-medium text-[#FF8F6B] ring-1 ring-inset ring-[#FF6B4A]/20">
                            <span class="relative flex h-2 w-2" aria-hidden="true">
                                <span
                                    class="absolute inline-flex h-full w-full animate-ping rounded-full bg-[#FF6B4A] opacity-75"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-[#FF6B4A]"></span>
                            </span>
                            One more thing...
                        </div>

                        {{-- Content --}}
                        <h2 class="mb-6 text-3xl font-bold tracking-tight text-white sm:text-4xl">
                            Acara Plate is <span
                                class="text-transparent bg-clip-text bg-linear-to-r from-[#FF6B4A] to-[#FF8F6B]">Open
                                Source</span>
                        </h2>

                        <p class="mb-10 max-w-2xl text-lg leading-relaxed text-slate-400">
                            Acara Plate is open source and 100% free to self-host. Whether you want to customize the
                            platform for your specific needs or simply prefer running your own instance, the choice is
                            yours. Self-host Plate for full control, or use Acara Cloud when you want managed
                            long-term memory included. Have a feature in mind? We welcome pull requests to improve
                            the product for everyone.
                        </p>

                        {{-- CTA Button --}}
                        <a href="https://github.com/acara-app/plate"
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label="View on GitHub (opens in a new tab)"
                            class="group inline-flex items-center justify-center gap-2 rounded-xl bg-white px-8 py-4 text-base font-bold text-slate-900 shadow-lg shadow-[#FF6B4A]/10 transition-all hover:scale-105 hover:bg-orange-50 hover:shadow-[#FF6B4A]/20 focus:outline-none focus:ring-2 focus:ring-[#FF6B4A] focus:ring-offset-2 focus:ring-offset-slate-900">
                            <svg class="h-5 w-5 transition-transform duration-300 group-hover:scale-110"
                                fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill-rule="evenodd"
                                    d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"
                                    clip-rule="evenodd" />
                            </svg>
                            View on GitHub
                        </a>
                    </div>
                </div>
            </section>

            <section class="w-full max-w-6xl" aria-labelledby="faq-heading">
                <div class="space-y-6">
                    <div class="text-center">
                        <h2 id="faq-heading" class="text-2xl font-bold text-slate-900 lg:text-3xl">Common Questions</h2>
                        <p class="mt-2 text-sm text-slate-600 lg:text-base">From your first scan to your daily Logbook</p>
                    </div>
                    <div class="space-y-3">
                        @foreach ($frequentlyAskedQuestions as $faq)
                            <details class="group rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition-colors hover:border-orange-300">
                                <summary class="flex min-h-11 cursor-pointer items-center justify-between gap-3 text-sm font-semibold text-slate-900 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#C94F35] lg:text-base">
                                    {{ $faq['question'] }}
                                    <svg aria-hidden="true" class="size-5 shrink-0 text-slate-400 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </summary>
                                <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ $faq['answer'] }}</p>
                            </details>
                        @endforeach
                    </div>
                    <p class="text-center text-sm leading-6 text-slate-500">
                        <a href="{{ route('ai-accuracy') }}" class="font-medium text-[#C94F35] underline underline-offset-4 hover:text-[#AF402A]">AI accuracy &amp; limitations</a>
                        <span aria-hidden="true" class="mx-2">&middot;</span>
                        <a href="{{ route('install-app') }}" class="font-medium text-[#C94F35] underline underline-offset-4 hover:text-[#AF402A]">Install Plate</a>
                        <span aria-hidden="true" class="mx-2">&middot;</span>
                        <a href="{{ route('health-sync') }}" class="font-medium text-[#C94F35] underline underline-offset-4 hover:text-[#AF402A]">Apple Health sync</a>
                    </p>
                </div>
            </section>

            {{-- Medical Disclaimer --}}
            <section class="w-full max-w-6xl">
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 lg:p-6">
                    <div class="flex items-start gap-3 lg:gap-4">
                        <div class="shrink-0 rounded-full bg-amber-100 p-2 text-amber-600">
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
                                The meal plans, nutritional estimates, and health tracking features are
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

</x-default-layout>
