<?php

declare(strict_types=1);

use App\Actions\PredictGlucoseSpikeAction;
use App\Enums\SpikeRiskLevel;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use RyanChandler\LaravelCloudflareTurnstile\Rules\Turnstile;

new
#[Layout('layouts.mini-app', ['metaDescription' => 'Free AI blood sugar spike checker: Enter any food to instantly predict its glucose impact. Get glycemic risk levels, smart swaps, and diabetes-friendly food alternatives—perfect for Type 2 diabetes meal planning.', 'metaKeywords' => 'blood sugar spike checker, glucose spike calculator, will this food spike my blood sugar, food glycemic impact, diabetes food analyzer, pre-diabetes food checker, glucose predictor, food insulin impact, type 2 diabetes food checker, carb spike risk'])]
#[Title('Blood Sugar Spike Checker | Free AI Glucose Impact Calculator')]
class extends Component
{
    public string $food = '';

    public ?string $compare = null;

    public ?string $turnstileToken = null;

    public bool $loading = false;

    /** @var array{food: string, riskLevel: string, estimatedGlycemicLoad: int, explanation: string, smartFix: string, spikeReductionPercentage: int}|null */
    public ?array $result = null;

    public ?string $error = null;

    public function mount(): void
    {
        // If compare param is set, use it for the food input
        if ($this->compare && ($this->food === '' || $this->food === '0')) {
            $this->food = $this->compare;
        }
    }

    public function predict(PredictGlucoseSpikeAction $action): void
    {
        $this->error = null;
        $this->result = null;

        $rules = [
            'food' => 'required|string|min:2|max:500',
        ];

        if (app()->environment(['production', 'testing'])) {
            $rules['turnstileToken'] = ['required', new Turnstile];
        }

        $this->validate($rules);

        $this->loading = true;

        try {
            $prediction = $action->handle($this->food);
            $this->result = [
                'food' => $prediction->food,
                'riskLevel' => $prediction->riskLevel->value,
                'estimatedGlycemicLoad' => $prediction->estimatedGlycemicLoad,
                'explanation' => $prediction->explanation,
                'smartFix' => $prediction->smartFix,
                'spikeReductionPercentage' => $prediction->spikeReductionPercentage,
            ];
        } catch (Throwable $e) {
            $this->error = 'Something went wrong. Please try again.';
            report($e);
        } finally {
            $this->loading = false;
        }
    }

    public function setExample(string $example): void
    {
        $this->food = $example;
    }

    public function getRiskLevel(): ?SpikeRiskLevel
    {
        if ($this->result === null) {
            return null;
        }

        return SpikeRiskLevel::from($this->result['riskLevel']);
    }
};
?>

<x-slot:jsonLd>
    <x-json-ld.spike-calculator />
</x-slot:jsonLd>

<div
    class="relative flex min-h-screen flex-col items-center overflow-hidden bg-linear-to-br from-slate-50 via-white to-blue-50 p-4 text-slate-900 lg:justify-center lg:p-8 dark:from-slate-950 dark:via-slate-900 dark:to-blue-950 dark:text-slate-50"
