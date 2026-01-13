<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int $id
 * @property-read int $order
 * @property-read string $name
 * @property-read string|null $description
 * @property-read string|null $nutritional_impact
 * @property-read array<int, string>|null $recommended_nutrients
 * @property-read array<int, string>|null $nutrients_to_limit
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read UserProfileHealthCondition|null $pivot
 * @property-read string|null $notes
 */
final class HealthCondition extends Model
{
    /** @use HasFactory<\Database\Factories\HealthConditionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $appends = [
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'integer',
            'order' => 'integer',
            'name' => 'string',
            'description' => 'string',
            'nutritional_impact' => 'string',
            'recommended_nutrients' => 'array',
            'nutrients_to_limit' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function notes(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->relationLoaded('pivot') ? $this->pivot?->notes : null);
    }
}
