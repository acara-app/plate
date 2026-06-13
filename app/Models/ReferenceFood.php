<?php

declare(strict_types=1);

namespace App\Models;

use App\Data\NutrientValues;
use Carbon\CarbonInterface;
use Database\Factories\ReferenceFoodFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $source
 * @property string $external_id
 * @property string $data_type
 * @property string $description
 * @property string $match_name
 * @property string|null $food_category
 * @property float|null $calories_per_100g
 * @property float|null $protein_per_100g
 * @property float|null $carbs_per_100g
 * @property float|null $fat_per_100g
 * @property array<string, mixed> $nutrients
 * @property list<float>|null $embedding
 * @property string $release
 * @property CarbonInterface|null $publication_date
 */
#[Table(name: 'reference_foods')]
final class ReferenceFood extends Model
{
    /** @use HasFactory<ReferenceFoodFactory> */
    use HasFactory;

    protected $guarded = [];

    public static function normalizeName(string $name): string
    {
        $ascii = Str::ascii($name);
        $lower = mb_strtolower(mb_trim($ascii));
        $cleaned = preg_replace('/[^a-z0-9 ]+/', ' ', $lower) ?? '';
        $collapsed = preg_replace('/\s+/', ' ', $cleaned) ?? '';

        return mb_trim($collapsed);
    }

    public function casts(): array
    {
        return [
            'calories_per_100g' => 'float',
            'protein_per_100g' => 'float',
            'carbs_per_100g' => 'float',
            'fat_per_100g' => 'float',
            'nutrients' => 'array',
            'embedding' => 'array',
            'publication_date' => 'date',
        ];
    }

    public function macrosFor(float $grams): NutrientValues
    {
        $factor = $grams / 100;

        return new NutrientValues(
            calories: round((float) $this->calories_per_100g * $factor, 1),
            protein: round((float) $this->protein_per_100g * $factor, 1),
            carbs: round((float) $this->carbs_per_100g * $factor, 1),
            fat: round((float) $this->fat_per_100g * $factor, 1),
        );
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function nutritionallyComplete(Builder $query): void
    {
        $query->whereNotNull('calories_per_100g')
            ->whereNotNull('protein_per_100g')
            ->whereNotNull('carbs_per_100g')
            ->whereNotNull('fat_per_100g');
    }
}
