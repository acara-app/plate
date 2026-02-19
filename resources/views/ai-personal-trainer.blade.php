@section('title', 'Open Source AI Personal Trainer | Acara Plate')
@section('meta_description', 'Your AI fitness coach for strength, cardio, and flexibility training. Get workout plans and exercise guidance.')
@section('meta_keywords', 'open source personal trainer, AI fitness coach, workout planner, exercise guidance, strength training, cardio training')

@section('head')

<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "WebApplication",
    "name": "Acara Plate AI Personal Trainer",
    "description": "Open source AI-powered personal trainer for fitness, strength training, and exercise guidance.",
    "applicationCategory": "SportsApplication",
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
            "name": "What can the AI Personal Trainer help with?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "The AI Personal Trainer specializes in strength training, cardio, HIIT, flexibility, and general fitness programming. It provides workout routines and training plans tailored to your fitness level and goals."
            }
        },
        {
            "@@type": "Question",
            "name": "Is this tool really open source?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Yes. We believe fitness tools should be transparent. You can view our entire codebase on GitHub, verify our privacy controls, and see exactly how our workout recommendations are generated."
            }
        },
        {
            "@@type": "Question",
            "name": "How does the AI Personal Trainer create workouts?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Simply tell the AI what you want to achieve—build muscle, lose weight, improve endurance—and it builds a custom program based on your fitness level and available equipment."
            }
        },
        {
            "@@type": "Question",
            "name": "Do I need gym equipment?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "No! The AI can create effective workouts using just your bodyweight. However, if you have access to dumbbells, kettlebells, or gym equipment, it can incorporate those into your program as well."
            }
        },
        {
            "@@type": "Question",
            "name": "Can beginners use this tool?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Absolutely. The AI Personal Trainer works for all fitness levels. Whether you're just starting out or you've been training for years, you get workout plans that match your current abilities."
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
            "name": "AI Personal Trainer"
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
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-full bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition-all">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">Log in</a>
                    <a href="{{ route('register') }}" class="rounded-full bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition-all">
                        Get Started
                    </a>
                @endauth
            </div>
        </header>
        
        <!-- Hero Section -->
        <section class="relative pt-16 pb-20 sm:pt-24 sm:pb-32 overflow-hidden">
            <!-- Decorative SVG elements — fitness-themed, scattered across hero -->
            <!-- Speed lines top-right — convey motion and energy -->
            <svg class="absolute top-10 right-16 w-20 sm:w-28 opacity-[0.12] select-none pointer-events-none rotate-[-15deg]" viewBox="0 0 100 60" fill="none" aria-hidden="true">
                <path d="M10 15 Q50 10 90 15" stroke="#34D399" stroke-width="2.5" stroke-linecap="round"/>
                <path d="M15 30 Q55 25 90 30" stroke="#34D399" stroke-width="2" stroke-linecap="round" opacity="0.7"/>
                <path d="M20 45 Q60 40 90 45" stroke="#A7F3D0" stroke-width="1.5" stroke-linecap="round" opacity="0.5"/>
            </svg>
            <!-- Circle cluster top-left — warm energy dots -->
            <svg class="absolute top-14 left-10 w-12 sm:w-16 opacity-[0.10] select-none pointer-events-none" viewBox="0 0 48 48" fill="none" aria-hidden="true">
                <circle cx="16" cy="16" r="8" fill="#FF6B4A"/>
                <circle cx="34" cy="12" r="5" fill="#FFCBB8"/>
                <circle cx="24" cy="34" r="6" fill="#FF6B4A" opacity="0.5"/>
            </svg>
            <!-- Organic blob bottom-left — grounding shape -->
            <svg class="absolute bottom-12 left-6 w-20 sm:w-28 opacity-[0.08] select-none pointer-events-none -rotate-12" viewBox="0 0 120 80" fill="none" aria-hidden="true">
                <path d="M20 40c0-20 15-36 40-36s40 14 44 36c4 22-10 36-44 36S20 60 20 40z" fill="#34D399"/>
            </svg>
            <!-- Lightning bolt bottom-right — power and intensity -->
            <svg class="absolute bottom-20 right-10 w-10 sm:w-14 opacity-[0.10] select-none pointer-events-none rotate-12" viewBox="0 0 40 64" fill="none" aria-hidden="true">
                <path d="M24 4L8 28h10L14 60l18-32H22L28 4h-4z" fill="#FFCBB8"/>
            </svg>
            <!-- Teal dot accent mid-left — analogous depth -->
            <svg class="absolute top-1/3 left-4 w-6 sm:w-8 opacity-[0.15] select-none pointer-events-none" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="10" fill="#14B8A6"/>
            </svg>
            <!-- Dumbbell molecule dots top-center — fitness motif -->
            <svg class="absolute top-6 left-1/2 -translate-x-1/2 w-28 sm:w-36 opacity-[0.06] select-none pointer-events-none" viewBox="0 0 140 40" fill="none" aria-hidden="true">
                <circle cx="20" cy="20" r="8" fill="#34D399"/>
                <circle cx="70" cy="20" r="5" fill="#34D399" opacity="0.6"/>
                <circle cx="120" cy="20" r="8" fill="#34D399"/>
                <line x1="28" y1="20" x2="62" y2="20" stroke="#34D399" stroke-width="2" opacity="0.4"/>
                <line x1="78" y1="20" x2="112" y2="20" stroke="#34D399" stroke-width="2" opacity="0.4"/>
            </svg>

            <!-- Runner SVG — repositioned bottom-right, larger, heroic presence -->
            <img src="https://pub-plate-assets.acara.app/images/runner-woman-4.svg" alt="Graphic of a woman running for fitness" aria-hidden="true" class="absolute bottom-0 right-4 w-24 sm:w-32 md:w-44 translate-y-6 opacity-70 select-none pointer-events-none">

            <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
                <div class="relative z-10 mx-auto max-w-3xl">
                    <div class="mb-6 flex justify-center">
                        <span class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-sm font-medium text-emerald-800">
                            <svg class="h-4 w-4 text-emerald-600" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
                            </svg>
                            Open Source Project
                        </span>
                    </div>
                    <h1 class="font-display text-5xl font-bold tracking-tight text-slate-900 sm:text-6xl mb-6">
                        I Hated the Gym. So I Built<br>
                        <span class="text-emerald-700">Workouts That Don't Suck</span>.
                    </h1>
                    <p class="mt-6 text-lg leading-8 text-slate-600 max-w-2xl mx-auto">
                        You don't need a gym membership or fancy equipment to get stronger. You need a plan that respects your time and meets you where you are.
                    </p>
                    <div class="mt-10 flex items-center justify-center gap-x-6">
                        <a href="{{ route('register') }}" class="rounded-full bg-[#FF6B4A] px-8 py-3.5 text-base font-semibold text-white shadow-sm hover:bg-[#E85A3A] transition-all focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#FF6B4A]">
                            Start Your Workout
                        </a>
                        <a href="https://github.com/acara-app/plate" target="_blank" class="text-sm font-semibold leading-6 text-slate-900">
                            View Source Code <span aria-hidden="true">→</span>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-slate-50 py-16 sm:py-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900">Why Training with AI Actually Makes Sense</h2>
                    <p class="mt-4 text-lg text-slate-600">
                        Forget the expensive personal trainer or the cookie-cutter app that doesn't know your shoulder injury from last year. Here's what actually moves the needle.
                    </p>
                </div>

                <div class="grid gap-8 md:grid-cols-3">
                    {{-- Card 1 --}}
                    <div class="bg-white rounded-xl p-8 shadow-sm border border-slate-100">
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center text-orange-600 mb-6">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Strength Without the Gym</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">
                            Push-ups, squats, lunges, and planks are underrated. The system builds progressive programs using bodyweight or whatever equipment you have available. No expensive memberships required.
                        </p>
                    </div>

                    <div class="bg-white rounded-xl p-8 shadow-sm border border-slate-100">
                        <div class="w-12 h-12 bg-rose-100 rounded-lg flex items-center justify-center text-rose-600 mb-6">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Cardio That Doesn't Bore You</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">
                            Running isn't the only option. HIIT, cycling, swimming, dancing—whatever keeps you moving. The system builds interval structures that maximize results in less time.
                        </p>
                    </div>

                    <div class="bg-white rounded-xl p-8 shadow-sm border border-slate-100">
                        <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center text-emerald-600 mb-6">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Move Better, Get Hurt Less</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">
                            Flexibility and mobility aren't just for yogis. Getting your shoulders and hips moving better prevents the injuries that derail progress. Simple drills that make a real difference.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-16 sm:py-24 bg-white">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900">Here's How It Works</h2>
                    <p class="mt-4 text-lg text-slate-600">
                        No complicated onboarding. Just tell it what you want and what you've got.
                    </p>
                </div>

                <div class="grid gap-8 md:grid-cols-3">
                    <div class="relative">
                        <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-slate-100 text-slate-900 font-bold text-xl mb-6">
                            01
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Tell It What You Want</h3>
                        <p class="text-slate-600 leading-relaxed">
                            Build muscle? Lose weight? Just not die when you climb stairs? The system understands goals in plain English. Mention any injuries or limitations too—it adjusts accordingly.
                        </p>
                    </div>

                    <div class="relative">
                        <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-slate-100 text-slate-900 font-bold text-xl mb-6 border-2 border-orange-500">
                            02
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Get Your Program</h3>
                        <p class="text-slate-600 leading-relaxed">
                            Here's where it gets interesting. A complete weekly workout plan lands in your chat—sets, reps, rest periods, the whole thing. Built for your fitness level and available equipment.
                        </p>
                    </div>

                    <div class="relative">
                        <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-slate-100 text-slate-900 font-bold text-xl mb-6">
                            03
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Train and Adjust</h3>
                        <p class="text-slate-600 leading-relaxed">
                            Done with a workout? Tell it. Feeling too easy or too hard? Give feedback. The system evolves your program based on how you're progressing and recovering.
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
                            What can the AI Personal Trainer help with?
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="mt-4 text-slate-600">
                            Strength training, cardio, HIIT, flexibility, mobility—basically anything fitness-related. It creates full programs, suggests exercises, gives form guidance, and progresses you over time. From absolute beginner to experienced lifter looking for variety.
                        </p>
                    </details>

                    <details class="group rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <summary class="flex cursor-pointer items-center justify-between font-semibold text-slate-900">
                            Do I need gym equipment?
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="mt-4 text-slate-600">
                            Nope. Some of the best workouts need zero equipment. Push-ups, squats, lunges, planks—these build serious strength. If you've got dumbbells or a gym membership, it incorporates those too. Works with whatever you've got.
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
                            Yes. The whole thing's on GitHub. You can see exactly how workouts are generated, verify the privacy controls, and even contribute if you're a developer. We welcome audits and contributions from the community.
                        </p>
                    </details>

                    <details class="group rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <summary class="flex cursor-pointer items-center justify-between font-semibold text-slate-900">
                            I'm a complete beginner. Is this for me?
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="mt-4 text-slate-600">
                            Absolutely. It meets you where you are. Never worked out before? You'll get a program that builds the basics safely. Been training for years? It'll challenge you with new variations. Everyone starts somewhere.
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
                    This is one tool in an open science health stack. We're building it because we got tired of fitness data being locked away in proprietary apps. Your progress belongs to you.
                </p>
                <div class="mt-8 flex justify-center">
                    <a href="{{ route('register') }}" class="text-base font-semibold text-rose-600 hover:text-rose-500 border-b-2 border-rose-100 hover:border-rose-500 transition-colors pb-1">
                        Get your first workout →
                    </a>
                </div>
            </div>
        </section>

    </div>
    <x-cta-block
        title="Pair Your Fitness with Nutrition AI"
        description="Let Altani help you optimize your nutrition to fuel your workouts and recovery."
        button-text="Meet Altani"
    />
    <x-footer />
</x-default-layout>
