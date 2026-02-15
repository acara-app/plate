@section('title', 'Open Source AI Health Coach | Acara Plate')
@section('meta_description', 'Your personal AI wellness coach for sleep, stress, hydration, and lifestyle optimization. Get personalized guidance to improve your overall well-being.')
@section('meta_keywords', 'open source health coach, AI wellness, sleep optimization, stress management, hydration tracker, lifestyle optimization')

@section('head')
{{-- Open Graph / Facebook --}}
<meta property="og:type" content="website">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:title" content="Open Source AI Health Coach | Acara Plate">
<meta property="og:description" content="Your personal AI wellness coach for sleep, stress, hydration, and lifestyle optimization.">
<meta property="og:image" content="{{ asset('screenshots/og-ai-health-coach.webp') }}">
<meta property="og:image:width" content="1920">
<meta property="og:image:height" content="1096">
<meta property="og:image:alt" content="AI Health Coach interface showing sleep optimization recommendations and wellness routine suggestions">

{{-- Twitter Card --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:url" content="{{ url()->current() }}">
<meta name="twitter:title" content="Open Source AI Health Coach | Acara Plate">
<meta name="twitter:description" content="Your personal AI wellness coach for sleep, stress, hydration, and lifestyle optimization.">
<meta name="twitter:image" content="{{ asset('screenshots/og-ai-health-coach.webp') }}">
<meta name="twitter:image:alt" content="AI Health Coach interface showing sleep optimization recommendations and wellness routine suggestions">

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
        }
    ]
}
</script>
@endsection

<x-default-layout>
    <div class="bg-white">
        
        <section class="relative overflow-hidden pt-12 pb-16 sm:pt-20 sm:pb-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="lg:grid lg:grid-cols-12 lg:gap-16 items-center">
                    
                    <div class="lg:col-span-6 text-center lg:text-left">
                        <div class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-800 mb-6">
                            <svg class="h-4 w-4 text-slate-500" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
                            </svg>
                            100% Open Source Project
                        </div>
                        <h1 class="text-teal-600 text-4xl mb-2">Acara Plate</h1> 
                        <p class="text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl">
                        Your Personal Wellness Coach
                        </p>
                        
                        <p class="mt-6 text-lg text-slate-600 leading-relaxed">
                            Get personalized guidance on sleep, stress, hydration, and lifestyle. Tell Acara what you're struggling with—sleep issues, high stress, energy dips—and get actionable recommendations.
                        </p>

                        <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                            <a href="{{ route('register') }}" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-6 py-3 text-base font-semibold text-white shadow-sm hover:bg-slate-800 transition-all">
                                Start Your Wellness Journey
                                <span class="ml-2 text-slate-400">→</span>
                            </a>
                            <a href="https://github.com/acara-app/plate" target="_blank" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-6 py-3 text-base font-medium text-slate-700 hover:bg-slate-50 transition-all">
                                View Source Code
                            </a>
                        </div>
                    </div>

                    <div class="mt-12 lg:mt-0 lg:col-span-6">
                        <img 
                            src="{{ asset('screenshots/og-ai-health-coach.webp') }}" 
                            alt="AI Health Coach terminal interface showing sleep optimization recommendations"
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
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900">Why focus on wellness?</h2>
                    <p class="mt-4 text-lg text-slate-600">
                        Sleep, stress, and hydration are the foundations of good health. Small improvements in these areas can have dramatic effects on your energy, mood, and long-term health.
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
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Sleep Optimization</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">
                            Quality sleep affects everything from mood to metabolism. Get personalized sleep hygiene recommendations and routines tailored to your lifestyle.
                        </p>
                    </div>

                    <div class="bg-white rounded-xl p-8 shadow-sm border border-slate-100">
                        <div class="w-12 h-12 bg-rose-100 rounded-lg flex items-center justify-center text-rose-600 mb-6">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Stress Management</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">
                            Chronic stress impacts every system in your body. Learn practical techniques for managing stress, from breathing exercises to daily mindfulness.
                        </p>
                    </div>

                    <div class="bg-white rounded-xl p-8 shadow-sm border border-slate-100">
                        <div class="w-12 h-12 bg-cyan-100 rounded-lg flex items-center justify-center text-cyan-600 mb-6">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Hydration & Lifestyle</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">
                            Proper hydration and daily habits are essential for optimal function. Get practical tips for staying hydrated and building healthy routines.
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
                        Three steps to better wellness
                    </p>
                </div>

                <div class="grid gap-8 md:grid-cols-3">
                    <div class="relative">
                        <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-slate-100 text-slate-900 font-bold text-xl mb-6">
                            01
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Conversational Interface</h3>
                        <p class="text-slate-600 leading-relaxed">
                            No strict forms or tracking apps. Just tell the AI what you're struggling with. It understands your context and provides relevant guidance.
                        </p>
                    </div>

                    <div class="relative">
                        <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-slate-100 text-slate-900 font-bold text-xl mb-6 border-2 border-cyan-500">
                            02
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Personalized Routines</h3>
                        <p class="text-slate-600 leading-relaxed">
                            Receive tailored wellness routines based on your goals, lifestyle, and current challenges. Everything from sleep schedules to stress techniques.
                        </p>
                    </div>

                    <div class="relative">
                        <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-slate-100 text-slate-900 font-bold text-xl mb-6">
                            03
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Track Your Progress</h3>
                        <p class="text-slate-600 leading-relaxed">
                            Get ongoing support and adjustments. The AI remembers your context and helps you build sustainable healthy habits.
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
                            What areas can the AI Health Coach help with?
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="mt-4 text-slate-600">
                            The AI Health Coach specializes in sleep optimization, stress management, hydration guidance, and general lifestyle wellness. It provides evidence-based recommendations tailored to your specific situation and goals.
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
                            Yes. We believe health utilities should be transparent. You can view our entire codebase on GitHub, verify our privacy controls, and see exactly how our wellness recommendations are generated. We welcome audits and contributions from the developer and health science communities.
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
                            We take a "Privacy-First" approach. We do not sell your data to third-party advertisers or insurance companies. Because our code is open source, these claims are verifiable by anyone. Your wellness data is used solely to provide you with accurate guidance.
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
                            Anyone looking to improve their overall wellness. Whether you're struggling with sleep, feeling stressed, wanting to stay better hydrated, or just looking for general health optimization, this tool provides personalized guidance without the complexity.
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
                    Acara Plate is built by developers and health enthusiasts who believe health data should be accessible, not locked in a black box.
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
