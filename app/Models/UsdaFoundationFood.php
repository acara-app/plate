<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class UsdaFoundationFood extends Model
{
    /** @use HasFactory<\Database\Factories\UsdaFoundationFoodFactory> */
    use HasFactory;

    protected $table = 'usda_foundation_foods';

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
