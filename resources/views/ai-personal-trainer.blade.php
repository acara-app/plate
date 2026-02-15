@section('title', 'Open Source AI Personal Trainer | Acara Plate')
@section('meta_description', 'Your AI fitness coach for strength, cardio, and flexibility training. Get workout plans and exercise guidance.')
@section('meta_keywords', 'open source personal trainer, AI fitness coach, workout planner, exercise guidance, strength training, cardio training')
@section('canonical_url', url()->current())
@section('og_image', asset('screenshots/og-ai-personal-trainer.webp'))
@section('og_image_width', '1920')
@section('og_image_height', '1096')
@section('og_image_alt', 'AI Personal Trainer displaying workout plans with strength exercises, cardio routines, and fitness goals')

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
                            Your Personal AI Trainer
                        </h1>
                        
                        <p class="mt-6 text-lg text-slate-600 leading-relaxed">
                            Get workout plans built around your fitness level and goals. Tell Acara what you want to achieve—build muscle, improve cardio, or just stay active—and get a custom training program.
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
                        <img 
                            src="{{ asset('screenshots/og-ai-personal-trainer.webp') }}" 
                            alt="AI Personal Trainer terminal interface showing workout routine with strength exercises"
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
                                <div class="text-xs font-mono text-slate-400 ml-2">acara-ai — fitness</div>
                            </div>

                            <div class="p-6 font-mono text-sm space-y-4">
                                <div class="text-slate-400">
                                    <span class="text-rose-500">user@acara:~$</span> ask "I want to build muscle and get stronger"
                                </div>
                                
                                <div class="space-y-2 border-l-2 border-slate-700 pl-4 py-2">
                                    <div class="text-emerald-400">✓ Fitness goal identified</div>
                                    <div class="text-slate-300">
                                        • Goal: Strength & Muscle Building<br>
                                        • Level: Intermediate<br>
                                        • Focus: Upper & Lower Body
                                    </div>
                                </div>

                                <div class="bg-white/10 rounded p-3 text-slate-200">
                                    <span class="font-bold text-orange-400">WEEKLY WORKOUT PLAN</span><br>
                                    <br>
                                    <span class="text-slate-400">Day 1 - Upper Body:</span> Push-ups, Rows, Press<br>
                                    <span class="text-slate-400">Day 2 - Lower Body:</span> Squats, Lunges, Deadlifts<br>
                                    <span class="text-slate-400">Day 3 - Rest</span><br>
                                    <span class="text-slate-400">Day 4 - HIIT:</span> 20 min intervals<br>
                                    <span class="text-slate-400">Day 5 - Upper:</span> Dips, Curls, Extensions<br>
                                    <br>
                                    <span class="text-orange-400">→</span> 3 sets, 10-12 reps, 60sec rest
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
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900">Why train with AI?</h2>
                    <p class="mt-4 text-lg text-slate-600">
                        Get professional-level training guidance without the gym membership. Personalized workouts that adapt to your fitness level and goals.
                    </p>
                </div>

                <div class="grid gap-8 md:grid-cols-3">
                    {{-- Card 1 --}}
                    <div class="bg-white rounded-xl p-8 shadow-sm border border-slate-100">
                        <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center text-orange-600 mb-6">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Strength Training</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">
                            Build muscle and increase strength with programs that match your level. From bodyweight exercises to weighted movements, get the right workout for you.
                        </p>
                    </div>

                    <div class="bg-white rounded-xl p-8 shadow-sm border border-slate-100">
                        <div class="w-12 h-12 bg-rose-100 rounded-lg flex items-center justify-center text-rose-600 mb-6">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Cardio & HIIT</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">
                            Improve your cardiovascular fitness with running plans, HIIT workouts, and interval training. Burn calories and build endurance efficiently.
                        </p>
                    </div>

                    <div class="bg-white rounded-xl p-8 shadow-sm border border-slate-100">
                        <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center text-emerald-600 mb-6">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Flexibility & Mobility</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">
                            Improve your range of motion and prevent injury with stretching routines, mobility drills, and yoga-inspired movements.
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
                        Three steps to get fit
                    </p>
                </div>

                <div class="grid gap-8 md:grid-cols-3">
                    <div class="relative">
                        <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-slate-100 text-slate-900 font-bold text-xl mb-6">
                            01
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Tell Us Your Goal</h3>
                        <p class="text-slate-600 leading-relaxed">
                            Tell the AI what you want to achieve. Whether it's building muscle, losing weight, or improving endurance—it builds a plan around your goals.
                        </p>
                    </div>

                    <div class="relative">
                        <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-slate-100 text-slate-900 font-bold text-xl mb-6 border-2 border-orange-500">
                            02
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Get Your Program</h3>
                        <p class="text-slate-600 leading-relaxed">
                            Receive a workout plan built for your fitness level, available equipment, and schedule. Complete with sets, reps, and rest periods.
                        </p>
                    </div>

                    <div class="relative">
                        <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-slate-100 text-slate-900 font-bold text-xl mb-6">
                            03
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Train & Progress</h3>
                        <p class="text-slate-600 leading-relaxed">
                            Follow your program and track progress. The AI adjusts recommendations based on your feedback and helps you progress over time.
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
                            What can the AI Personal Trainer help with?
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="mt-4 text-slate-600">
                            The AI Personal Trainer specializes in strength training, cardiovascular fitness, HIIT, flexibility, and general fitness programming. It can create workout plans, suggest exercises, provide form guidance, and help you progress toward your fitness goals.
                        </p>
                    </details>

                    <details class="group rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <summary class="flex cursor-pointer items-center justify-between font-semibold text-slate-900">
                            Do I need gym equipment?
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="mt-4 text-slate-600">
                            No! The AI can create effective workouts using just your bodyweight. However, if you have access to dumbbells, kettlebells, or gym equipment, it can incorporate those into your program as well.
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
                            Yes. We believe fitness tools should be transparent. You can view our entire codebase on GitHub, verify our privacy controls, and see exactly how our workout recommendations are generated. We welcome audits and contributions from the developer and fitness communities.
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
                            Anyone looking to improve their fitness! Whether you're a complete beginner, an intermediate athlete, or an advanced trainer looking for new ideas, the AI Personal Trainer provides guidance for all levels.
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
                    Acara Plate is built by developers and fitness enthusiasts who believe health data should be accessible, not locked in a black box.
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
