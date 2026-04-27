<?php

declare(strict_types=1);

use App\Actions\CalculateCaffeineSafeDose;
use App\Actions\LogToolEvent;
use App\Data\SafeDoseData;
use App\Models\CaffeineDrink;
use App\Utilities\WeightConverter;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('layouts.mini-app', ['metaDescription' => 'Free caffeine calculator: estimate your safe daily caffeine dose and find out when to stop drinking coffee for better sleep.', 'metaKeywords' => 'caffeine calculator, safe caffeine dose, caffeine sleep cutoff, coffee calculator, caffeine half life'])]
#[Title('Coffee Caffeine Calculator: How Much Is Too Much?')]
class extends Component
{
    public const float MIN_WEIGHT_KG = 30.0;

    public const float MAX_WEIGHT_KG = 250.0;

    public ?string $weight = null;

    #[Url(as: 'unit', except: 'kg')]
    public string $weightUnit = 'kg';

    public int $sensitivity = 3;

    public string $drinkQuery = '';

    public ?int $drinkId = null;

    public ?float $safeMg = null;

    public ?int $safeCups = null;

    public ?float $perCupMg = null;

    public bool $lacksCaffeineEstimate = false;

    public bool $optimiseForSleep = false;

    public ?string $bedtime = null;

    public function mount(): void
    {
        app(LogToolEvent::class)->handle('caffeine-calculator', 'page_view');
    }

    public function setUnit(string $unit): void
    {
        if (! in_array($unit, ['kg', 'lb'], true)) {
            return;
        }

        if ($this->weightUnit === $unit) {
            return;
        }

        $this->weightUnit = $unit;

        app(LogToolEvent::class)->handle('caffeine-calculator', 'unit_toggled', [
            'unit' => $unit,
        ]);
    }

