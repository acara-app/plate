<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\SleepQuality;
use App\Enums\StressLevel;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreDailyCheckInRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.$this->today()->toDateString()],
            'sleep_hours' => ['nullable', 'required_without_all:sleep_quality,stress_level', 'numeric', 'between:0,24'],
            'sleep_quality' => ['nullable', 'required_without_all:sleep_hours,stress_level', Rule::enum(SleepQuality::class)],
            'stress_level' => ['nullable', 'required_without_all:sleep_hours,sleep_quality', Rule::enum(StressLevel::class)],
        ];
    }

    public function selectedDay(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->string('date')->value(), $this->timezone())->startOfDay();
    }

    public function sleepHours(): ?float
    {
        return $this->filled('sleep_hours') ? $this->float('sleep_hours') : null;
    }

    private function today(): CarbonImmutable
    {
        return CarbonImmutable::now($this->timezone());
    }

    private function timezone(): string
    {
        /** @var User $user */
        $user = $this->user();

        return $user->resolveTimezone();
    }
}
