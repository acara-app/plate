@section('title', 'Acara Plate for Dietitians & Nutritionists | Client Meal Planning Software')
@section('meta_description', 'Empower your nutrition practice with AI meal planning, client tracking, and Telegram integration. Serve more clients with less busywork. Free for practitioners during beta.')
@section('meta_keywords', 'dietitian meal planning software, nutritionist client management, AI meal planner for RDs, client tracking software nutrition, practitioner meal planning')
@section('canonical_url', url()->current())

@section('head')

<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "WebPage",
    "name": "Acara Plate for Dietitians & Nutritionists",
    "description": "Client meal planning software for registered dietitians and nutritionists with AI-powered meal generation and client tracking.",
    "speakable": {
        "@@type": "SpeakableSpecification",
        "cssSelector": [".speakable-intro"]
    },
    "publisher": {
        "@@type": "Organization",
        "name": "Acara Plate"
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
            "name": "Can I use Acara Plate if I'm not a registered dietitian?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Yes, but with caveats. Health coaches and nutritionists can use the platform, but should practice within their scope. The platform doesn't validate credentials — it is your responsibility to ensure compliance with your state regulations."
            }
        },
        {
            "@@type": "Question",
            "name": "Is this a medical device? Do I need FDA clearance?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "No. Acara Plate is practice management and client engagement software. It provides meal suggestions and tracks data, but doesn't diagnose or prescribe. Clinical decisions remain with you, the licensed provider."
            }
        },
        {
            "@@type": "Question",
            "name": "What about HIPAA compliance?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "As a self-hosted platform, you own your data. We do not have access to your client information. Compliance depends on your hosting environment and security practices."
            }
        },
        {
            "@@type": "Question",
            "name": "Can I export client data?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Yes. CSV export for all client logs, meal plans, and trends. No lock-in."
            }
        },
        {
            "@@type": "Question",
            "name": "Do my clients need to pay?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Not necessarily. You can include Acara Plate access in your service fee, have clients subscribe directly ($12/month), or white-label and set your own pricing."
            }
        },
        {
            "@@type": "Question",
            "name": "What if the AI suggests something inappropriate for my client?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "You maintain full control. Review and approve all meal plans before clients see them. The AI is a starting point, not the final clinical decision."
            }
        },
        {
            "@@type": "Question",
            "name": "Can I customize the diet types?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Within the 8 supported dietary patterns, yes. You can adjust calorie targets, macronutrient ratios, and exclude specific foods. Fully custom protocol creation is on the roadmap."
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
            "name": "For Dietitians"
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
                    <a href="{{ route('support') }}" class="inline-flex items-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition-all duration-200 hover:bg-slate-800">
                        Contact
                    </a>
                </div>
            </div>
        </header>

        {{-- Hero Section --}}
        <section class="relative overflow-hidden pt-12 pb-16 sm:pt-20 sm:pb-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="lg:grid lg:grid-cols-12 lg:gap-16 items-center">
                    <div class="lg:col-span-7 text-center lg:text-left speakable-intro">
                        <div class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-sm font-medium text-emerald-800 mb-6">
                            <svg class="h-4 w-4 text-emerald-600" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
                            </svg>
                            Free for Practitioners During Beta
                        </div>
                        <h1 class="text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl">
                            The Meal Planning Tool You'll Actually Want to Use
                        </h1>
                        <p class="mt-6 text-lg text-slate-600 leading-relaxed">
                            Here's what nobody talks about: you became a dietitian to help people change their lives through food. Not to spend three hours building meal plans in spreadsheets. We built the tool we wished existed.
                        </p>
                        <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                            <a href="{{ route('support') }}" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-6 py-3 text-base font-semibold text-white shadow-sm hover:bg-slate-800 transition-all">
                                Request Early Access
                                <span class="ml-2 text-slate-400">→</span>
                            </a>
                            <a href="https://github.com/acara-app/plate" target="_blank" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-6 py-3 text-base font-medium text-slate-700 hover:bg-slate-50 transition-all">
                                View Source Code
                            </a>
                        </div>
                        <p class="mt-4 text-sm text-slate-500">
                            No commitment required. Beta access is free for qualified practitioners.
                        </p>
                    </div>

                    <div class="mt-12 lg:mt-0 lg:col-span-5">
                        <div class="relative rounded-xl bg-slate-900 shadow-2xl ring-1 ring-white/10" aria-hidden="true">
                            <div class="flex items-center gap-2 border-b border-white/10 px-4 py-3 bg-white/5 rounded-t-xl">
                                <div class="flex gap-1.5">
                                    <div class="h-3 w-3 rounded-full bg-red-500"></div>
                                    <div class="h-3 w-3 rounded-full bg-yellow-500"></div>
                                    <div class="h-3 w-3 rounded-full bg-green-500"></div>
                                </div>
                                <div class="text-xs font-mono text-slate-400 ml-2">acara — practitioner dashboard</div>
                            </div>

                            <div class="p-6 font-mono text-sm space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-400">Client: Sarah M.</span>
                                    <span class="text-emerald-400 text-xs">Type 2 Diabetes</span>
                                </div>
                                <div class="bg-white/10 rounded p-3 text-slate-200">
                                    <div class="text-emerald-400 mb-2">✓ Meal plan generated (7 days)</div>
                                    <div class="text-slate-300 text-xs space-y-1">
                                        <div>• Mediterranean diet profile</div>
                                        <div>• 1,800 cal target</div>
                                        <div>• 45% carbs / 20% protein / 35% fat</div>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-slate-400">Adherence (7-day)</span>
                                    <span class="text-emerald-400">87%</span>
                                </div>
                                <div class="w-full bg-slate-700 rounded-full h-2">
                                    <div class="bg-emerald-500 h-2 rounded-full" style="width: 87%"></div>
                                </div>
                                <div class="text-slate-300 text-xs border-l-2 border-amber-500 pl-3">
                                    <span class="text-amber-400">⚠ Note:</span> Client logged high glucose after pasta dinner (Day 3). Recommend portion adjustment.
                                </div>
                                <div class="animate-pulse text-slate-500 mt-4">
                                    <span class="text-slate-400">Ready for next session</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- The Problem Section --}}
        <section class="bg-slate-50 py-16 sm:py-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900">
                        Here's What Actually Happens in Most Practices
                    </h2>
                    <p class="mt-4 text-lg text-slate-600">
                        You went to school for nutrition science. Here's what your typical day actually looks like:
                    </p>
                </div>

                <div class="mt-12 grid gap-8 md:grid-cols-3">
                    <div class="bg-white rounded-xl p-8 shadow-sm border border-slate-200">
                        <div class="w-12 h-12 bg-rose-100 rounded-lg flex items-center justify-center text-rose-600 mb-6">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">The Copy-Paste Grind</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">
                            Building meal plans in Word, manually formatting macros in Excel, emailing PDFs back and forth. Hours of your week gone to admin work that a computer should handle.
                        </p>
                    </div>

                    <div class="bg-white rounded-xl p-8 shadow-sm border border-slate-200">
                        <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center text-amber-600 mb-6">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">The Client Chase</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">
                            Sending reminders for food logs. Following up on adherence. The endless back-and-forth just to get basic data. It takes time away from the meaningful work that actually changes lives.
                        </p>
                    </div>

                    <div class="bg-white rounded-xl p-8 shadow-sm border border-slate-200">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600 mb-6">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-3">The Macro Recalculation Spiral</h3>
                        <p class="text-slate-600 text-sm leading-relaxed">
                            Client wants to try keto? Back to the spreadsheet. Switching to Mediterranean? Manually recalculate everything. Every diet change means starting from scratch.
                        </p>
                    </div>
                </div>

                <div class="mt-12 text-center">
                    <p class="text-lg text-slate-900 font-medium">
                        You became an RD to help people. The admin overhead just eats away your billable hours.
                    </p>
                </div>
            </div>
        </section>

        {{-- The Solution Section --}}
        <section class="py-16 sm:py-24 bg-white">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900">
                        Client Meal Planning That Grows With Your Practice
                    </h2>
                    <p class="mt-4 text-lg text-slate-600">
                        Think of it as an AI assistant that handles the repetitive work so you can focus on what actually matters—your clients and your expertise.
                    </p>
                </div>

                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="flex gap-4 p-6 rounded-xl border border-slate-200 bg-slate-50">
                        <div class="flex-shrink-0 w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center text-emerald-600">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">AI Meal Plan Generator</h3>
                            <p class="mt-2 text-sm text-slate-600">Generate personalized 7-day plans in minutes based on client profiles and diet preferences. No more manual recipe hunting.</p>
                        </div>
                    </div>

                    <div class="flex gap-4 p-6 rounded-xl border border-slate-200 bg-slate-50">
                        <div class="flex-shrink-0 w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">
                                <a href="{{ route('telegram-health-logging') }}" class="hover:text-emerald-600">Telegram Integration</a>
                            </h3>
                            <p class="mt-2 text-sm text-slate-600">Clients log meals via Telegram chat — no app download required. See client data instantly when they log it. 3x better adherence than traditional apps.</p>
                        </div>
                    </div>

                    <div class="flex gap-4 p-6 rounded-xl border border-slate-200 bg-slate-50">
                        <div class="flex-shrink-0 w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center text-orange-600">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">
                                <a href="{{ route('spike-calculator') }}" class="hover:text-emerald-600">Glucose Response Estimator</a>
                            </h3>
                            <p class="mt-2 text-sm text-slate-600">Estimate blood sugar impact of any meal for clients with diabetes or pre-diabetes. Evidence-based recommendations at your fingertips.</p>
                        </div>
                    </div>

                    <div class="flex gap-4 p-6 rounded-xl border border-slate-200 bg-slate-50">
                        <div class="flex-shrink-0 w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center text-purple-600">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">8 Evidence-Based Dietary Patterns</h3>
                            <p class="mt-2 text-sm text-slate-600">Mediterranean, DASH, Low Carb, Ketogenic, Paleo, Vegetarian, Vegan, and Balanced. Serve diverse client needs without separate systems.</p>
                        </div>
                    </div>

                    <div class="flex gap-4 p-6 rounded-xl border border-slate-200 bg-slate-50">
                        <div class="flex-shrink-0 w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center text-teal-600">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Multi-Client Dashboard</h3>
                            <p class="mt-2 text-sm text-slate-600">See all clients' adherence, glucose trends, and goals at a glance. Prep for sessions in 5 minutes instead of 30.</p>
                        </div>
                    </div>

                    <div class="flex gap-4 p-6 rounded-xl border border-slate-200 bg-slate-50">
                        <div class="flex-shrink-0 w-12 h-12 bg-slate-100 rounded-lg flex items-center justify-center text-slate-600">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Open Source</h3>
                            <p class="mt-2 text-sm text-slate-600">Transparent, community-driven development. No vendor lock-in. CSV exports for all data. You own your practice's information.</p>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-center">
                        @include('components.cgm-coming-soon')
                    </div>
                </div>
            </div>
        </section>

        {{-- How It Works Section --}}
        <section class="bg-slate-50 py-16 sm:py-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900">
                        Three Ways Dietitians Use This
                    </h2>
                    <p class="mt-4 text-lg text-slate-600">
                        Flexible workflows for different practice types
                    </p>
                </div>

                <div class="grid gap-8 lg:grid-cols-3">
                    {{-- Use Case 1 --}}
                    <div class="bg-white rounded-xl p-8 shadow-sm border border-slate-200">
                        <div class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-sm font-medium text-emerald-800 mb-4">
                            Individual
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-4">Individual Client Management</h3>
                        <p class="text-slate-600 text-sm mb-6">Perfect for one-on-one nutrition counseling</p>

                        <div class="space-y-3 text-sm">
                            <div class="flex items-start gap-3">
                                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">1</span>
                                <p class="text-slate-600">Assess client in initial consult</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">2</span>
                                <p class="text-slate-600">Preset diet type, calorie targets, and restrictions</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">3</span>
                                <p class="text-slate-600">AI generates personalized 7-day meal plan instantly</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">4</span>
                                <p class="text-slate-600">Client logs meals via Telegram — you see client data instantly when they log it</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">5</span>
                                <p class="text-slate-600">Review trends before next session and adjust</p>
                            </div>
                        </div>

                        <div class="mt-6 p-4 bg-emerald-50 rounded-lg border border-emerald-100">
                            <p class="text-sm text-emerald-800 font-medium">⏱ Efficiency: Reclaim ~2 hours of administrative time per client per week</p>
                        </div>
                    </div>

                    {{-- Use Case 2 --}}
                    <div class="bg-white rounded-xl p-8 shadow-sm border border-slate-200">
                        <div class="inline-flex items-center gap-2 rounded-full bg-blue-100 px-3 py-1 text-sm font-medium text-blue-800 mb-4">
                            Group
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-4">Group Programs</h3>
                        <p class="text-slate-600 text-sm mb-6">For 8-week resets, weight loss cohorts, corporate wellness</p>

                        <div class="space-y-3 text-sm">
                            <div class="flex items-start gap-3">
                                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">1</span>
                                <p class="text-slate-600">Create a cohort with shared diet parameters</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">2</span>
                                <p class="text-slate-600">Each participant gets personalized meal plans</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">3</span>
                                <p class="text-slate-600">Group accountability via Telegram or community features</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">4</span>
                                <p class="text-slate-600">Monitor cohort-wide trends and intervene when needed</p>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-slate-100 text-slate-700 text-xs font-bold flex items-center justify-center">5</span>
                                <p class="text-slate-600">Export reports for outcomes tracking</p>
                            </div>
                        </div>

                        <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-100">
                            <p class="text-sm text-blue-800 font-medium">📈 Scalability: Deliver high-quality care to 20+ participants in the time it takes to manage one individual</p>
                        </div>
                    </div>

                    {{-- Use Case 3 --}}
                    <div class="bg-white rounded-xl p-8 shadow-sm border border-slate-200">
                        <div class="inline-flex items-center gap-2 rounded-full bg-purple-100 px-3 py-1 text-sm font-medium text-purple-800 mb-4">
                            Enterprise
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 mb-4">White-Label Partnership</h3>
                        <p class="text-slate-600 text-sm mb-6">For practices wanting their own branded app</p>

                        <div class="space-y-3 text-sm">
                            <div class="flex items-start gap-2">
                                <span class="text-emerald-500">✓</span>
                                <p class="text-slate-600"><strong>Your brand</strong> — fully branded experience</p>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="text-emerald-500">✓</span>
                                <p class="text-slate-600">Your logo, your colors, your domain</p>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="text-emerald-500">✓</span>
                                <p class="text-slate-600">Same powerful AI backend</p>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="text-emerald-500">✓</span>
                                <p class="text-slate-600">Your clients never see "Acara Plate"</p>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="text-emerald-500">✓</span>
                                <p class="text-slate-600">Set your own pricing and packages</p>
                            </div>
                        </div>

                        <div class="mt-6 p-4 bg-purple-50 rounded-lg border border-purple-100">
                            <p class="text-sm text-purple-800 font-medium">🏷 Brand Equity: Professionalize your practice with a custom-branded client portal</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Evidence & Approach Section --}}
        <section class="py-16 sm:py-24 bg-white">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900">
                        Built by People Who Actually Understand Nutrition
                    </h2>
                    <p class="mt-4 text-lg text-slate-600">
                        This isn't a Silicon Valley "move fast and break things" project. We take nutrition science seriously.
                    </p>
                </div>

                <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-4">
                    <div class="bg-slate-50 rounded-xl p-6">
                        <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center text-emerald-600 mb-4">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-2">Evidence-Based Frameworks</h3>
                        <p class="text-sm text-slate-600">Each diet type maps to established clinical guidelines (ADA, AHA, Mediterranean Diet Foundation).</p>
                    </div>

                    <div class="bg-slate-50 rounded-xl p-6">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600 mb-4">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-2">Macronutrient Targets</h3>
                        <p class="text-sm text-slate-600">AI enforces diet-appropriate macronutrient splits (Ketogenic: 5% carbs, Mediterranean: 45% carbs).</p>
                    </div>

                    <div class="bg-slate-50 rounded-xl p-6">
                        <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center text-orange-600 mb-4">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-2">
                            <a href="{{ route('spike-calculator') }}" class="hover:text-emerald-600">Glucose-Aware Algorithms</a>
                        </h3>
                        <p class="text-sm text-slate-600">Meal suggestions prioritize low-glycemic options for clients with diabetes.</p>
                    </div>

                    <div class="bg-slate-50 rounded-xl p-6">
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center text-purple-600 mb-4">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h3 class="text-base font-bold text-slate-900 mb-2">Dietitian Input</h3>
                        <p class="text-sm text-slate-600">Developed with feedback from practicing RDs who understand real-world workflows.</p>
                    </div>
                </div>

                <div class="mt-12 text-center">
                    <p class="text-lg text-slate-700">
                        The AI doesn't replace your expertise. It handles the repetitive work so you can focus on the complex cases that need your clinical judgment.
                    </p>
                </div>
            </div>
        </section>

        {{-- FAQ Section --}}
        <section class="bg-slate-50 py-16 sm:py-24">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-slate-900">Questions From Fellow Practitioners</h2>
                </div>

                <div class="space-y-4">
                    <details class="group rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <summary class="flex cursor-pointer items-center justify-between font-semibold text-slate-900">
                            Can I use Acara Plate if I'm not a registered dietitian?
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="mt-4 text-slate-600">
                            Yes, but with caveats. Health coaches and nutritionists can use the platform, but should practice within their scope. The platform doesn't validate credentials — it is your responsibility to ensure compliance with your state regulations.
                        </p>
                    </details>

                    <details class="group rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <summary class="flex cursor-pointer items-center justify-between font-semibold text-slate-900">
                            Is this a medical device? Do I need FDA clearance?
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="mt-4 text-slate-600">
                            No. Acara Plate is practice management and client engagement software. It provides meal suggestions and tracks data, but doesn't diagnose or prescribe. Clinical decisions remain with you, the licensed provider.
                        </p>
                    </details>

                    <details class="group rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <summary class="flex cursor-pointer items-center justify-between font-semibold text-slate-900">
                            What about HIPAA compliance?
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="mt-4 text-slate-600">
                            As a self-hosted platform, you own your data. We do not have access to your client information. Compliance depends on your hosting environment and security practices.
                        </p>
                    </details>

                    <details class="group rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <summary class="flex cursor-pointer items-center justify-between font-semibold text-slate-900">
                            Can I export client data?
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="mt-4 text-slate-600">
                            Yes. CSV export for all client logs, meal plans, and trends. No lock-in.
                        </p>
                    </details>

                    <details class="group rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <summary class="flex cursor-pointer items-center justify-between font-semibold text-slate-900">
                            Do my clients need to pay?
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="mt-4 text-slate-600">
                            Not necessarily. You can include Acara Plate access in your service fee, have clients subscribe directly ($12/month), or white-label and set your own pricing.
                        </p>
                    </details>

                    <details class="group rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <summary class="flex cursor-pointer items-center justify-between font-semibold text-slate-900">
                            What if the AI suggests something inappropriate for my client?
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="mt-4 text-slate-600">
                            You maintain full control. Review and approve all meal plans before clients see them. The AI is a starting point, not the final clinical decision.
                        </p>
                    </details>

                    <details class="group rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                        <summary class="flex cursor-pointer items-center justify-between font-semibold text-slate-900">
                            Can I customize the diet types?
                            <svg class="h-5 w-5 text-slate-500 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </summary>
                        <p class="mt-4 text-slate-600">
                            Within the 8 supported dietary patterns, yes. You can adjust calorie targets, macronutrient ratios, and exclude specific foods. Fully custom protocol creation is on the roadmap.
                        </p>
                    </details>
                </div>
            </div>
        </section>

        {{-- Comparison Section --}}
        <section class="py-16 sm:py-24 bg-white">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <h2 class="text-3xl font-bold tracking-tight text-slate-900">
                        "But I Already Use..."
                    </h2>
                    <p class="mt-4 text-lg text-slate-600">
                        Here's how we compare to tools you might already know
                    </p>
                </div>

                <div class="grid gap-8 md:grid-cols-2">
                    <div class="bg-slate-50 rounded-xl p-6 border border-slate-200">
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Consumer Apps?</h3>
                        <p class="text-slate-600 text-sm">Built for end-users, not clinicians. Acara Plate provides a dedicated practitioner dashboard to monitor adherence, trending data, and intervene immediately when issues arise.</p>
                    </div>

                    <div class="bg-slate-50 rounded-xl p-6 border border-slate-200">
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Analysis Tools?</h3>
                        <p class="text-slate-600 text-sm">Excellent for retrospective nutrient analysis, but time-consuming for prospective planning. Acara Plate answers your client's #1 question—"What should I eat?"—with instantly generated plans.</p>
                    </div>

                    <div class="bg-slate-50 rounded-xl p-6 border border-slate-200">
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Spreadsheets?</h3>
                        <p class="text-slate-600 text-sm">Static documents require manual updates for every macro adjustment. Acara Plate is dynamic—instantly recalculating entire weeks of meals as you adjust clinical parameters.</p>
                    </div>

                    <div class="bg-slate-50 rounded-xl p-6 border border-slate-200">
                        <h3 class="text-lg font-bold text-slate-900 mb-3">Proprietary Platforms?</h3>
                        <p class="text-slate-600 text-sm">Proprietary platforms cap your earning potential and lock away your data. Acara Plate is open-source software that gives you full ownership of your practice's data and revenue.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- The Vision Section --}}
        <section class="bg-slate-900 py-16 sm:py-24">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl font-bold tracking-tight text-white">
                    What We're Building
                </h2>
                <p class="mt-6 text-lg text-slate-300">
                    Imagine a world where every dietitian has AI help for the repetitive tasks, clients actually follow their meal plans because they're personalized and easy to log, and nutrition counseling scales beyond the 1:1 hourly session model.
                </p>
                <p class="mt-4 text-lg text-slate-300">
                    That's what we're building. And we need practitioners like you to help shape it.
                </p>
            </div>
        </section>

        {{-- Dashboard Preview Section --}}
        <section class="py-16 sm:py-24 bg-[#FFFBF5]">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-slate-100">
                    <div class="p-8 sm:p-12">
                        <div class="text-center mb-8">
                            <h2 class="text-2xl font-bold text-slate-900 sm:text-3xl">
                                See Your Client Dashboard
                            </h2>
                            <p class="mt-3 text-slate-600 max-w-xl mx-auto">
                                Track adherence, monitor trends, and manage multiple clients from one clean interface. Everything you need to deliver exceptional care.
                            </p>
                        </div>

                        <figure class="w-full">
                            <div class="relative overflow-hidden rounded-2xl shadow-lg ring-1 ring-slate-900/5">
                                <picture>
                                    <source srcset="{{ asset('meal-plan-hero-section.webp') }}" type="image/webp">
                                    <img src="{{ asset('meal-plan-hero-section.webp') }}"
                                         alt="AI-powered meal planning dashboard showing personalized nutrition recommendations for dietitians"
                                         class="w-full">
                                </picture>
                            </div>
                        </figure>
                    </div>
                </div>
            </div>
        </section>

        {{-- Final CTA Section --}}
        <section class="py-16 sm:py-24 bg-white">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl font-bold tracking-tight text-slate-900">
                    Ready to Scale Your Nutrition Practice?
                </h2>
                <p class="mt-4 text-lg text-slate-600">
                    Join practitioners who are replacing spreadsheets with AI, and hourly billing with scalable programs.
                </p>
                <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('support') }}" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-8 py-4 text-base font-bold text-white shadow-lg transition-all hover:bg-slate-800">
                        Request Early Access
                        <span class="ml-2">→</span>
                    </a>
                    <a href="https://github.com/acara-app/plate" target="_blank" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-8 py-4 text-base font-semibold text-slate-700 shadow-sm transition-all hover:bg-slate-50">
                        View Source Code
                    </a>
                </div>
                <p class="mt-4 text-sm text-slate-500">
                    Free for practitioners during beta • 
                    <a href="{{ route('about') }}" class="text-emerald-600 hover:underline">About the approach</a>
                </p>
            </div>
        </section>

        {{-- Related Tools --}}
        <section class="border-t border-slate-200 py-12 bg-slate-50">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <h3 class="text-sm font-semibold text-slate-900 uppercase tracking-wide mb-6">Related Tools & Resources</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <a href="{{ route('meal-planner') }}" class="group flex items-center gap-3 rounded-lg border border-slate-200 bg-white p-4 transition-all hover:border-emerald-300 hover:bg-emerald-50">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 group-hover:bg-emerald-200">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </span>
                        <div>
                            <span class="font-medium text-slate-900 group-hover:text-emerald-700">AI Meal Planner</span>
                            <p class="text-xs text-slate-500">Personalized meal plans</p>
                        </div>
                    </a>
                    <a href="{{ route('spike-calculator') }}" class="group flex items-center gap-3 rounded-lg border border-slate-200 bg-white p-4 transition-all hover:border-orange-300 hover:bg-orange-50">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-orange-100 text-orange-600 group-hover:bg-orange-200">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </span>
                        <div>
                            <span class="font-medium text-slate-900 group-hover:text-orange-700">Glucose Response Estimator</span>
                            <p class="text-xs text-slate-500">Check glucose impact</p>
                        </div>
                    </a>
                    <a href="{{ route('tools.index') }}" class="group flex items-center gap-3 rounded-lg border border-slate-200 bg-white p-4 transition-all hover:border-blue-300 hover:bg-blue-50">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-600 group-hover:bg-blue-200">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                            </svg>
                        </span>
                        <div>
                            <span class="font-medium text-slate-900 group-hover:text-blue-700">All Tools</span>
                            <p class="text-xs text-slate-500">Full tool directory</p>
                        </div>
                    </a>
                </div>
                <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <a href="{{ route('ai-nutritionist') }}" class="text-sm text-slate-600 hover:text-emerald-600 transition-colors">
                        → <span class="font-medium">AI Nutritionist</span> — Analyze meals for glycemic impact
                    </a>
                    <a href="{{ route('telegram-health-logging') }}" class="text-sm text-slate-600 hover:text-emerald-600 transition-colors">
                        → <span class="font-medium">Telegram Logging</span> — Client meal tracking via chat
                    </a>
                    <a href="{{ route('food.index') }}" class="text-sm text-slate-600 hover:text-emerald-600 transition-colors">
                        → <span class="font-medium">Food Database</span> — Glycemic index & nutrition facts
                    </a>
                </div>
            </div>
        </section>

    </div>
    <x-cta-block
        title="Altani Can Assist Your Clients"
        description="Your clients can chat with Altani for instant answers to nutrition questions between sessions."
        button-text="Learn More"
    />
    <x-footer />
</x-default-layout>