    public function setSensitivity(int $step): void
    {
        if ($step >= 1 && $step <= 5) {
            $this->sensitivity = $step;
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'weight' => ['required', 'numeric', 'gt:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'weight.required' => 'Enter your weight to calculate.',
            'weight.numeric' => 'Weight must be a number.',
            'weight.gt' => 'Weight must be greater than 0.',
        ];
    }

    public function updatedWeight(): void
    {
        $this->validateOnly('weight');

        if (! is_numeric($this->weight) || (float) $this->weight <= 0) {
            return;
        }

        $weightKg = WeightConverter::convertToKg((float) $this->weight, $this->weightUnit);

        app(LogToolEvent::class)->handle('caffeine-calculator', 'weight_entered', [
            'weight_kg' => $weightKg,
        ]);
    }

    public function calculate(): void
    {
        $this->validate();

        if ($this->drinkId === null) {
            return;
        }

        $drink = CaffeineDrink::query()->find($this->drinkId);

        if ($drink === null) {
            return;
        }

        if ($drink->caffeine_mg === null || (float) $drink->caffeine_mg <= 0) {
            $this->lacksCaffeineEstimate = true;
            $this->safeMg = null;
            $this->safeCups = null;
            $this->perCupMg = null;

            return;
        }

        $this->lacksCaffeineEstimate = false;

        $weightKg = WeightConverter::convertToKg((float) $this->weight, $this->weightUnit);
        $clampedKg = max(self::MIN_WEIGHT_KG, min(self::MAX_WEIGHT_KG, $weightKg));

        $result = app(CalculateCaffeineSafeDose::class)->handle(
            weightKg: $clampedKg,
            sensitivityStep: $this->sensitivity - 1,
            perCupMg: (float) $drink->caffeine_mg,
        );

        assert($result instanceof SafeDoseData);

        $this->safeMg = $result->safeMg;
        $this->safeCups = $result->cups;
        $this->perCupMg = (float) $drink->caffeine_mg;
    }

    #[Computed]
    public function hasDrinks(): bool
    {
        return CaffeineDrink::query()->exists();
    }

    #[Computed]
    public function isWeightOutOfRange(): bool
    {
        if (! is_numeric($this->weight) || (float) $this->weight <= 0) {
            return false;
        }

        $weightKg = WeightConverter::convertToKg((float) $this->weight, $this->weightUnit);

        return $weightKg < self::MIN_WEIGHT_KG || $weightKg > self::MAX_WEIGHT_KG;
    }

    /**
     * @return Collection<int, array{id: int, name: string, category: ?string, caffeine_mg: float, rank: int}>
     */
    #[Computed]
    public function drinkOptions(): Collection
    {
        $query = mb_strtolower(mb_trim($this->drinkQuery));

        if ($query === '') {
            return collect();
        }

        return CaffeineDrink::query()
            ->orderBy('name')
            ->get(['id', 'name', 'category', 'caffeine_mg'])
            ->map(function (CaffeineDrink $drink) use ($query): ?array {
                $name = mb_strtolower($drink->name);

                $rank = match (true) {
                    $name === $query => 0,
                    str_starts_with($name, $query) => 1,
                    str_contains($name, $query) => 2,
                    default => null,
                };

                if ($rank === null) {
                    return null;
                }

                return [
                    'id' => $drink->id,
                    'name' => $drink->name,
                    'category' => $drink->category,
                    'caffeine_mg' => (float) $drink->caffeine_mg,
                    'rank' => $rank,
                ];
            })
            ->filter()
            ->sortBy([['rank', 'asc'], ['name', 'asc']])
            ->values();
    }

    public function selectDrink(int $id): void
    {
        $drink = CaffeineDrink::query()->find($id);

        if ($drink === null) {
            return;
        }

        $this->drinkId = $drink->id;
        $this->drinkQuery = $drink->name;

        app(LogToolEvent::class)->handle('caffeine-calculator', 'drink_picked', [
            'drink' => $drink->slug,
        ]);
    }

    public function clearDrink(): void
    {
        $this->drinkId = null;
        $this->drinkQuery = '';
    }

    public function toggleOptimiseForSleep(): void
    {
        $this->optimiseForSleep = ! $this->optimiseForSleep;

        if (! $this->optimiseForSleep) {
            $this->bedtime = null;
        }
    }

    #[Computed]
    public function selectedDrink(): ?CaffeineDrink
    {
        if ($this->drinkId === null) {
            return null;
        }

        return CaffeineDrink::query()->find($this->drinkId);
    }
}; ?>

<div class="min-h-screen w-full bg-gray-50 dark:bg-slate-900">
    <div class="mx-auto max-w-2xl px-4 py-12">
        <h1 class="text-[32px] font-bold leading-tight tracking-tight text-gray-900 md:text-5xl dark:text-slate-50">
            Coffee Caffeine Calculator: How Much Is Too Much?
        </h1>
        <p class="mt-4 text-lg text-gray-600 dark:text-slate-400">
            Choose your drink, tell us about you, and find your safe daily limit.
        </p>

