<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\PredictGlucoseSpikeAction;
use App\Enums\SpikeRiskLevel;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use RyanChandler\LaravelCloudflareTurnstile\Rules\Turnstile;
use Throwable;

#[Layout('layouts.mini-app')]
final class SpikeCalculator extends Component
{
    #[Validate('required|string|min:2|max:500')]
    public string $food = '';

    public ?string $turnstileToken = null;

    public bool $loading = false;

    /** @var array{food: string, riskLevel: string, estimatedGlycemicLoad: int, explanation: string, smartFix: string, spikeReductionPercentage: int}|null */
    public ?array $result = null;

    public ?string $error = null;

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

    public function render(): View
    {
        return view('livewire.spike-calculator');
    }
}
