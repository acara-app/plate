<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read string|null $description
 * @property-read float|null $protein_ratio
 * @property-read float|null $carb_ratio
 * @property-read float|null $fat_ratio
 * @property-read float|null $calorie_adjustment
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class Goal extends Model
{
    /** @use HasFactory<\Database\Factories\GoalFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'integer',
            'name' => 'string',
            'description' => 'string',
            'protein_ratio' => 'float',
            'carb_ratio' => 'float',
            'fat_ratio' => 'float',
            'calorie_adjustment' => 'float',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
