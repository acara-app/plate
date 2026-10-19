<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\SleepQuality;
use App\Enums\StressLevel;
use App\Http\Requests\Concerns\ResolvesSelectedDay;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreDailyCheckInRequest extends FormRequest
{
    use ResolvesSelectedDay;

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'date' => $this->selectedDayRules(),
            'sleep_hours' => ['nullable', 'required_without_all:sleep_quality,stress_level', 'numeric', 'between:0,24'],
            'sleep_quality' => ['nullable', 'required_without_all:sleep_hours,stress_level', Rule::enum(SleepQuality::class)],
            'stress_level' => ['nullable', 'required_without_all:sleep_hours,sleep_quality', Rule::enum(StressLevel::class)],
        ];
    }

    public function sleepHours(): ?float
    {
        return $this->filled('sleep_hours') ? $this->float('sleep_hours') : null;
    }
}
