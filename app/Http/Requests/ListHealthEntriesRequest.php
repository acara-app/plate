<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Http\FormRequest;

final class ListHealthEntriesRequest extends FormRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    public function selectedDay(string $timezone): CarbonImmutable
    {
        $date = $this->validated('date');

        return is_string($date)
            ? CarbonImmutable::parse($date, $timezone)->startOfDay()
            : CarbonImmutable::now($timezone)->startOfDay();
    }

    public function selectedWeekStart(string $timezone): CarbonImmutable
    {
        return $this->selectedDay($timezone)->startOfWeek(CarbonInterface::MONDAY);
    }
}
