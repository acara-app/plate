@section('title', 'Open Source AI Health Coach | Acara Plate')
@section('meta_description', 'Your personal AI wellness coach for sleep, stress, hydration, and lifestyle optimization. Get guidance to improve your overall well-being.')
@section('meta_keywords', 'open source health coach, AI wellness, sleep optimization, stress management, hydration tracker, lifestyle optimization')

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
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "WebPage",
    "name": "AI Health Coach — Personalized Wellness for Diabetes",
    "description": "An AI-powered health coach that helps you understand sleep, stress, and lifestyle factors affecting your blood sugar and overall wellness.",
    "url": "{{ url('/ai-health-coach') }}",
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

<x-default-layout>
    <div class="relative">
        <!-- Paper grain texture -->
        <x-paper-grain class="z-0" />

        <x-tools-header />

        <!-- Hero Section — Editorial F-pattern with botanical decorations -->
        <section class="relative pt-16 pb-20 sm:pt-24 sm:pb-32 overflow-hidden">
            <!-- Botanical SVG decorations -->
            <svg class="absolute top-10 right-20 w-16 sm:w-20 opacity-[0.08] select-none pointer-events-none rotate-25" viewBox="0 0 64 64" fill="none" aria-hidden="true">
                <path d="M32 4C32 4 8 20 8 40c0 11.046 10.745 20 24 20s24-8.954 24-20C56 20 32 4 32 4z" fill="#1b4332"/>
                <path d="M32 14v40M32 28c-6 -4-14-2-18 4M32 36c6-4 14-2 18 4" stroke="#1b4332" stroke-width="1.5" stroke-linecap="round" opacity="0.6"/>
            </svg>
            <svg class="absolute top-16 left-12 w-10 sm:w-14 opacity-[0.06] select-none pointer-events-none" viewBox="0 0 48 48" fill="none" aria-hidden="true">
                <circle cx="16" cy="16" r="8" fill="#1b4332"/>
                <circle cx="34" cy="12" r="5" fill="#bc4749"/>
                <circle cx="24" cy="34" r="6" fill="#1b4332"/>
            </svg>
            <svg class="absolute bottom-12 left-8 w-20 sm:w-28 opacity-[0.05] select-none pointer-events-none -rotate-12" viewBox="0 0 120 80" fill="none" aria-hidden="true">
                <path d="M20 40c0-20 15-36 40-36s40 14 44 36c4 22-10 36-44 36S20 60 20 40z" fill="#1b4332"/>
            </svg>
            <svg class="absolute bottom-20 right-10 w-10 sm:w-14 opacity-[0.07] select-none pointer-events-none rotate-140" viewBox="0 0 64 64" fill="none" aria-hidden="true">
                <path d="M32 4C32 4 8 20 8 40c0 11.046 10.745 20 24 20s24-8.954 24-20C56 20 32 4 32 4z" fill="#bc4749"/>
            </svg>
            <svg class="absolute top-1/3 left-4 w-6 sm:w-8 opacity-[0.10] select-none pointer-events-none" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="10" fill="#1b4332"/>
            </svg>

            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="lg:grid lg:grid-cols-2 lg:gap-12 items-center">
                    <!-- LEFT COLUMN — Text content (F-pattern reading zone) -->
                    <div class="text-center lg:text-left">
                        <!-- Badge -->
                        <div class="mb-6 flex justify-center lg:justify-start">
                            <span class="inline-flex items-center gap-2 rounded-full bg-[#1b4332]/10 px-4 py-1.5 text-sm font-medium text-[#1b4332] font-mono">
                                <svg class="h-4 w-4 text-[#bc4749]" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
                                </svg>
                                Open Source Project
                            </span>
                        </div>

                        <!-- Headline -->
                        <h1 class="font-display text-4xl font-bold tracking-tight text-[#1a1a1a] sm:text-5xl lg:text-6xl mb-6 speakable-intro leading-[1.1]">
                            You Sleep 8 Hours and Still<br>
                            <span class="text-[#bc4749]">Feel Like a Zombie</span>?
                        </h1>

                        <!-- Subheadline -->
                        <p class="mt-4 text-lg leading-8 text-[#1a1a1a]/70 max-w-xl mx-auto lg:mx-0 speakable-intro font-serif">
                            It's not about sleeping more—it's about understanding the hidden factors wrecking your rest: afternoon caffeine, blue light at night, room temperature, stress hormones. Your body keeps score even when you're not paying attention.
                        </p>

                        <!-- CTAs -->
                        <div class="mt-10 flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                            <a href="{{ route('register') }}"
                               class="w-full sm:w-auto rounded-full bg-[#1b4332] px-8 py-3.5 text-center text-base font-semibold text-[#f7f3ed] shadow-lg shadow-[#1b4332]/20 hover:bg-[#143329] hover:shadow-[#1b4332]/30 hover:-translate-y-0.5 transition-all duration-200">
                                Start Your Wellness Journey
                            </a>
                            <a href="https://github.com/acara-app/plate"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="group w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-full bg-white px-6 py-3.5 text-base font-medium text-[#1a1a1a]/70 shadow-sm ring-1 ring-[#1a1a1a]/10 transition-all duration-200 hover:bg-[#f7f3ed] hover:text-[#1a1a1a] hover:ring-[#1a1a1a]/20 hover:-translate-y-0.5">
                                <svg class="h-5 w-5 transition-transform group-hover:scale-110" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
                                </svg>
                                Star on GitHub
                            </a>
                        </div>

                        <x-no-limits-bullets class="justify-center lg:justify-start" />
                    </div>

                    <!-- RIGHT COLUMN — Image with soft-fade edges -->
                    <div class="hidden lg:block relative mt-12 lg:mt-0">
                        <img src="https://pub-plate-assets.acara.app/images/woman-meditating-full.webp"
                             alt="Woman meditating peacefully, representing wellness and mindfulness"
                             class="w-full h-auto max-w-xl mx-auto"
                             style="mask-image: radial-gradient(ellipse 85% 80% at 50% 50%, black 55%, transparent 100%); -webkit-mask-image: radial-gradient(ellipse 85% 80% at 50% 50%, black 55%, transparent 100%);">
                    </div>
                </div>
            </div>
        </section>

        <!-- Why Sleep and Stress Deserve Real Attention — Editorial Cards -->
        <section class="relative py-20 sm:py-28 overflow-hidden">
            <x-paper-grain class="opacity-50" />

            <div class="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <div class="flex items-center justify-center gap-4 mb-6">
                        <span class="h-px w-12 bg-[#bc4749]"></span>
                        <span class="font-mono text-xs font-semibold uppercase tracking-[0.2em] text-[#bc4749]">
                            The Big Picture
                        </span>
                        <span class="h-px w-12 bg-[#bc4749]"></span>
                    </div>
                    <h2 class="font-display text-3xl font-bold text-[#1a1a1a] sm:text-4xl lg:text-5xl">
                        Why Sleep and Stress Deserve Real Attention
                    </h2>
                    <p class="mt-5 text-lg text-[#1a1a1a]/60 leading-relaxed font-serif">
                        Skip the generic "drink more water" advice. These three areas—sleep, stress, and hydration—interact in ways that affect everything from your immune system to your afternoon energy crash.
                    </p>
                </div>

                <div class="grid gap-8 md:grid-cols-3">
                    <!-- Card 1 -->
                    <div class="group relative overflow-hidden bg-white p-8 shadow-[0_4px_24px_-8px_rgba(26,26,26,0.1)] transition-all duration-500 hover:shadow-[0_12px_40px_-12px_rgba(26,26,26,0.18)]"
                         style="border-radius: 2px 28px 2px 28px;">
                        <div class="absolute top-0 left-6 right-6 h-px bg-gradient-to-r from-transparent via-[#bc4749]/20 to-transparent"></div>

                        <div class="mb-6 flex items-center gap-3">
                            <span class="font-mono text-xs font-semibold uppercase tracking-[0.15em] text-[#bc4749]">01</span>
                            <div class="h-px flex-1 bg-gradient-to-r from-[#bc4749]/20 to-transparent"></div>
                        </div>

                        <div class="w-12 h-12 bg-[#1b4332]/10 flex items-center justify-center text-[#1b4332] mb-5" style="border-radius: 2px 12px 2px 12px;">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                        </div>

                        <h3 class="font-display text-xl font-bold text-[#1a1a1a] mb-3">Sleep That Actually Works</h3>
                        <p class="text-[#1a1a1a]/60 text-sm leading-relaxed font-serif">
                            It's not just about hours in bed. Your sleep environment, screen habits, and meal timing all play a role. Get recommendations that fit your actual schedule—not some generic 10pm bedtime rule.
                        </p>
                    </div>

                    <!-- Card 2 -->
                    <div class="group relative overflow-hidden bg-white p-8 shadow-[0_4px_24px_-8px_rgba(26,26,26,0.1)] transition-all duration-500 hover:shadow-[0_12px_40px_-12px_rgba(26,26,26,0.18)]"
                         style="border-radius: 2px 28px 2px 28px;">
                        <div class="absolute top-0 left-6 right-6 h-px bg-gradient-to-r from-transparent via-[#bc4749]/20 to-transparent"></div>

                        <div class="mb-6 flex items-center gap-3">
                            <span class="font-mono text-xs font-semibold uppercase tracking-[0.15em] text-[#bc4749]">02</span>
                            <div class="h-px flex-1 bg-gradient-to-r from-[#bc4749]/20 to-transparent"></div>
                        </div>

                        <div class="w-12 h-12 bg-[#bc4749]/10 flex items-center justify-center text-[#bc4749] mb-5" style="border-radius: 2px 12px 2px 12px;">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>

                        <h3 class="font-display text-xl font-bold text-[#1a1a1a] mb-3">Stress Without the Overwhelm</h3>
                        <p class="text-[#1a1a1a]/60 text-sm leading-relaxed font-serif">
                            You can't eliminate stress entirely—that's not the goal. But you can build better recovery patterns. Practical breathing techniques, micro-habits, and routine tweaks that actually move the needle.
                        </p>
                    </div>

                    <!-- Card 3 -->
                    <div class="group relative overflow-hidden bg-white p-8 shadow-[0_4px_24px_-8px_rgba(26,26,26,0.1)] transition-all duration-500 hover:shadow-[0_12px_40px_-12px_rgba(26,26,26,0.18)]"
                         style="border-radius: 2px 28px 2px 28px;">
                        <div class="absolute top-0 left-6 right-6 h-px bg-gradient-to-r from-transparent via-[#bc4749]/20 to-transparent"></div>

                        <div class="mb-6 flex items-center gap-3">
                            <span class="font-mono text-xs font-semibold uppercase tracking-[0.15em] text-[#bc4749]">03</span>
                            <div class="h-px flex-1 bg-gradient-to-r from-[#bc4749]/20 to-transparent"></div>
                        </div>

                        <div class="w-12 h-12 bg-[#1b4332]/10 flex items-center justify-center text-[#1b4332] mb-5" style="border-radius: 2px 12px 2px 12px;">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                            </svg>
                        </div>

                        <h3 class="font-display text-xl font-bold text-[#1a1a1a] mb-3">Hydration That Makes Sense</h3>
                        <p class="text-[#1a1a1a]/60 text-sm leading-relaxed font-serif">
                            The "8 glasses a day" rule is oversimplified. Your needs depend on activity, climate, and what you're eating. Get practical reminders and learn to read your body's signals instead of chasing arbitrary numbers.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- How It Works — Editorial Steps -->
        <section class="relative py-20 sm:py-28 overflow-hidden">
            <div class="absolute inset-0 bg-[#1b4332]" aria-hidden="true"></div>
            <x-paper-grain class="opacity-30" />

            <!-- Decorative botanical SVG -->
            <svg class="absolute top-8 right-8 w-32 h-32 opacity-[0.06] pointer-events-none" viewBox="0 0 120 120" fill="none" aria-hidden="true">
                <path d="M60 10C60 10 20 40 20 80c0 22 17.9 40 40 40s40-18 40-40c0-40-40-70-40-70z" stroke="#f7f3ed" stroke-width="1.5" fill="none"/>
                <path d="M60 30v80M60 55c-10-8-24-4-32 8M60 70c10-8 24-4 32 8" stroke="#f7f3ed" stroke-width="1" stroke-linecap="round" opacity="0.5"/>
            </svg>

            <div class="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <div class="flex items-center justify-center gap-4 mb-6">
                        <span class="h-px w-12 bg-[#f7f3ed]/40"></span>
                        <span class="font-mono text-xs font-semibold uppercase tracking-[0.2em] text-[#f7f3ed]/60">
                            Process
                        </span>
                        <span class="h-px w-12 bg-[#f7f3ed]/40"></span>
                    </div>
                    <h2 class="font-display text-3xl font-bold text-[#f7f3ed] sm:text-4xl lg:text-5xl">
                        How It Works
                    </h2>
                    <p class="mt-5 text-lg text-[#f7f3ed]/70 leading-relaxed font-serif">
                        No trackers, no spreadsheets, no endless logging. Just describe how you're feeling.
                    </p>
                </div>

                <div class="grid gap-8 md:grid-cols-3">
                    <div class="relative">
                        <div class="flex items-center gap-3 mb-6">
                            <span class="font-display text-4xl font-bold text-[#f7f3ed]/20">01</span>
                            <div class="h-px flex-1 bg-gradient-to-r from-[#f7f3ed]/20 to-transparent"></div>
                        </div>
                        <h3 class="font-display text-xl font-bold text-[#f7f3ed] mb-3">Tell It What's Bothering You</h3>
                        <p class="text-[#f7f3ed]/60 leading-relaxed font-serif">
                            Can't sleep? Stressed about work? Forgot to drink water all day? Just say it. No structured forms, no 47 questions to answer. It understands context.
                        </p>
                    </div>

                    <div class="relative">
                        <div class="flex items-center gap-3 mb-6">
                            <span class="font-display text-4xl font-bold text-[#f7f3ed]/20">02</span>
                            <div class="h-px flex-1 bg-gradient-to-r from-[#f7f3ed]/20 to-transparent"></div>
                        </div>
                        <h3 class="font-display text-xl font-bold text-[#f7f3ed] mb-3">Get a Routine That Fits</h3>
                        <p class="text-[#f7f3ed]/60 leading-relaxed font-serif">
                            Instead of generic advice, you get a routine built around your life. Morning sunlight if you're not getting it. Evening wind-down if you're wired at night. Things that actually work for your schedule.
                        </p>
                    </div>

                    <div class="relative">
                        <div class="flex items-center gap-3 mb-6">
                            <span class="font-display text-4xl font-bold text-[#f7f3ed]/20">03</span>
                            <div class="h-px flex-1 bg-gradient-to-r from-[#f7f3ed]/20 to-transparent"></div>
                        </div>
                        <h3 class="font-display text-xl font-bold text-[#f7f3ed] mb-3">Track Progress Without the Friction</h3>
                        <p class="text-[#f7f3ed]/60 leading-relaxed font-serif">
                            Check in naturally over time. The system remembers your context and adjusts recommendations as your habits shift. No manual tracking required unless you want it.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <x-related-reading
            title="New Ways to Use Your Health Data"
            description="Read how Acara turns logged habits, activity, sleep, and body metrics into practical progress reviews."
            :articles="[
                [
                    'title' => 'Introducing the Nutrition Analyzer: Evidence-Based Nutrient Adequacy and Food-First Coaching',
                    'description' => 'A new Acara protocol for reviewing nutrient adequacy, intake trends, diet quality, and the most useful food-first change to make next.',
                    'url' => route('post.show', 'nutrition-analyzer-launch'),
                    'image' => 'https://pub-plate-assets.acara.app/blog/nutrition-analyzer.webp',
                    'imageAlt' => 'Nutrition Analyzer reviewing food logs and nutrient adequacy',
                    'category' => 'Product Update',
                    'readingTime' => '9 min read',
                ],
                [
                    'title' => 'Introducing the Acara Weight-Loss Analyzer',
                    'description' => 'A structured progress-review protocol for calorie balance, plateau troubleshooting, nutrition quality, and sustainable next steps.',
                    'url' => route('post.show', 'weightloss-analyzer-launch'),
                    'image' => 'https://pub-plate-assets.acara.app/blog/weightloss-analyzer.png',
                    'imageAlt' => 'Acara Weight-Loss Analyzer showing AI progress review concepts',
                    'category' => 'Product Update',
                    'readingTime' => '9 min read',
                ],
            ]"
        />

        <!-- FAQ — Editorial Accordion -->
        <section class="relative py-20 sm:py-28 overflow-hidden">
            <x-paper-grain class="opacity-40" />

            <div class="relative z-10 mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-14">
                    <div class="flex items-center justify-center gap-4 mb-6">
                        <span class="h-px w-12 bg-[#bc4749]"></span>
                        <span class="font-mono text-xs font-semibold uppercase tracking-[0.2em] text-[#bc4749]">
                            Common Questions
                        </span>
                        <span class="h-px w-12 bg-[#bc4749]"></span>
                    </div>
                    <h2 class="font-display text-3xl font-bold text-[#1a1a1a] sm:text-4xl">
                        What People Ask
                    </h2>
                </div>

                <div class="space-y-4">
                    <details class="group relative overflow-hidden bg-white shadow-[0_2px_12px_-4px_rgba(26,26,26,0.08)] transition-all duration-300"
                             style="border-radius: 2px 16px 2px 16px;">
                        <div class="absolute top-0 left-5 right-5 h-px bg-gradient-to-r from-transparent via-[#bc4749]/20 to-transparent"></div>
                        <summary class="flex cursor-pointer items-center justify-between p-6 font-display text-lg font-bold text-[#1a1a1a]">
                            What areas can the AI Health Coach help with?
                            <svg class="h-5 w-5 text-[#1a1a1a]/30 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="px-6 pb-6 text-[#1a1a1a]/60 leading-relaxed font-serif">
                            Sleep optimization, stress management, hydration, and general lifestyle wellness. It's not a replacement for therapy or medical care, but it gives you practical, evidence-based routines that fit your actual life.
                        </p>
                    </details>

                    <details class="group relative overflow-hidden bg-white shadow-[0_2px_12px_-4px_rgba(26,26,26,0.08)] transition-all duration-300"
                             style="border-radius: 2px 16px 2px 16px;">
                        <div class="absolute top-0 left-5 right-5 h-px bg-gradient-to-r from-transparent via-[#bc4749]/20 to-transparent"></div>
                        <summary class="flex cursor-pointer items-center justify-between p-6 font-display text-lg font-bold text-[#1a1a1a]">
                            Is this really open source?
                            <svg class="h-5 w-5 text-[#1a1a1a]/30 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="px-6 pb-6 text-[#1a1a1a]/60 leading-relaxed font-serif">
                            Yep. The code's on GitHub. You can verify how recommendations are generated, check the privacy controls, and even fork it if you want to build your own version. We welcome contributions from developers and health enthusiasts alike.
                        </p>
                    </details>

                    <details class="group relative overflow-hidden bg-white shadow-[0_2px_12px_-4px_rgba(26,26,26,0.08)] transition-all duration-300"
                             style="border-radius: 2px 16px 2px 16px;">
                        <div class="absolute top-0 left-5 right-5 h-px bg-gradient-to-r from-transparent via-[#bc4749]/20 to-transparent"></div>
                        <summary class="flex cursor-pointer items-center justify-between p-6 font-display text-lg font-bold text-[#1a1a1a]">
                            How is my privacy protected?
                            <svg class="h-5 w-5 text-[#1a1a1a]/30 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="px-6 pb-6 text-[#1a1a1a]/60 leading-relaxed font-serif">
                            Privacy-first, always. We don't sell your data to advertisers or insurance companies. Since the code is open source, these aren't just marketing claims—you can verify them yourself.
                        </p>
                    </details>

                    <details class="group relative overflow-hidden bg-white shadow-[0_2px_12px_-4px_rgba(26,26,26,0.08)] transition-all duration-300"
                             style="border-radius: 2px 16px 2px 16px;">
                        <div class="absolute top-0 left-5 right-5 h-px bg-gradient-to-r from-transparent via-[#bc4749]/20 to-transparent"></div>
                        <summary class="flex cursor-pointer items-center justify-between p-6 font-display text-lg font-bold text-[#1a1a1a]">
                            Do I need to track everything manually?
                            <svg class="h-5 w-5 text-[#1a1a1a]/30 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="px-6 pb-6 text-[#1a1a1a]/60 leading-relaxed font-serif">
                            No. That's the whole point. Unlike other wellness apps that turn logging into a second job, this one just asks how you're doing. Describe your day, your challenges, your energy levels—and it figures out the patterns.
                        </p>
                    </details>
                </div>
            </div>
        </section>

        <!-- Part of Something Bigger — Editorial CTA -->
        <section class="relative py-24 px-4 overflow-hidden">
            <div class="absolute inset-0 bg-[#f7f3ed]" aria-hidden="true"></div>
            <x-paper-grain />

            <div class="relative z-10 max-w-3xl mx-auto text-center">
                <div class="flex items-center justify-center gap-4 mb-6">
                    <span class="h-px w-12 bg-[#bc4749]"></span>
                    <span class="font-mono text-xs font-semibold uppercase tracking-[0.2em] text-[#bc4749]">
                        Our Mission
                    </span>
                    <span class="h-px w-12 bg-[#bc4749]"></span>
                </div>

                <h2 class="font-display text-3xl font-bold text-[#1a1a1a] sm:text-4xl">
                    Part of Something Bigger
                </h2>
                <p class="mt-5 text-lg text-[#1a1a1a]/60 leading-relaxed font-serif">
                    We're building an open science health stack because we got tired of wellness data being locked in proprietary apps. Your health data should be yours—verifiable, portable, and transparent.
                </p>
                <div class="mt-8 flex justify-center">
                    <a href="{{ route('register') }}" class="group inline-flex items-center gap-2 rounded-full bg-white px-6 py-3 text-base font-semibold text-[#1b4332] shadow-sm ring-1 ring-[#1a1a1a]/10 transition-all duration-200 hover:bg-[#1b4332] hover:text-[#f7f3ed] hover:ring-[#1b4332]">
                        <x-editorial-underline>See what it can do</x-editorial-underline>
                    </a>
                </div>
            </div>
        </section>

        <div class="py-8">
            <x-ios-app-promo
                eyebrow="New — Apple Health integration"
                headline="Your coach, backed by your actual sleep and stress data"
                body="Sleep, HRV, hydration, and activity sync from Apple Health automatically. The coach recommends based on your real patterns — not what you remembered to type in yesterday. Because telling your coach you slept fine when you didn't isn't helping anyone."
                :features="['Sleep & HRV tracking', 'Hydration & activity sync', 'Stress trend context', 'Private by design']"
            />
        </div>

        <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
            <x-cta-block
                title="Get to Know Altani Better"
                description="Meet your AI health coach — ready to help with sleep, stress, nutrition, and daily wellness support."
                button-text="Meet Altani"
            />
        </div>
    </div>
    <x-footer />
</x-default-layout>
