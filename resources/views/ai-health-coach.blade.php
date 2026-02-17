@section('title', 'Open Source AI Health Coach | Acara Plate')
@section('meta_description', 'Your personal AI wellness coach for sleep, stress, hydration, and lifestyle optimization. Get guidance to improve your overall well-being.')
@section('meta_keywords', 'open source health coach, AI wellness, sleep optimization, stress management, hydration tracker, lifestyle optimization')
@section('canonical_url', url()->current())

@section('head')

<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "WebApplication",
    "name": "Acara Plate AI Health Coach",
    "description": "Open source AI-powered health coach for wellness optimization including sleep, stress management, and lifestyle guidance.",
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
            "name": "What areas can the AI Health Coach help with?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "The AI Health Coach specializes in sleep optimization, stress management, hydration guidance, and general lifestyle wellness. It provides personalized routines and evidence-based recommendations for improving your overall well-being."
            }
        },
        {
            "@@type": "Question",
            "name": "Is this tool really open source?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Yes. We believe health utilities should be transparent. You can view our entire codebase on GitHub, verify our privacy controls, and see exactly how our wellness recommendations are generated."
            }
        },
        {
            "@@type": "Question",
            "name": "How does the AI Health Coach work?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Simply tell the AI what you're struggling with—sleep issues, high stress, low energy—and it analyzes your situation to provide specific recommendations. No forms to fill out, just conversational input."
            }
        },
        {
            "@@type": "Question",
            "name": "Is my health data secure?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "We take a privacy-first approach. Your data is never sold to third parties. Since our code is open source, you can verify exactly how your information is handled."
            }
        },
        {
            "@@type": "Question",
            "name": "Do I need to track everything manually?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "No. Unlike other wellness apps, you don't need to log every meal or hour of sleep. Just describe how you feel and what challenges you're facing."
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
            "name": "AI Health Coach"
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
                            You Sleep 8 Hours and Still Feel Like a Zombie. What's Actually Going On?
                        </h1>
                        
                        <p class="mt-6 text-lg text-slate-600 leading-relaxed">
                            It's not about sleeping more—it's about understanding the hidden factors wrecking your rest: afternoon caffeine, blue light at night, room temperature, stress hormones. Your body keeps score even when you're not paying attention.
                        </p>

                        <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                            <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-6 py-3 text-base font-semibold text-white shadow-sm hover:bg-slate-800 transition-all">
                                Get Started Free
                                <span class="ml-2 text-slate-400">→</span>
                            </a>
                            <a href="https://github.com/acara-app/plate" target="_blank" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-6 py-3 text-base font-medium text-slate-700 hover:bg-slate-50 transition-all">
                                View Source Code
                            </a>
                        </div>
                    </div>

                    <div class="mt-12 lg:mt-0 lg:col-span-6">
                        <div class="relative rounded-xl bg-slate-900 shadow-2xl ring-1 ring-white/10" aria-hidden="true">
                            <div class="flex items-center gap-2 border-b border-white/10 px-4 py-3 bg-white/5 rounded-t-xl">
                                <div class="flex gap-1.5">
                                    <div class="h-3 w-3 rounded-full bg-red-500"></div>
                                    <div class="h-3 w-3 rounded-full bg-yellow-500"></div>
                                    <div class="h-3 w-3 rounded-full bg-green-500"></div>
                                </div>
                                <div class="text-xs font-mono text-slate-400 ml-2">acara-ai — wellness</div>
                            </div>

                            <div class="p-6 font-mono text-sm space-y-4">
                                <div class="text-slate-400">
                                    <span class="text-rose-500">user@acara:~$</span> ask "I having trouble sleeping and feel stressed all the time"
                                </div>
                                
                                <div class="space-y-2 border-l-2 border-slate-700 pl-4 py-2">
                                    <div class="text-emerald-400">✓ Wellness areas identified</div>
                                    <div class="text-slate-300">
                                        • Sleep quality issues<br>
                                        • Chronic stress patterns
                                    </div>
                                </div>

                                <div class="bg-white/10 rounded p-3 text-slate-200">
                                    <span class="font-bold text-cyan-400">SUGGESTED ROUTINE</span><br>
                                    <br>
                                    <span class="text-slate-400">Morning:</span> 10 min sunlight, avoid phone first hour<br>
                                    <span class="text-slate-400">Evening:</span> Dim lights 8pm, no screens after 9pm<br>
                                    <span class="text-slate-400">Bedtime:</span> 65°F room, 4-7-8 breathing<br>
                                    <br>
                                    <span class="text-cyan-400">→</span> Try the sleep optimization routine for 2 weeks
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
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900">Why Sleep and Stress Deserve Real Attention</h2>
                    <p class="mt-4 text-lg text-slate-600">
                        Skip the generic "drink more water" advice. These three areas—sleep, stress, and hydration—interact in ways that affect everything from your immune system to your afternoon energy crash.
                    </p>
                </div>

                <div class="grid gap-8 md:grid-cols-3">
                    {{-- Card 1 --}}
                    <div class="bg-white rounded-xl p-8 shadow-sm border border-slate-100">
                        <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center text-indigo-600 mb-6">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Sleep That Actually Works</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">
                            It's not just about hours in bed. Your sleep environment, screen habits, and meal timing all play a role. Get recommendations that fit your actual schedule—not some generic 10pm bedtime rule.
                        </p>
                    </div>

                    <div class="bg-white rounded-xl p-8 shadow-sm border border-slate-100">
                        <div class="w-12 h-12 bg-rose-100 rounded-lg flex items-center justify-center text-rose-600 mb-6">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Stress Without the Overwhelm</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">
                            You can't eliminate stress entirely—that's not the goal. But you can build better recovery patterns. Practical breathing techniques, micro-habits, and routine tweaks that actually move the needle.
                        </p>
                    </div>

                    <div class="bg-white rounded-xl p-8 shadow-sm border border-slate-100">
                        <div class="w-12 h-12 bg-cyan-100 rounded-lg flex items-center justify-center text-cyan-600 mb-6">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Hydration That Makes Sense</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">
                            The "8 glasses a day" rule is oversimplified. Your needs depend on activity, climate, and what you're eating. Get practical reminders and learn to read your body's signals instead of chasing arbitrary numbers.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-16 sm:py-24 bg-white">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900">How It Works</h2>
                    <p class="mt-4 text-lg text-slate-600">
                        No trackers, no spreadsheets, no endless logging. Just describe how you're feeling.
                    </p>
                </div>

                <div class="grid gap-8 md:grid-cols-3">
                    <div class="relative">
                        <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-slate-100 text-slate-900 font-bold text-xl mb-6">
                            01
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Tell It What's Bothering You</h3>
                        <p class="text-slate-600 leading-relaxed">
                            Can't sleep? Stressed about work? Forgot to drink water all day? Just say it. No structured forms, no 47 questions to answer. It understands context.
                        </p>
                    </div>

                    <div class="relative">
                        <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-slate-100 text-slate-900 font-bold text-xl mb-6 border-2 border-cyan-500">
                            02
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Get a Routine That Fits</h3>
                        <p class="text-slate-600 leading-relaxed">
                            Instead of generic advice, you get a routine built around your life. Morning sunlight if you're not getting it. Evening wind-down if you're wired at night. Things that actually work for your schedule.
                        </p>
                    </div>

                    <div class="relative">
                        <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-slate-100 text-slate-900 font-bold text-xl mb-6">
                            03
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Track Progress Without the Friction</h3>
                        <p class="text-slate-600 leading-relaxed">
                            Check in naturally over time. The system remembers your context and adjusts recommendations as your habits shift. No manual tracking required unless you want it.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-slate-50 py-16 sm:py-24">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-slate-900">What People Ask</h2>
                </div>

                <div class="space-y-4">
                    <details class="group rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <summary class="flex cursor-pointer items-center justify-between font-semibold text-slate-900">
                            What areas can the AI Health Coach help with?
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="mt-4 text-slate-600">
                            Sleep optimization, stress management, hydration, and general lifestyle wellness. It's not a replacement for therapy or medical care, but it gives you practical, evidence-based routines that fit your actual life.
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
                            Yep. The code's on GitHub. You can verify how recommendations are generated, check the privacy controls, and even fork it if you want to build your own version. We welcome contributions from developers and health enthusiasts alike.
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
                            Privacy-first, always. We don't sell your data to advertisers or insurance companies. Since the code is open source, these aren't just marketing claims—you can verify them yourself.
                        </p>
                    </details>

                    <details class="group rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <summary class="flex cursor-pointer items-center justify-between font-semibold text-slate-900">
                            Do I need to track everything manually?
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="mt-4 text-slate-600">
                            No. That's the whole point. Unlike other wellness apps that turn logging into a second job, this one just asks how you're doing. Describe your day, your challenges, your energy levels—and it figures out the patterns.
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
                    We're building an open science health stack because we got tired of wellness data being locked in proprietary apps. Your health data should be yours—verifiable, portable, and transparent.
                </p>
                <div class="mt-8 flex justify-center">
                    <a href="{{ route('register') }}" class="text-base font-semibold text-rose-600 hover:text-rose-500 border-b-2 border-rose-100 hover:border-rose-500 transition-colors pb-1">
                        See what it can do →
                    </a>
                </div>
            </div>
        </section>

    </div>
    <x-footer />
</x-default-layout>
