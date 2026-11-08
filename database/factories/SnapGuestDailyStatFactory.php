<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SnapGuestDailyStat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SnapGuestDailyStat>
 */
final class SnapGuestDailyStatFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => now()->subDay()->toDateString(),
            'guests' => 10,
            'scans' => 12,
            'failed_scans' => 1,
            'guests_by_scans' => [0 => 1, 1 => 6, 2 => 3],
            'came_back' => 2,
            'signed_in' => 1,
        ];
    }
}