>
    {{-- Animated background elements --}}
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 overflow-hidden">
        <div class="absolute -left-4 top-0 h-72 w-72 animate-pulse rounded-full bg-blue-300/20 blur-3xl dark:bg-blue-500/10"></div>
        <div class="absolute -right-4 bottom-0 h-96 w-96 animate-pulse rounded-full bg-sky-300/20 blur-3xl dark:bg-sky-500/10"></div>
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
    <main class="relative z-10 w-full max-w-md space-y-6 rounded-3xl bg-white p-6 shadow-xl shadow-blue-500/10 dark:bg-slate-800 dark:shadow-blue-900/20">

        {{-- Header Section --}}
        <div class="text-center speakable-intro">
            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-2xl dark:bg-blue-900/50">⚡️</div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">Which Foods Spike Your Blood Sugar? Find Out Instantly</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Free AI-powered glucose spike checker. Type any food to predict blood sugar impact and discover diabetes-friendly swaps.</p>
        </div>

        {{-- Input Section --}}
        <form wire:submit="predict" class="relative">
            <input
                type="text"
                wire:model.live.debounce.150ms="food"
                placeholder="e.g. white rice, chocolate cake, or grilled salmon"
                class="w-full min-h-14 rounded-xl border-2 border-slate-200 bg-slate-50 px-4 py-4 pr-14 text-lg font-medium outline-none transition-colors focus:border-blue-500 focus:bg-white focus:ring-2 focus:ring-blue-500/20 dark:border-slate-700 dark:bg-slate-900 dark:focus:border-blue-500 dark:focus:bg-slate-800"
                @disabled($loading)
                aria-label="Enter a food to check its blood sugar impact"
            >
            <button
                type="submit"
                class="absolute right-2 top-2 flex h-12 w-12 items-center justify-center rounded-lg bg-blue-600 text-white transition-all hover:bg-blue-700 hover:scale-105 active:scale-95 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:scale-100"
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

            @if (App::environment(['production', 'testing']))
                <div class="mt-4 flex justify-center">
                    <x-turnstile wire:model="turnstileToken" data-theme="auto" />
                </div>
            @endif

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
                    <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-900/50 dark:bg-blue-900/20">
                        <div class="flex items-center gap-2">
                            <span class="text-lg">✨</span>
                            <span class="text-xs font-bold uppercase tracking-wider text-blue-700 dark:text-blue-400">Try this instead</span>
                            <span class="rounded-full bg-blue-200 px-2 py-0.5 text-xs font-bold text-blue-800 dark:bg-blue-800 dark:text-blue-200">about {{ $result['spikeReductionPercentage'] }}% lower</span>
                        </div>
                        <p class="mt-2 text-sm font-medium text-blue-900 dark:text-blue-100">{{ $result['smartFix'] }}</p>
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
                        class="rounded-full bg-slate-100 px-3 py-2 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-200 active:scale-95 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600"
                    >
                        🍚 White rice with chicken
                    </button>
                    <button
                        type="button"
                        wire:click="setExample('Overnight oats with berries')"
                        class="rounded-full bg-slate-100 px-3 py-2 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-200 active:scale-95 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600"
                    >
                        🫐 Overnight oats with berries
                    </button>
                    <button
                        type="button"
                        wire:click="setExample('Chocolate chip cookie')"
                        class="rounded-full bg-slate-100 px-3 py-2 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-200 active:scale-95 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600"
                    >
                        🍪 Chocolate chip cookie
                    </button>
                    <button
                        type="button"
                        wire:click="setExample('Grilled salmon with quinoa')"
                        class="rounded-full bg-slate-100 px-3 py-2 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-200 active:scale-95 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600"
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
    <section class="relative z-10 mt-8 w-full max-w-md" aria-labelledby="how-it-works-heading">
        <h2 id="how-it-works-heading" class="mb-4 text-center text-lg font-bold text-slate-900 dark:text-white">
            How This Blood Sugar Spike Checker Works
        </h2>
        <div class="space-y-3 text-sm text-slate-600 dark:text-slate-400">
            <div class="rounded-xl bg-white/50 p-4 backdrop-blur-sm dark:bg-slate-800/50">
                <p class="speakable-how-it-works"><strong>AI-Powered Analysis:</strong> Our free tool analyzes carbohydrates, fiber, protein, and fat content in any food. It predicts how quickly your body digests it and determines the likelihood of a glucose spike.</p>
            </div>
            <div class="rounded-xl bg-white/50 p-4 backdrop-blur-sm dark:bg-slate-800/50">
                <p class="speakable-how-it-works"><strong>Instant Risk Levels:</strong> Get immediate Low, Medium, or High glycemic risk assessment based on USDA nutrition data and diabetic safety guidelines.</p>
            </div>
            <div class="rounded-xl bg-white/50 p-4 backdrop-blur-sm dark:bg-slate-800/50">
                <p class="speakable-how-it-works"><strong>Smart Food Swaps:</strong> Receive diabetes-friendly alternatives that significantly reduce blood sugar impact—perfect for Type 2 diabetes meal planning and pre-diabetes management.</p>
            </div>
        </div>
    </section>

    {{-- FAQ Section --}}
    <section class="relative z-10 mt-8 w-full max-w-md" aria-labelledby="faq-heading">
        <h2 id="faq-heading" class="mb-4 text-center text-lg font-bold text-slate-900 dark:text-white">
            Frequently Asked Questions
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
                    <span class="speakable-intro">What is a glucose spike and why does it matter for Type 2 diabetes?</span>
                    <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform" :class="{ 'rotate-180': openFaq === 1 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="openFaq === 1" x-collapse class="border-t border-slate-100 px-4 pb-4 pt-2 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
                    <p class="speakable-intro">A glucose spike occurs when blood sugar rises rapidly after eating high-carbohydrate foods. For people with pre-diabetes or Type 2 diabetes, frequent spikes can lead to long-term health complications. This tool helps you predict which foods trigger spikes and find safer alternatives.</p>
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
                    <span class="speakable-how-it-works">How accurate is this blood sugar spike checker?</span>
                    <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform" :class="{ 'rotate-180': openFaq === 2 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="openFaq === 2" x-collapse class="border-t border-slate-100 px-4 pb-4 pt-2 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
                    <p class="speakable-how-it-works">Our AI tool provides estimates based on USDA nutrition data, glycemic index research, and diabetic safety guidelines. While individual responses vary based on metabolism, portion size, and food combinations, our checker offers reliable guidance for meal planning. Always verify with your doctor.</p>
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
                    <span>What foods cause the highest blood sugar spikes?</span>
                    <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform" :class="{ 'rotate-180': openFaq === 3 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="openFaq === 3" x-collapse class="border-t border-slate-100 px-4 pb-4 pt-2 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
                    <p>High-glycemic foods include white rice, white bread, pastries, sugar-sweetened beverages, candy, and fruit juices. These refined carbohydrates digest quickly, causing fast blood sugar elevation. Whole grains, legumes, non-starchy vegetables, and lean proteins generally have lower impact.</p>
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
                    <span>How can I reduce meal glycemic impact naturally?</span>
                    <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform" :class="{ 'rotate-180': openFaq === 4 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="openFaq === 4" x-collapse class="border-t border-slate-100 px-4 pb-4 pt-2 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
                    <p>Pair carbohydrates with protein, healthy fats, or fiber-rich vegetables to slow sugar absorption. Choose whole grains over refined options, eat smaller portions, and take a 10-15 minute walk after meals to improve insulin sensitivity. Our tool suggests specific swaps to maximize these benefits.</p>
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
                    <span>Can I use this tool for pre-diabetes or Type 2 diabetes management?</span>
                    <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform" :class="{ 'rotate-180': openFaq === 5 }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="openFaq === 5" x-collapse class="border-t border-slate-100 px-4 pb-4 pt-2 text-sm text-slate-600 dark:border-slate-700 dark:text-slate-300">
                    <p>Yes! This tool is designed for pre-diabetes and Type 2 diabetes meal planning. However, it provides educational estimates only and is not a substitute for professional medical advice or glucose monitoring. Always consult your healthcare provider for personalized guidance.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- More Free Tools --}}
    <section class="relative z-10 mt-12 mb-8 w-full max-w-md">
        <h2 class="mb-4 text-center text-lg font-bold text-slate-900 dark:text-white">
            Explore More Free Tools
        </h2>
        <a href="{{ route('tools.index') }}" class="group flex flex-col items-center rounded-xl bg-white p-6 text-center shadow-sm transition-all hover:shadow-md dark:bg-slate-800">
            <span class="mb-2 text-3xl">🛠️</span>
            <h3 class="font-bold text-slate-900 dark:text-white">View All Free Tools</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Discover free health trackers, calculators, and nutrition tools</p>
        </a>
    </section>

    <x-footer />
</div>
