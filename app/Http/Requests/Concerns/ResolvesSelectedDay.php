<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use App\Models\User;
use Carbon\CarbonImmutable;

trait ResolvesSelectedDay
{
    public function selectedDay(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->string('date')->value(), $this->timezone())->startOfDay();
    }

    /** @return array<int, string> */
    private function selectedDayRules(): array
    {
        return ['required', 'date_format:Y-m-d', 'before_or_equal:'.CarbonImmutable::now($this->timezone())->toDateString()];
    }

    private function timezone(): string
    {
        /** @var User $user */
        $user = $this->user();

        return $user->resolveTimezone();
    }
}