        <div
            data-testid="caffeine-form-card"
            class="mt-8 rounded-xl border border-gray-200 bg-white p-6 md:p-8 dark:border-slate-700 dark:bg-slate-800"
        >
            <div data-testid="caffeine-form-rows" class="space-y-6">
                <div data-testid="caffeine-form-row-weight">
                    <div class="flex items-center justify-between gap-4">
                        <label for="caffeine-weight" class="block text-sm font-medium text-gray-700 dark:text-slate-200">
                            Your weight
                        </label>
                        <div
                            data-testid="caffeine-weight-unit-toggle"
                            role="group"
                            aria-label="Weight unit"
                            class="inline-flex gap-2"
                        >
                            <button
                                type="button"
                                wire:click="setUnit('kg')"
                                data-testid="caffeine-weight-unit-kg"
                                aria-pressed="{{ $weightUnit === 'kg' ? 'true' : 'false' }}"
                                @class([
                                    'rounded-full border px-3 py-1 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-emerald-500/30',
                                    'border-emerald-600 bg-emerald-600 text-white dark:hover:bg-emerald-400' => $weightUnit === 'kg',
                                    'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' => $weightUnit !== 'kg',
                                ])
                            >
                                Kilos
                            </button>
                            <button
                                type="button"
                                wire:click="setUnit('lb')"
                                data-testid="caffeine-weight-unit-lb"
                                aria-pressed="{{ $weightUnit === 'lb' ? 'true' : 'false' }}"
                                @class([
                                    'rounded-full border px-3 py-1 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-emerald-500/30',
                                    'border-emerald-600 bg-emerald-600 text-white dark:hover:bg-emerald-400' => $weightUnit === 'lb',
                                    'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' => $weightUnit !== 'lb',
                                ])
                            >
                                Pounds
                            </button>
                        </div>
                    </div>
                    <input
                        type="number"
                        id="caffeine-weight"
                        wire:model.blur="weight"
                        inputmode="decimal"
                        min="0"
                        step="0.1"
                        placeholder="e.g. 70"
                        aria-describedby="caffeine-weight-error"
                        @class([
                            'mt-1 block w-full rounded-md border bg-white px-3.5 py-2.5 text-base text-gray-900 placeholder-gray-400 outline-none focus:ring-2 dark:bg-slate-900 dark:text-slate-50 dark:placeholder-slate-500',
                            'border-gray-200 focus:border-emerald-500 focus:ring-emerald-500/15 dark:border-slate-700' => ! $errors->has('weight'),
                            'border-red-600 focus:border-red-600 focus:ring-red-600/15 dark:border-red-500' => $errors->has('weight'),
                        ])
                    />
                    @error('weight')
                        <p
                            id="caffeine-weight-error"
                            data-testid="caffeine-weight-error"
                            class="mt-1 text-sm text-red-600 dark:text-red-400"
                        >
                            {{ $message }}
                        </p>
                    @enderror
                    @if (! $errors->has('weight') && $this->isWeightOutOfRange)
                        <p
                            data-testid="caffeine-weight-clamp-notice"
                            class="mt-1 text-sm text-amber-700 dark:text-amber-400"
                        >
                            That's outside our documented range. Calculations are clamped to typical adult weights of 30&ndash;250 kg (66&ndash;551 lb).
                        </p>
                    @else
                        <p
                            data-testid="caffeine-weight-typical-adult-note"
                            class="mt-1 text-xs text-gray-500 dark:text-slate-400"
                        >
                            Calibrated for typical adult weights of 30&ndash;250 kg (66&ndash;551 lb).
                        </p>
                    @endif
                </div>

