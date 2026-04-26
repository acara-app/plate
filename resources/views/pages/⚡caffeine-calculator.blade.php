<?php

declare(strict_types=1);

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('layouts.mini-app', ['metaDescription' => 'Free caffeine calculator: estimate your safe daily caffeine dose and find out when to stop drinking coffee for better sleep.', 'metaKeywords' => 'caffeine calculator, safe caffeine dose, caffeine sleep cutoff, coffee calculator, caffeine half life'])]
#[Title('Coffee Caffeine Calculator: How Much Is Too Much?')]
class extends Component
{
    public ?string $weight = null;

    #[Url(as: 'unit', except: 'kg')]
    public string $weightUnit = 'kg';

    public function setUnit(string $unit): void
    {
        if (in_array($unit, ['kg', 'lb'], true)) {
            $this->weightUnit = $unit;
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
    }

    public function calculate(): void
    {
        $this->validate();
    }
}; ?>

<div class="mx-auto max-w-2xl px-4 py-12">
    <h1 class="text-[32px] font-bold leading-tight tracking-tight md:text-5xl">
        Coffee Caffeine Calculator: How Much Is Too Much?
    </h1>
    <p class="mt-4 text-lg text-gray-600">
        Choose your drink, tell us about you, and find your safe daily limit.
    </p>

    <div
        data-testid="caffeine-form-card"
        class="mt-8 rounded-xl border border-gray-200 bg-white p-6 md:p-8"
    >
        <div data-testid="caffeine-form-rows" class="space-y-6">
            <div data-testid="caffeine-form-row-weight">
                <div class="flex items-center justify-between gap-4">
                    <label for="caffeine-weight" class="block text-sm font-medium text-gray-700">
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
                                'border-emerald-600 bg-emerald-600 text-white' => $weightUnit === 'kg',
                                'border-gray-300 bg-white text-gray-700 hover:bg-gray-50' => $weightUnit !== 'kg',
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
                                'border-emerald-600 bg-emerald-600 text-white' => $weightUnit === 'lb',
                                'border-gray-300 bg-white text-gray-700 hover:bg-gray-50' => $weightUnit !== 'lb',
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
                        'mt-1 block w-full rounded-md border bg-white px-3.5 py-2.5 text-base text-gray-900 placeholder-gray-400 outline-none focus:ring-2',
                        'border-gray-200 focus:border-emerald-500 focus:ring-emerald-500/15' => ! $errors->has('weight'),
                        'border-red-600 focus:border-red-600 focus:ring-red-600/15' => $errors->has('weight'),
                    ])
                />
                @error('weight')
                    <p
                        id="caffeine-weight-error"
                        data-testid="caffeine-weight-error"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ $message }}
                    </p>
                @enderror
            </div>
        </div>
    </div>
</div>
