<?php

declare(strict_types=1);

namespace App\DataObjects;

use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;

final class UsdaFoodImportRowData extends Data
{
    public function __construct(
        public mixed $id,
        public ?string $description,
        public ?string $food_category,
        public ?string $publication_date,
        public string|false $nutrients,
        public Carbon $created_at,
        public Carbon $updated_at,
    ) {}
}