                <div data-testid="caffeine-form-row-drink">
                    <label for="caffeine-drink" class="block text-sm font-medium text-gray-700 dark:text-slate-200">
                        Choose a coffee
                    </label>
                    @if (! $this->hasDrinks)
                        <p
                            data-testid="caffeine-drink-empty-state"
                            class="mt-1 text-sm text-gray-500 dark:text-slate-400"
                        >
                            We're refreshing our drinks list&mdash;please check back soon.
                        </p>
                    @else
                    <div
                        x-data="{
                            open: false,
                            active: 0,
                            optionCount: () => ($refs.list ? $refs.list.children.length : 0),
                            move(delta) {
                                const total = this.optionCount();
                                if (total === 0) { return; }
                                this.active = (this.active + delta + total) % total;
                            },
                            choose() {
                                const total = this.optionCount();
                                if (total === 0) { return; }
                                const el = $refs.list.children[this.active];
                                if (el) { el.click(); }
                                this.open = false;
                            },
                        }"
                        @click.outside="open = false"
                        @keydown.escape.window="open = false"
                        class="relative mt-1"
                    >
                        <input
                            type="text"
                            id="caffeine-drink"
                            data-testid="caffeine-drink-input"
                            wire:model.live.debounce.150ms="drinkQuery"
                            x-on:focus="open = true"
                            x-on:input="open = true; active = 0"
                            x-on:keydown.arrow-down.prevent="open = true; move(1)"
                            x-on:keydown.arrow-up.prevent="open = true; move(-1)"
                            x-on:keydown.enter.prevent="choose()"
                            x-on:keydown.escape.prevent="open = false"
                            role="combobox"
                            autocomplete="off"
                            aria-autocomplete="list"
                            aria-controls="caffeine-drink-listbox"
                            x-bind:aria-expanded="open && {{ count($this->drinkOptions) }} > 0 ? 'true' : 'false'"
                            x-bind:aria-activedescendant="open ? 'caffeine-drink-option-' + active : null"
                            placeholder="eg. Americano"
                            class="block w-full rounded-md border border-gray-200 bg-white px-3.5 py-2.5 text-base text-gray-900 placeholder-gray-400 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/15 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-50 dark:placeholder-slate-500"
                        />

                        @if ($drinkQuery !== '' && count($this->drinkOptions) > 0)
                            <ul
                                x-show="open"
                                x-cloak
                                x-transition.opacity.duration.150ms
                                x-ref="list"
                                id="caffeine-drink-listbox"
                                data-testid="caffeine-drink-listbox"
                                role="listbox"
                                class="absolute left-0 right-0 z-10 mt-1 max-h-64 overflow-y-auto rounded-md border border-gray-200 bg-white py-1 shadow-lg dark:border-slate-700 dark:bg-slate-800"
                            >
                                @foreach ($this->drinkOptions as $index => $option)
                                    <li
                                        id="caffeine-drink-option-{{ $index }}"
                                        data-testid="caffeine-drink-option-{{ $option['id'] }}"
                                        role="option"
                                        x-bind:aria-selected="active === {{ $index }} ? 'true' : 'false'"
                                        x-on:mouseenter="active = {{ $index }}"
                                        x-on:mousedown.prevent
                                        x-on:click="open = false"
                                        wire:click="selectDrink({{ $option['id'] }})"
                                        x-bind:class="active === {{ $index }}
                                            ? 'cursor-pointer px-3 py-2 text-sm bg-emerald-50 text-emerald-900 dark:bg-slate-700 dark:text-slate-50'
                                            : 'cursor-pointer px-3 py-2 text-sm text-gray-900 dark:text-slate-100'"
                                    >
                                        <span class="font-medium">{{ $option['name'] }}</span>
                                        <span class="ml-2 text-xs text-gray-500 dark:text-slate-400">
                                            {{ (int) round($option['caffeine_mg']) }} mg
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    @endif
                </div>

                @php
                    $sensitivityLabels = [
                        1 => 'More tolerant',
                        2 => 'Tolerant',
                        3 => 'Normal',
                        4 => 'Sensitive',
                        5 => 'More sensitive',
                    ];
                @endphp
                <div data-testid="caffeine-form-row-sensitivity">
                    <span class="block text-sm font-medium text-gray-700 dark:text-slate-200">
                        Caffeine sensitivity
                    </span>
                    <div
                        data-testid="caffeine-sensitivity-rail"
                        role="radiogroup"
                        aria-label="Caffeine sensitivity"
                        class="relative mt-3"
                    >
                        <div
                            aria-hidden="true"
                            class="absolute left-0 right-0 top-1/2 h-0.5 -translate-y-1/2 bg-gray-200 dark:bg-slate-700"
                        ></div>
                        <div class="relative flex items-center justify-between">
                            @foreach (range(1, 5) as $step)
                                <button
                                    type="button"
                                    role="radio"
                                    wire:click="setSensitivity({{ $step }})"
                                    wire:key="caffeine-sensitivity-step-{{ $step }}"
                                    data-testid="caffeine-sensitivity-step-{{ $step }}"
                                    aria-checked="{{ $sensitivity === $step ? 'true' : 'false' }}"
                                    aria-label="Sensitivity step {{ $step }} of 5: {{ $sensitivityLabels[$step] }}"
                                    tabindex="{{ $sensitivity === $step ? '0' : '-1' }}"
                                    @if ($step < 5)
                                        x-on:keydown.arrow-right.prevent="
                                            const next = $el.nextElementSibling;
                                            if (next) { next.focus(); $wire.setSensitivity({{ $step + 1 }}); }
                                        "
                                    @endif
                                    @if ($step > 1)
                                        x-on:keydown.arrow-left.prevent="
                                            const prev = $el.previousElementSibling;
                                            if (prev) { prev.focus(); $wire.setSensitivity({{ $step - 1 }}); }
                                        "
                                    @endif
                                    @class([
                                        'h-7 w-7 rounded-full border transition focus:outline-none focus:ring-2 focus:ring-emerald-500/30',
                                        'border-emerald-600 bg-emerald-600 ring-2 ring-inset ring-white dark:ring-slate-800' => $sensitivity === $step,
                                        'border-gray-300 bg-white hover:border-gray-400 dark:border-slate-600 dark:bg-slate-800 dark:hover:border-slate-500' => $sensitivity !== $step,
                                    ])
                                ></button>
                            @endforeach
                        </div>
                    </div>
                    <div class="mt-2 flex items-center justify-between text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-slate-400">
                        <span>More tolerant</span>
                        <span>Normal</span>
                        <span>More sensitive</span>
                    </div>
                    <div
                        data-testid="caffeine-sensitivity-announcement"
                        class="sr-only"
                        aria-live="polite"
                        aria-atomic="true"
                    >
                        Sensitivity: {{ $sensitivityLabels[$sensitivity] }}, step {{ $sensitivity }} of 5
                    </div>
                </div>
            </div>

            <button
                type="button"
                wire:click="calculate"
                data-testid="caffeine-cta-calculate"
                @disabled(! $this->hasDrinks)
                aria-disabled="{{ ! $this->hasDrinks ? 'true' : 'false' }}"
                class="mt-6 inline-flex w-full items-center justify-center rounded-lg bg-emerald-500 px-6 py-3 text-base font-semibold text-white transition duration-150 hover:-translate-y-px hover:bg-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 active:translate-y-0 active:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0 disabled:hover:bg-emerald-500 sm:w-auto dark:hover:bg-emerald-400 dark:focus:ring-offset-slate-900"
            >
                How Much Coffee?
            </button>
        </div>

        @if ($lacksCaffeineEstimate)
            <div
                data-testid="caffeine-result-fallback"
                role="status"
                aria-live="polite"
                class="mt-6 rounded-xl border border-amber-200 border-t-4 border-t-amber-500 bg-amber-50 p-6 md:p-8 dark:border-amber-900/50 dark:border-t-amber-500 dark:bg-amber-950/30"
            >
                <p
                    data-testid="caffeine-result-fallback-headline"
                    class="text-lg font-semibold text-amber-900 dark:text-amber-200"
                >
                    We don't have a confident estimate for this drink yet.
                </p>
                <p
                    data-testid="caffeine-result-fallback-suggestion"
                    class="mt-2 text-sm text-amber-800 dark:text-amber-300"
                >
                    Try picking another drink from the list above so we can calculate your safe daily limit.
                </p>
            </div>
        @endif

        @if ($safeMg !== null && $safeCups !== null && $perCupMg !== null)
            @php
                $perCupRounded = (int) round($perCupMg);
                $safeMgRounded = (int) round($safeMg);
                $breakdownTotal = (int) round($perCupMg * $safeCups);
            @endphp
            <div
                data-testid="caffeine-result-panel"
                role="status"
                aria-live="polite"
                class="mt-6 rounded-xl border border-gray-200 border-t-4 border-t-emerald-500 bg-white p-6 md:p-8 dark:border-slate-700 dark:border-t-emerald-500 dark:bg-slate-800"
            >
                <p
                    data-testid="caffeine-result-cups"
                    class="text-5xl font-bold leading-none tracking-tight text-gray-900 tabular-nums md:text-6xl dark:text-slate-50"
                >
                    &approx; {{ $safeCups }} {{ $safeCups === 1 ? 'cup' : 'cups' }}
                </p>
                <p
                    data-testid="caffeine-result-safe-mg"
                    class="mt-3 text-lg text-gray-700 dark:text-slate-300"
                >
                    Your safe daily limit is about <span class="font-semibold tabular-nums">{{ $safeMgRounded }} mg</span> of caffeine.
                </p>
                <p
                    data-testid="caffeine-result-breakdown"
                    class="mt-2 text-sm text-gray-500 dark:text-slate-400"
                >
                    &approx; <span class="tabular-nums">{{ $perCupRounded }}</span> mg per cup &times; <span class="tabular-nums">{{ $safeCups }}</span> {{ $safeCups === 1 ? 'cup' : 'cups' }} &approx; <span class="tabular-nums">{{ $breakdownTotal }}</span> mg
                </p>
            </div>
        @endif

        @if ($safeMg !== null && $safeCups !== null && $perCupMg !== null)
            @php
                $sensitivityMultipliers = [1 => 0.7, 2 => 0.85, 3 => 1.0, 4 => 1.15, 5 => 1.3];
                $currentMultiplier = $sensitivityMultipliers[$sensitivity] ?? 1.0;
                $drink = $this->selectedDrink;
            @endphp
            <details
                data-testid="caffeine-how-calculated"
                class="mt-4 rounded-xl border border-gray-200 bg-white p-4 md:p-6 dark:border-slate-700 dark:bg-slate-800"
            >
                <summary
                    data-testid="caffeine-how-calculated-summary"
                    class="cursor-pointer text-sm font-medium text-gray-700 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 dark:text-slate-200 dark:hover:text-slate-50"
                >
                    How we calculated this
                </summary>
                <div class="mt-3 space-y-3 text-sm text-gray-700 dark:text-slate-300">
                    <p data-testid="caffeine-how-calculated-formula">
                        <span class="font-semibold">Formula:</span>
                        safe daily mg = weight (kg) &times; 5.7 mg/kg &times; sensitivity multiplier.
                        With your inputs: <span class="tabular-nums">{{ $weight }}</span> {{ $weightUnit }} &times; 5.7 &times; <span class="tabular-nums">{{ $currentMultiplier }}</span> &approx; <span class="tabular-nums">{{ (int) round($safeMg) }}</span> mg.
                    </p>
                    <p data-testid="caffeine-how-calculated-sensitivity">
                        Your sensitivity multiplier (<span class="tabular-nums">{{ $currentMultiplier }}</span>) is based on your self-reported caffeine sensitivity step.
                    </p>
                    <p data-testid="caffeine-how-calculated-fda">
                        <span class="font-semibold">FDA reference:</span>
                        the U.S. Food &amp; Drug Administration considers up to 400 mg/day of caffeine generally safe for healthy adults.
                        <a
                            href="https://www.fda.gov/consumers/consumer-updates/spilling-beans-how-much-caffeine-too-much"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-emerald-600 underline hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300"
                        >
                            FDA: Spilling the Beans &mdash; How Much Caffeine is Too Much?
                        </a>
                    </p>
                    @if ($drink !== null)
                        <p data-testid="caffeine-how-calculated-drink-citation">
                            <span class="font-semibold">Per-drink caffeine ({{ $drink->name }}, {{ (int) round((float) $drink->caffeine_mg) }} mg):</span>
                            sourced from {{ $drink->source ?? 'public-domain nutrition data' }}@if ($drink->attribution), {{ $drink->attribution }}@endif.
                            @if ($drink->license_url)
                                <a
                                    href="{{ $drink->license_url }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-emerald-600 underline hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300"
                                >
                                    View source
                                </a>
                            @endif
                        </p>
                    @endif
                </div>
            </details>

            <div data-testid="caffeine-optimise-sleep" class="mt-4">
                <button
                    type="button"
                    wire:click="toggleOptimiseForSleep"
                    data-testid="caffeine-optimise-sleep-toggle"
                    aria-expanded="{{ $optimiseForSleep ? 'true' : 'false' }}"
                    aria-controls="caffeine-bedtime-panel"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-transparent px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800"
                >
                    Also, when should I stop drinking?
                </button>
                @if ($optimiseForSleep)
                    <div
                        id="caffeine-bedtime-panel"
                        data-testid="caffeine-bedtime-panel"
                        class="mt-4 rounded-xl border border-gray-200 bg-white p-4 md:p-6 dark:border-slate-700 dark:bg-slate-800"
                    >
                        <label
                            for="caffeine-bedtime"
                            class="block text-sm font-medium text-gray-700 dark:text-slate-200"
                        >
                            What time do you go to bed?
                        </label>
                        <input
                            type="time"
                            id="caffeine-bedtime"
                            data-testid="caffeine-bedtime-input"
                            wire:model.blur="bedtime"
                            class="mt-1 block w-full rounded-md border border-gray-200 bg-white px-3.5 py-2.5 text-base text-gray-900 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/15 sm:w-auto dark:border-slate-700 dark:bg-slate-900 dark:text-slate-50"
                        />
                    </div>
                @endif
            </div>
        @endif

        <x-json-ld.caffeine-calculator />
    </div>
</div>
