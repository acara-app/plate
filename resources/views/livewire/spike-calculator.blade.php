@section('title', 'Free Glucose Spike Checker - Will This Food Raise My Blood Sugar? | Acara Plate')
@section('meta_description', 'Type what you plan to eat and get a quick estimate of how likely it is to raise your blood sugar, plus a simple swap you can try. Free to use. No account needed.')
@section('meta_keywords', 'glucose spike checker, blood sugar spike, glycemic index, food blood sugar impact, diabetes food checker, will food spike blood sugar, free glucose tool, blood sugar calculator')

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
        <div class="text-center">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-2xl dark:bg-emerald-900/50">⚡️</div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Will It Spike?</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Check any food in seconds</p>
        </div>

        {{-- Input Section --}}
        <form wire:submit="predict" class="relative">
            <input 
                type="text" 
                wire:model="food"
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

    </main>

    {{-- Footer --}}
    <footer class="relative z-10 mt-6 text-center text-xs text-slate-500 dark:text-slate-400">
        <p>
            This gives you an estimate, not medical advice. Got a health question? Talk to your doctor.
        </p>
        <p class="mt-2">
            <a href="{{ route('home') }}" class="underline hover:text-emerald-600">Back to Home</a>
            ·
            <a href="{{ route('register') }}" class="underline hover:text-emerald-600">Create Free Account</a>
        </p>
    </footer>
</div>
