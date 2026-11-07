<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SnapPhotoPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SnapPhotoPeriod>
 */
final class SnapPhotoPeriodFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject' => 'guest:'.Str::uuid(),
            'starts_at' => '2000-01-01 00:00:00',
            'ends_at' => null,
            'allowance' => 2,
            'mode' => 'trial',
        ];
    }
}
