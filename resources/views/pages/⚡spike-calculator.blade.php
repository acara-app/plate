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

@push('turnstile')
    @if (App::environment(['production', 'testing']))
        <x-turnstile.scripts />
    @endif
@endpush

<div class="min-h-screen bg-[#F2EBDD] text-[#1A1814]">
    <x-tools-header theme="cream" />

    <div class="px-4 py-8 md:py-12">
        {{-- Editorial breadcrumbs --}}
        <nav aria-label="Breadcrumb" class="mx-auto flex max-w-7xl items-center gap-2 font-mono text-[11px] uppercase tracking-[0.14em] text-[#6E665C] lg:px-8">
            <a href="/" aria-label="Home" class="inline-flex items-center transition hover:text-[#1A1814]">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="size-3.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
            </a>
            <span aria-hidden="true">›</span>
            <a href="{{ route('tools.index') }}" class="transition hover:text-[#1A1814]">Tools</a>
            <span aria-hidden="true">›</span>
            <span aria-current="page" class="text-[#1A1814]">Spike Checker</span>
        </nav>

        {{-- Hero --}}
        <header class="mx-auto mt-8 max-w-7xl lg:px-8">
            <div class="inline-flex items-center gap-2 border border-[#D9CFBC] bg-[#EBE2D0] px-3 py-1.5">
                <span class="size-1.5 rounded-full bg-[#C4623A] shadow-[0_0_0_3px_rgba(196,98,58,0.18)]" aria-hidden="true"></span>
                <span class="font-mono text-[10px] uppercase tracking-[0.18em] text-[#3D3833]">Free AI-powered glucose spike checker</span>
            </div>

            <h1 class="mt-6 max-w-4xl text-balance font-bold text-[clamp(40px,5vw,68px)] leading-[1.02] tracking-[-0.02em] text-[#1A1814]">
                Which Foods Spike Your Blood Sugar?
            </h1>
            <p class="mt-5 max-w-2xl text-base leading-relaxed text-[#3D3833] sm:text-lg">
                Type any food—a sandwich, a smoothie, a bowl of pho. We'll estimate the glucose hit in seconds and hand back a swap that's gentler on your blood sugar.
            </p>
        </header>

        {{-- Form + Result --}}
        <main class="mx-auto mt-10 grid max-w-7xl gap-6 lg:grid-cols-[0.92fr_1.08fr] lg:px-8">
            {{-- Form panel --}}
            <section class="border border-[#D9CFBC] bg-[#EBE2D0] p-6 sm:p-8">
                <div class="flex items-center justify-between gap-4 pb-5">
                    <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">Spike check</p>
                    <span class="inline-flex items-center gap-2 font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">
                        <span class="size-1.5 rounded-full bg-emerald-600" aria-hidden="true"></span>
                        Live
                    </span>
                </div>

                <form wire:submit="predict" class="space-y-7">
                    {{-- Food field --}}
                    <div class="space-y-3 border-t border-[#D9CFBC] pt-6">
                        <div class="flex items-baseline justify-between gap-3">
                            <label for="food" class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">
                                Food or meal
                            </label>
                            @error('food')
                                <span class="font-mono text-[10px] uppercase tracking-[0.12em] text-[#B5482E]">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="relative">
                            <input
                                id="food"
                                type="text"
                                wire:model.live.debounce.150ms="food"
                                placeholder="e.g. white rice, chocolate cake, or grilled salmon"
                                class="h-12 w-full rounded-none border border-[#D9CFBC] bg-[#F2EBDD] px-4 text-base text-[#1A1814] outline-none transition placeholder:text-[#6E665C] focus:border-[#1A1814] focus:ring-2 focus:ring-[#1A1814]/15"
                                @disabled($loading)
                            >
                        </div>
                    </div>

                    {{-- Examples --}}
                    @if (!$result && !$loading && !$error)
                        <div class="space-y-3 border-t border-[#D9CFBC] pt-6">
                            <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">Not sure what to check? Pick one:</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach (['White rice with chicken', 'Overnight oats with berries', 'Chocolate chip cookie', 'Grilled salmon with quinoa'] as $example)
                                    <button
                                        type="button"
                                        wire:click="setExample('{{ $example }}')"
                                        class="rounded-none border px-3.5 py-2 text-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1A1814]/30 {{ trim($food) === $example ? 'border-[#1A1814] bg-[#1A1814] text-[#F2EBDD]' : 'border-[#D9CFBC] bg-[#F2EBDD] text-[#3D3833] hover:border-[#1A1814]/40' }}"
                                    >
                                        {{ $example }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if (App::environment(['production', 'testing']))
                        <div class="border-t border-[#D9CFBC] pt-6">
                            <x-turnstile wire:model="turnstileToken" data-theme="auto" />
                        </div>
                    @endif

                    <button
                        type="submit"
                        data-umami-event="spike_calculator_submit"
                        data-umami-event-location="main_form"
                        class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-none bg-[#1A1814] px-6 text-base font-semibold text-[#F2EBDD] transition hover:bg-[#3D3833] focus:outline-none focus:ring-2 focus:ring-[#1A1814] focus:ring-offset-2 focus:ring-offset-[#EBE2D0] disabled:cursor-not-allowed disabled:opacity-50"
                        @disabled($loading || trim($food) === '')
                    >
                        <span wire:loading.remove wire:target="predict" class="inline-flex items-center gap-2">
                            Check this food
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </span>
                        <span wire:loading wire:target="predict" class="inline-flex items-center gap-2">
                            <svg class="size-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Looking that up…
                        </span>
                    </button>

                    <p class="font-mono text-[10px] uppercase tracking-[0.18em] text-[#6E665C]">
                        Estimates only · Not medical advice
                    </p>
                </form>
            </section>

            {{-- Result panel --}}
            <section aria-live="polite" aria-label="Spike check result" class="flex flex-col">
                @if ($error)
                    <div class="border border-[#B5482E]/40 bg-[#B5482E]/5 p-6 sm:p-8">
                        <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#B5482E]">Hit a snag</p>
                        <p class="mt-3 text-sm leading-relaxed text-[#3D3833]">{{ $error }}</p>
                    </div>
                @elseif ($loading)
                    <div class="flex flex-col gap-4">
                        <div class="border border-[#D9CFBC] bg-[#F2EBDD] p-6">
                            <div class="h-3 w-28 animate-pulse bg-[#D9CFBC]"></div>
                            <div class="mt-6 h-12 w-3/4 animate-pulse bg-[#D9CFBC]"></div>
                            <div class="mt-3 h-3 w-full animate-pulse bg-[#EBE2D0]"></div>
                            <div class="mt-2 h-3 w-2/3 animate-pulse bg-[#EBE2D0]"></div>
                        </div>
                        <div class="h-28 animate-pulse border border-[#D9CFBC] bg-[#F2EBDD]"></div>
                        <div class="h-44 animate-pulse border border-[#D9CFBC] bg-[#F2EBDD]"></div>
                    </div>
                @elseif ($result)
                    @php
                        $riskLevel = $this->getRiskLevel();
                        $riskColor = match ($riskLevel) {
                            \App\Enums\SpikeRiskLevel::Low => '#1F7A52',
                            \App\Enums\SpikeRiskLevel::Medium => '#C28A2C',
                            \App\Enums\SpikeRiskLevel::High => '#B5482E',
                            default => '#1A1814',
                        };
                    @endphp
                    <div
                        x-data
                        x-init="window.acaraTrack?.('spike_calculator_result_viewed', { risk_level: @js($result['riskLevel']) })"
                        class="flex flex-col gap-4 motion-safe:animate-in motion-safe:fade-in-0 motion-safe:slide-in-from-bottom-2 motion-safe:duration-200"
                    >
                        {{-- Headline result --}}
                        <article class="border border-[#D9CFBC] bg-[#F2EBDD] p-6 sm:p-8">
                            <div class="flex items-baseline justify-between gap-4">
                                <p class="truncate font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">
                                    Reading · &ldquo;{{ $result['food'] }}&rdquo;
                                </p>
                                <span class="shrink-0 font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">
                                    GL {{ $result['estimatedGlycemicLoad'] }}
                                </span>
                            </div>
                            <div class="mt-4 flex items-end gap-3">
                                <span class="font-bold text-[clamp(56px,7vw,96px)] leading-[1] tracking-[-0.03em]" style="color: {{ $riskColor }}">
                                    {{ $riskLevel->label() }}
                                </span>
                                <span class="mb-2 font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">
                                    spike risk
                                </span>
                            </div>

                            <div class="mt-7">
                                <div class="relative h-3" role="img" aria-label="Risk gauge: {{ $riskLevel->label() }}">
                                    <div class="flex h-full overflow-hidden">
                                        <div class="h-full w-1/3 bg-emerald-600"></div>
                                        <div class="h-full w-1/3 bg-amber-500"></div>
                                        <div class="h-full w-1/3 bg-red-600"></div>
                                    </div>
                                    <div
                                        class="absolute top-1/2 size-5 -translate-x-1/2 -translate-y-1/2 border-2 border-[#1A1814] bg-[#F2EBDD] transition-[left] duration-700"
                                        style="left: {{ $riskLevel->gaugePercentage() }}%"
                                    ></div>
                                </div>
                                <div class="mt-2 flex justify-between font-mono text-[10px] uppercase tracking-[0.18em] text-[#6E665C]">
                                    <span>Low</span>
                                    <span>Medium</span>
                                    <span>High</span>
                                </div>
                            </div>
                        </article>

                        {{-- Why --}}
                        <article class="border border-[#D9CFBC] bg-[#F2EBDD] p-6">
                            <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">Here is why</p>
                            <p class="mt-3 text-sm leading-relaxed text-[#3D3833]">{{ $result['explanation'] }}</p>
                        </article>

                        {{-- Smart fix --}}
                        <article class="border border-[#1A1814] bg-[#1A1814] p-6 text-[#F2EBDD]">
                            <div class="flex items-baseline justify-between gap-3">
                                <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#C4623A]">Try this instead</p>
                                <span class="shrink-0 border border-[#C4623A] px-2 py-0.5 font-mono text-[10px] uppercase tracking-[0.16em] text-[#C4623A]">
                                    about {{ $result['spikeReductionPercentage'] }}% lower
                                </span>
                            </div>
                            <p class="mt-3 text-base leading-relaxed">{{ $result['smartFix'] }}</p>
                        </article>

                        @auth
                            <button
                                type="button"
                                x-on:click="document.getElementById('food')?.focus()"
                                class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-none border border-[#1A1814] bg-[#F2EBDD] px-6 text-sm font-semibold text-[#1A1814] transition hover:bg-[#1A1814] hover:text-[#F2EBDD]"
                            >
                                Run another check
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                            </button>
                        @else
                            <article class="border border-[#D9CFBC] bg-[#EBE2D0] p-6 sm:p-8">
                                <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">Next move</p>
                                <h3 class="mt-3 font-bold text-2xl leading-tight tracking-[-0.02em] text-[#1A1814]">
                                    Turn this spike check into advice Acara remembers.
                                </h3>
                                <p class="mt-3 text-sm leading-relaxed text-[#3D3833]">
                                    Save your goals, food preferences, and low-spike swaps so future meal ideas build on what you have already checked.
                                </p>
                                <a
                                    href="{{ route('register') }}"
                                    data-umami-event="signup_cta_click"
                                    data-umami-event-location="spike_calculator_result"
                                    class="mt-5 inline-flex h-12 w-full items-center justify-center gap-2 rounded-none bg-[#C4623A] px-6 text-base font-semibold text-[#F2EBDD] transition hover:bg-[#A04A28]"
                                >
                                    Get my free meal plan
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                    </svg>
                                </a>
                                <p class="mt-3 text-center font-mono text-[10px] uppercase tracking-[0.16em] text-[#6E665C]">
                                    Free · No credit card · Takes 30 seconds
                                </p>
                            </article>
                        @endauth
                    </div>
                @else
                    <div class="flex h-full min-h-[420px] items-center justify-center border-2 border-dashed border-[#D9CFBC] p-10 text-center">
                        <div class="max-w-sm">
                            <span class="mx-auto block size-2 rounded-full bg-[#C4623A]" aria-hidden="true"></span>
                            <h2 class="mt-5 font-bold text-2xl leading-tight tracking-[-0.02em] text-[#1A1814]">
                                Your reading shows up here.
                            </h2>
                            <p class="mt-3 text-sm leading-relaxed text-[#3D3833]">
                                Drop a food on the left and we'll plot it on the Low–Medium–High scale, then suggest a swap that lands lower.
                            </p>
                        </div>
                    </div>
                @endif
            </section>
        </main>

        {{-- Method --}}
        <section class="mx-auto mt-24 max-w-7xl lg:px-8" aria-labelledby="how-it-works-heading">
            <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">How the checker works</p>
            <h2 id="how-it-works-heading" class="mt-4 max-w-3xl font-bold text-[clamp(28px,3.4vw,44px)] leading-[1.05] tracking-[-0.02em] text-[#1A1814]">
                Three steps from food to forecast.
            </h2>
            <p class="mt-5 max-w-3xl text-base leading-relaxed text-[#3D3833]">
                We pair USDA carb, fiber, and protein data with diabetic safety guidelines to estimate how a food is likely to move blood sugar—then suggest a swap that's easier on it.
            </p>

            <div class="mt-10 grid border-t border-[#D9CFBC] sm:grid-cols-3 sm:divide-x sm:divide-[#D9CFBC]">
                <article class="border-b border-[#D9CFBC] px-2 pt-8 pb-10 sm:border-b-0 sm:px-7">
                    <div class="font-bold text-5xl italic leading-none text-[#C4623A]">A</div>
                    <h3 class="mt-4 font-bold text-xl leading-tight tracking-[-0.01em] text-[#1A1814]">You name the food</h3>
                    <p class="speakable-how-it-works mt-3 text-sm leading-relaxed text-[#3D3833]">
                        "Two slices of pizza," "a banana with peanut butter"—plain English works. Our free tool parses portion and ingredients, then weighs carbs, fiber, protein, and fat to predict how quickly your body will digest it.
                    </p>
                </article>
                <article class="border-b border-[#D9CFBC] px-2 pt-8 pb-10 sm:border-b-0 sm:px-7">
                    <div class="font-bold text-5xl italic leading-none text-[#C4623A]">B</div>
                    <h3 class="mt-4 font-bold text-xl leading-tight tracking-[-0.01em] text-[#1A1814]">We model the spike</h3>
                    <p class="speakable-how-it-works mt-3 text-sm leading-relaxed text-[#3D3833]">
                        Carbs, fiber, fat, and protein each pull glucose differently. We weight them against USDA nutrition data and diabetic safety guidelines to land on a Low, Medium, or High glycemic risk.
                    </p>
                </article>
                <article class="px-2 pt-8 pb-10 sm:px-7">
                    <div class="font-bold text-5xl italic leading-none text-[#C4623A]">C</div>
                    <h3 class="mt-4 font-bold text-xl leading-tight tracking-[-0.01em] text-[#1A1814]">You get a smart swap</h3>
                    <p class="speakable-how-it-works mt-3 text-sm leading-relaxed text-[#3D3833]">
                        A diabetes-friendly alternative—plus an estimate of how much gentler it is on your blood sugar. Useful for Type 2 diabetes meal planning and pre-diabetes management.
                    </p>
                </article>
            </div>

            <aside class="mt-12 grid gap-8 border-t border-[#D9CFBC] pt-10 sm:grid-cols-[2fr_3fr]">
                <div>
                    <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">Field guide, not a meter</p>
                </div>
                <p class="text-sm leading-relaxed text-[#3D3833]">
                    <strong class="font-bold text-[#1A1814]">These are AI estimates.</strong> Real responses vary with portion size, what you ate before, sleep, and stress. Use this to plan—not to replace your CGM or your clinician.
                </p>
            </aside>
        </section>

        {{-- FAQ --}}
        <section class="mx-auto mt-24 max-w-7xl lg:px-8" aria-labelledby="faq-heading">
            <div class="grid gap-12 sm:grid-cols-[1fr_2fr]">
                <div>
                    <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">FAQ</p>
                    <h2 id="faq-heading" class="mt-4 font-bold text-[clamp(28px,3.4vw,44px)] leading-[1.05] tracking-[-0.02em] text-[#1A1814]">
                        Frequently Asked Questions
                    </h2>
                    <p class="mt-3 text-sm leading-relaxed text-[#3D3833]">
                        Quick context for using the checker as a planning aid—not a replacement for your meter or clinician.
                    </p>
                </div>

                <div x-data="{ openFaq: 1 }">
                    @php
                    $faqs = [
                        ['q' => 'What is a glucose spike and why does it matter for Type 2 diabetes?', 'a' => 'A glucose spike occurs when blood sugar rises rapidly after eating high-carbohydrate foods. For people with pre-diabetes or Type 2 diabetes, frequent spikes can lead to long-term health complications. This tool helps you predict which foods trigger spikes and find safer alternatives.', 'speakable' => 'speakable-intro'],
                        ['q' => 'How accurate is this blood sugar spike checker?', 'a' => 'Our AI tool provides estimates based on USDA nutrition data, glycemic index research, and diabetic safety guidelines. While individual responses vary based on metabolism, portion size, and food combinations, our checker offers reliable guidance for meal planning. Always verify with your doctor.', 'speakable' => 'speakable-how-it-works'],
                        ['q' => 'What foods cause the highest blood sugar spikes?', 'a' => 'High-glycemic foods include white rice, white bread, pastries, sugar-sweetened beverages, candy, and fruit juices. These refined carbohydrates digest quickly, causing fast blood sugar elevation. Whole grains, legumes, non-starchy vegetables, and lean proteins generally have lower impact.'],
                        ['q' => 'How can I reduce meal glycemic impact naturally?', 'a' => 'Pair carbohydrates with protein, healthy fats, or fiber-rich vegetables to slow sugar absorption. Choose whole grains over refined options, eat smaller portions, and take a 10-15 minute walk after meals to improve insulin sensitivity. Our tool suggests specific swaps to maximize these benefits.'],
                        ['q' => 'Can I use this tool for pre-diabetes or Type 2 diabetes management?', 'a' => 'Yes! This tool is designed for pre-diabetes and Type 2 diabetes meal planning. However, it provides educational estimates only and is not a substitute for professional medical advice or glucose monitoring. Always consult your healthcare provider for personalized guidance.'],
                        ['q' => 'Will Acara remember foods I check here?', 'a' => 'The free public checker gives an instant estimate without saving long-term context. With an Acara Cloud account, stable food preferences, goals, and low-spike swaps can be saved so future chats start with more useful context.'],
                    ];
                    @endphp

                    @foreach ($faqs as $index => $faq)
                        @php $position = $index + 1; @endphp
                        <div class="border-t {{ $loop->first ? 'border-[#1A1814]' : 'border-[#D9CFBC]' }} {{ $loop->last ? 'border-b border-[#D9CFBC]' : '' }}">
                            <button
                                type="button"
                                @click="openFaq = openFaq === {{ $position }} ? null : {{ $position }}"
                                :aria-expanded="openFaq === {{ $position }} ? 'true' : 'false'"
                                class="flex w-full items-baseline justify-between gap-4 py-5 text-left transition hover:text-[#1A1814]"
                            >
                                <div class="flex items-baseline gap-4">
                                    <span class="font-mono text-[11px] tracking-[0.14em] text-[#6E665C]" aria-hidden="true">
                                        {{ str_pad((string) $position, 2, '0', STR_PAD_LEFT) }}
                                    </span>
                                    <span class="font-bold text-lg leading-tight tracking-[-0.01em] text-[#1A1814] sm:text-xl {{ $faq['speakable'] ?? '' }}">
                                        {{ $faq['q'] }}
                                    </span>
                                </div>
                                <svg
                                    class="mt-1 size-5 shrink-0 text-[#C4623A] transition-transform duration-200"
                                    :class="{ 'rotate-45': openFaq === {{ $position }} }"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    aria-hidden="true"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                            </button>
                            <div
                                x-show="openFaq === {{ $position }}"
                                x-collapse
                                class="overflow-hidden"
                            >
                                <p class="mb-6 max-w-prose pl-10 text-sm leading-relaxed text-[#3D3833] {{ $faq['speakable'] ?? '' }}">
                                    {{ $faq['a'] }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Altani CTA --}}
        <section class="mx-auto mt-24 max-w-7xl lg:px-8">
            <div class="flex flex-col items-center gap-8 border border-[#D9CFBC] bg-[#EBE2D0] p-8 text-center sm:flex-row sm:items-center sm:gap-10 sm:p-12 sm:text-left">
                <div class="shrink-0">
                    <img
                        src="https://pub-plate-assets.acara.app/images/altani_with_hand_on_chin_considering_expression_thought-1024.webp"
                        alt="Altani, your personal AI health coach"
                        loading="lazy"
                        class="h-28 w-28 border border-[#D9CFBC] object-cover sm:h-32 sm:w-32"
                    >
                </div>
                <div class="flex-1">
                    <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">Meet Altani</p>
                    <h3 class="mt-3 font-bold text-2xl leading-tight tracking-[-0.02em] text-[#1A1814] sm:text-3xl">
                        Spike checks are just the start.
                    </h3>
                    <p class="mt-3 max-w-xl text-sm leading-relaxed text-[#3D3833] sm:text-base">
                        Acara can remember your low-spike swaps, food preferences, and goals so each check becomes part of your bigger nutrition plan.
                    </p>
                    <div class="mt-6">
                        <a
                            href="/register"
                            class="inline-flex items-center gap-2 rounded-none border border-[#1A1814] px-6 py-3 font-mono text-[11px] uppercase tracking-[0.16em] text-[#1A1814] transition hover:bg-[#1A1814] hover:text-[#F2EBDD]"
                        >
                            Ask Acara What To Eat Next →
                        </a>
                    </div>
                </div>
            </div>
        </section>

        {{-- More tools strip --}}
        <section class="mx-auto mt-16 max-w-7xl lg:px-8">
            <a
                href="{{ route('tools.index') }}"
                class="group flex flex-col gap-4 border-t border-[#1A1814] pt-8 sm:flex-row sm:items-baseline sm:justify-between"
            >
                <div>
                    <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-[#6E665C]">More free tools</p>
                    <h2 class="mt-3 font-bold text-2xl leading-tight tracking-[-0.01em] text-[#1A1814] transition-colors group-hover:text-[#C4623A]">
                        Explore the full toolkit →
                    </h2>
                </div>
                <span class="font-mono text-[11px] uppercase tracking-[0.16em] text-[#6E665C]">
                    Calculators · Trackers · Planners
                </span>
            </a>
        </section>
    </div>

    <x-footer class="bg-[#F2EBDD]! border-[#D9CFBC]!" />
</div>
