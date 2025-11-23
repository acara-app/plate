<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int $id
 * @property string $description
 * @property string|null $food_category
 * @property CarbonInterface|null $publication_date
 * @property array<int, array<string, mixed>> $nutrients
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class UsdaSrLegacyFood extends Model
{
    /** @use HasFactory<\Database\Factories\UsdaSrLegacyFoodFactory> */
    use HasFactory;

    protected $table = 'usda_sr_legacy_foods';

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'nutrients' => 'array',
            'publication_date' => 'date',
        ];
    }
}
