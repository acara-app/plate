@section('title', 'Free Glucose Spike Calculator | Predict Blood Sugar Impact Instantly')
@section('meta_description', 'Check if foods will spike your blood sugar with our free AI calculator. Get instant glycemic risk analysis and smart food swaps. No sign-up required.')
@section('meta_keywords', 'glucose spike checker, blood sugar spike, glycemic index, food blood sugar impact, diabetes food checker, will food spike blood sugar, free glucose tool, blood sugar calculator, what foods cause blood sugar spikes, low glycemic foods')

@section('head')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [{
        "@@type": "ListItem",
        "position": 1,
        "name": "Home",
        "item": "{{ url('/') }}"
    },{
        "@@type": "ListItem",
        "position": 2,
        "name": "Spike Calculator",
        "item": "{{ url()->current() }}"
    }]
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "FAQPage",
    "mainEntity": [
        {
            "@@type": "Question",
            "name": "What is a glucose spike and why should I care?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "A spike happens when your blood sugar goes up fast after you eat. You might feel tired or hungry afterwards. Keeping your levels steady helps you feel better and stay healthy."
            }
        },
        {
            "@@type": "Question",
            "name": "How does the glucose spike checker work?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "We look at the carbs, fiber, protein, and fat in the food. Then we give you a risk level: Low, Medium, or High. We also suggest foods that might be better for your blood sugar."
            }
        },
        {
            "@@type": "Question",
            "name": "Is this tool a replacement for medical advice?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "No. This tool gives you estimates. It does not replace your doctor. Talk to a medical professional if you have health questions."
            }
        },
        {
            "@@type": "Question",
            "name": "What foods typically cause high blood sugar spikes?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "White bread, white rice, and sugary drinks often cause spikes. Candy and pastries do too. These foods have lots of carbs but not much fiber. Your body digests them fast, which sends sugar into your blood quickly."
            }
        },
        {
            "@@type": "Question",
            "name": "How can I reduce the glycemic impact of my meals?",
            "acceptedAnswer": {
                "@@type": "Answer",
                "text": "Add protein or healthy fats to your meal. Choose whole grains instead of white ones. Eat vegetables first. A short walk after eating also helps."
            }
        }
    ]
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "WebApplication",
    "name": "Glucose Spike Checker",
    "description": "Check if foods will raise your blood sugar. Get simple risk levels and better food ideas.",
    "url": "{{ url()->current() }}",
    "applicationCategory": "HealthApplication",
    "operatingSystem": "Any",
    "offers": {
        "@@type": "Offer",
        "price": "0",
        "priceCurrency": "USD"
    },
    "author": {
        "@@type": "Organization",
        "name": "Acara Plate",
        "url": "{{ url('/') }}"
    },
    "aggregateRating": {
        "@@type": "AggregateRating",
        "ratingValue": "4.8",
        "ratingCount": "150"
    }
}
</script>
{{-- Speakable Structured Data for Voice Search --}}
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "WebPage",
    "name": "Free Glucose Spike Checker",
    "speakable": {
        "@@type": "SpeakableSpecification",
        "cssSelector": [".speakable-intro", ".speakable-how-it-works"]
    },
    "url": "{{ url()->current() }}"
}
</script>
@endsection

<div
    class="relative flex min-h-screen flex-col items-center overflow-hidden bg-linear-to-br from-slate-50 via-white to-emerald-50 p-4 text-slate-900 lg:justify-center lg:p-8 dark:from-slate-950 dark:via-slate-900 dark:to-emerald-950 dark:text-slate-50"
