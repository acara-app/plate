<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read string $activity_level
 * @property-read string|null $sleep_hours
 * @property-read string|null $occupation
 * @property-read string|null $description
 * @property-read float $activity_multiplier
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class Lifestyle extends Model
{
    /** @use HasFactory<\Database\Factories\LifestyleFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'integer',
            'name' => 'string',
            'activity_level' => 'string',
            'sleep_hours' => 'string',
            'occupation' => 'string',
            'description' => 'string',
            'activity_multiplier' => 'float',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