>
    {{-- Animated background elements --}}
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 overflow-hidden">
        <div class="absolute -left-4 top-0 h-72 w-72 animate-pulse rounded-full bg-emerald-300/20 blur-3xl dark:bg-emerald-500/10"></div>
        <div class="absolute -right-4 bottom-0 h-96 w-96 animate-pulse rounded-full bg-teal-300/20 blur-3xl dark:bg-teal-500/10"></div>
    </div>

    {{-- Header --}}
    <header class="relative z-10 mb-6 w-full max-w-md lg:mb-8">
        <nav class="flex items-center justify-center">
            <a href="/" class="flex items-center gap-2 text-xl font-bold text-slate-900 transition-opacity hover:opacity-80 dark:text-white">
                <span class="text-2xl" role="img" aria-label="strawberry">🍓</span>
                Acara Plate
            </a>
        </nav>
    </header>

    {{-- Main Card --}}
    <main class="relative z-10 w-full max-w-md space-y-6 rounded-3xl bg-white p-6 shadow-xl shadow-emerald-500/10 dark:bg-slate-800 dark:shadow-emerald-900/20">

        {{-- Header Section --}}
        <div class="text-center speakable-intro">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-2xl dark:bg-emerald-900/50">⚡️</div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Glucose Spike Calculator: Will It Spike?</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Type in a food to check its impact.</p>
        </div>

        {{-- Input Section --}}
        <form wire:submit="predict" class="relative">
            <input 
                type="text" 
                wire:model.live.debounce.150ms="food"
                placeholder="e.g. 2 slices of pepperoni pizza" 
                class="w-full rounded-xl border-2 border-slate-200 bg-slate-50 px-4 py-4 pr-14 text-lg font-medium outline-none transition-colors focus:border-emerald-500 focus:bg-white dark:border-slate-700 dark:bg-slate-900 dark:focus:border-emerald-500 dark:focus:bg-slate-800"
                @disabled($loading)
            >
            <button 
                type="submit"
                class="absolute right-2 top-2 rounded-lg bg-emerald-600 p-2.5 text-white transition-all hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                @disabled($loading || empty(trim($food)))
            >
                <span wire:loading.remove wire:target="predict">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </span>
                <span wire:loading wire:target="predict">
                    <svg class="h-6 w-6 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </span>
            </button>

            @error('food')
                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </form>

        {{-- Error Message --}}
        @if ($error)
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-900/20 dark:text-red-400">
                <p>{{ $error }}</p>
            </div>
        @endif

        {{-- Results Section --}}
        @if ($result)
            @php $riskLevel = $this->getRiskLevel(); @endphp
            <div class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-800">
                
                {{-- Spike Gauge Section --}}
                <div class="bg-slate-50 p-6 text-center dark:bg-slate-800/50">
                    <div class="mb-2 text-xs font-bold uppercase tracking-wider text-slate-400">Here's what we found</div>
                    
                    {{-- Gauge Bar --}}
                    <div class="relative mb-4 h-4 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                        <div class="absolute inset-0 flex">
                            <div class="h-full w-1/3 bg-emerald-400"></div>
                            <div class="h-full w-1/3 bg-amber-400"></div>
                            <div class="h-full w-1/3 bg-red-400"></div>
                        </div>
                        <div 
                            class="absolute top-1/2 h-6 w-1 -translate-y-1/2 rounded-full bg-slate-900 shadow-lg transition-all duration-500 dark:bg-white"
                            style="left: {{ $riskLevel->gaugePercentage() }}%"
                        ></div>
                    </div>

                    {{-- Risk Level --}}
                    <div class="flex items-end justify-center gap-2">
                        <span class="text-5xl font-black {{ $riskLevel->colorClass() }}">
                            {{ $riskLevel->label() }}
                        </span>
                        <span class="mb-1 text-lg font-medium text-slate-400">risk</span>
                    </div>
                </div>

                {{-- Details Section --}}
                <div class="space-y-4 p-6">
                    {{-- Explanation --}}
                    <div class="flex items-start gap-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm dark:bg-slate-700">💡</span>
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Here is why</h3>
                            <p class="text-sm text-slate-600 dark:text-slate-300">{{ $result['explanation'] }}</p>
                        </div>
                    </div>

                    {{-- Smart Fix --}}
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/50 dark:bg-emerald-900/20">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">✨</span>
                            <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Try this instead</span>
                            <span class="rounded-full bg-emerald-200 px-2 py-0.5 text-xs font-bold text-emerald-800 dark:bg-emerald-800 dark:text-emerald-200">about {{ $result['spikeReductionPercentage'] }}% lower</span>
                        </div>
                        <p class="mt-2 text-sm font-medium text-emerald-900 dark:text-emerald-100">{{ $result['smartFix'] }}</p>
                    </div>
                    
                    {{-- CTA Button --}}
                    <a 
                        href="{{ route('register') }}"
                        class="block w-full rounded-xl bg-slate-900 py-3 text-center text-sm font-bold text-white transition-transform hover:scale-[1.02] dark:bg-white dark:text-slate-900"
                    >
                        Build your meal plan →
                    </a>
                </div>
            </div>
        @endif

        {{-- Empty State / Suggestions --}}
        @if (!$result && !$loading && !$error)
            <div class="text-center text-sm text-slate-500 dark:text-slate-400">
                <p class="mb-3">Not sure what to check? Pick one:</p>
                <div class="flex flex-wrap justify-center gap-2">
                    <button 
                        type="button"
                        wire:click="setExample('White rice with chicken')"
                        class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600"
                    >
                        🍚 White rice with chicken
                    </button>
                    <button 
                        type="button"
                        wire:click="setExample('Overnight oats with berries')"
                        class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600"
                    >
                        🫐 Overnight oats with berries
                    </button>
                    <button 
                        type="button"
                        wire:click="setExample('Chocolate chip cookie')"
                        class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600"
                    >
                        🍪 Chocolate chip cookie
                    </button>
                    <button 
                        type="button"
                        wire:click="setExample('Grilled salmon with quinoa')"
                        class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600"
                    >
                        🐟 Grilled salmon with quinoa
                    </button>
                </div>
            </div>
        @endif

        {{-- Loading State --}}
        <div wire:loading wire:target="predict" class="text-center">
            <div class="inline-flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Looking that up for you...
            </div>
        </div>

        <p class="text-center text-xs text-slate-400 dark:text-slate-500">
            <strong>Disclaimer:</strong> These are AI estimates. Everyone's body is different.
        </p>

    </main>

    {{-- How it Works Section --}}
    <section class="relative z-10 mt-8 w-full max-w-md">
        <h2 class="mb-4 text-center text-lg font-bold text-slate-900 dark:text-white">
            How We Check for Spikes
        </h2>
        <div class="grid gap-4 text-sm text-slate-600 dark:text-slate-400">
            <div class="rounded-xl bg-white/50 p-4 backdrop-blur-sm dark:bg-slate-800/50">
                <p class="speakable-how-it-works">We look at the carbs, fiber, protein, and fat in the food you enter. This helps us guess how fast your body will digest it and if it might cause a sugar spike. Then we suggest better options to keep your energy steady.</p>
            </div>
        </div>
    </section>

    {{-- FAQ Section --}}
    <section class="relative z-10 mt-8 w-full max-w-md" aria-labelledby="faq-heading">
        <h2 id="faq-heading" class="mb-4 text-center text-lg font-bold text-slate-900 dark:text-white">
            Common Questions
        </h2>
        
        <div class="space-y-3" x-data="{ openFaq: null }">
            {{-- FAQ 1 --}}
            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-slate-800">
                <button 
                    type="button"
                    @click="openFaq = openFaq === 1 ? null : 1"
                    class="flex w-full items-center justify-between p-4 text-left font-medium text-slate-900 transition-colors hover:bg-slate-50 dark:text-white dark:hover:bg-slate-700/50"
                    aria-expanded="false"
                >
                    <span class="speakable-intro">What is a glucose spike and why should I care?</span>
                    <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform" :class="{ 'rotate-180': openFaq === 1 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="openFaq === 1" x-collapse class="border-t border-slate-100 px-4 pb-4 pt-2 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
                    <p class="speakable-intro">A spike happens when your blood sugar goes up fast after you eat. You might feel tired or hungry afterwards. Keeping your levels steady helps you feel better and stay healthy.</p>
                </div>
            </div>

            {{-- FAQ 2 --}}
            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-slate-800">
                <button 
                    type="button"
                    @click="openFaq = openFaq === 2 ? null : 2"
                    class="flex w-full items-center justify-between p-4 text-left font-medium text-slate-900 transition-colors hover:bg-slate-50 dark:text-white dark:hover:bg-slate-700/50"
                    aria-expanded="false"
                >
                    <span class="speakable-how-it-works">How does the glucose spike checker work?</span>
                    <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform" :class="{ 'rotate-180': openFaq === 2 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="openFaq === 2" x-collapse class="border-t border-slate-100 px-4 pb-4 pt-2 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
                    <p class="speakable-how-it-works">We look at the carbs, fiber, protein, and fat in the food. Then we give you a risk level: Low, Medium, or High. We also suggest foods that might be better for your blood sugar.</p>
                </div>
            </div>

            {{-- FAQ 3 --}}
            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-slate-800">
                <button 
                    type="button"
                    @click="openFaq = openFaq === 3 ? null : 3"
                    class="flex w-full items-center justify-between p-4 text-left font-medium text-slate-900 transition-colors hover:bg-slate-50 dark:text-white dark:hover:bg-slate-700/50"
                    aria-expanded="false"
                >
                    <span>What foods typically cause high blood sugar spikes?</span>
                    <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform" :class="{ 'rotate-180': openFaq === 3 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="openFaq === 3" x-collapse class="border-t border-slate-100 px-4 pb-4 pt-2 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
                    <p>White bread, white rice, and sugary drinks often cause spikes. Candy and pastries do too. These foods have lots of carbs but not much fiber. Your body digests them fast, which sends sugar into your blood quickly.</p>
                </div>
            </div>

            {{-- FAQ 4 --}}
            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-slate-800">
                <button 
                    type="button"
                    @click="openFaq = openFaq === 4 ? null : 4"
                    class="flex w-full items-center justify-between p-4 text-left font-medium text-slate-900 transition-colors hover:bg-slate-50 dark:text-white dark:hover:bg-slate-700/50"
                    aria-expanded="false"
                >
                    <span>How can I reduce the glycemic impact of my meals?</span>
                    <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform" :class="{ 'rotate-180': openFaq === 4 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="openFaq === 4" x-collapse class="border-t border-slate-100 px-4 pb-4 pt-2 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
                    <p>Add protein or healthy fats to your meal. Choose whole grains instead of white ones. Eat vegetables first. A short walk after eating also helps.</p>
                </div>
            </div>

            {{-- FAQ 5 --}}
            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-slate-800">
                <button 
                    type="button"
                    @click="openFaq = openFaq === 5 ? null : 5"
                    class="flex w-full items-center justify-between p-4 text-left font-medium text-slate-900 transition-colors hover:bg-slate-50 dark:text-white dark:hover:bg-slate-700/50"
                    aria-expanded="false"
                >
                    <span>Is this tool a replacement for medical advice?</span>
                    <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform" :class="{ 'rotate-180': openFaq === 5 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="openFaq === 5" x-collapse class="border-t border-slate-100 px-4 pb-4 pt-2 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
                    <p>No. This tool gives you estimates. It does not replace your doctor. Talk to a medical professional if you have health questions.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- More Free Tools --}}
    <section class="relative z-10 mt-8 w-full max-w-md">
        <h2 class="mb-4 text-center text-lg font-bold text-slate-900 dark:text-white">
            More Free Tools
        </h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <a href="{{ route('snap-to-track') }}" class="group flex flex-col items-center rounded-xl bg-white p-4 text-center shadow-sm transition-all hover:shadow-md dark:bg-slate-800">
                <span class="mb-2 text-2xl">📸</span>
                <h3 class="font-bold text-slate-900 dark:text-white">Snap to Track</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Get instant macro breakdown from food photos</p>
            </a>
            <a href="{{ route('diabetes-log-book-info') }}" class="group flex flex-col items-center rounded-xl bg-white p-4 text-center shadow-sm transition-all hover:shadow-md dark:bg-slate-800">
                <span class="mb-2 text-2xl">📖</span>
                <h3 class="font-bold text-slate-900 dark:text-white">Diabetes Log Book</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Free printable log book to track your levels</p>
            </a>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="relative z-10 mt-6 text-center text-xs text-slate-500 dark:text-slate-400">
        <p>
            <strong>Disclaimer:</strong> AI estimates are for guidance only, not medical advice. Got a health question? Talk to your doctor.
        </p>
        <p class="mt-2">
            <a href="{{ route('home') }}" class="underline hover:text-emerald-600">Back to Home</a>
            ·
            <a href="{{ route('register') }}" class="underline hover:text-emerald-600">Create Free Account</a>
        </p>
    </footer>
</div>
