<?php

declare(strict_types=1);

namespace App\Actions;

use App\DataObjects\UsdaFoodImportRowData;
use App\Services\JsonStreamReader;
use Generator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

final readonly class ImportUsdaFoodDataAction
{
    public function __construct(
        private JsonStreamReader $streamReader
    ) {}

    public function handle(string $filePath, string $table, int $chunkSize = 1000): void
    {
        $this->extract($filePath)
            ->map(fn (array $item): UsdaFoodImportRowData => $this->transform($item))
            ->chunk($chunkSize)
            ->each(fn (LazyCollection $chunk) => $this->load($chunk, $table));
    }

    /**
     * Extract: Stream JSON data from file
     *
     * @return LazyCollection<int, array<string, mixed>>
     */
    private function extract(string $filePath): LazyCollection
    {
        $streamReader = $this->streamReader;

        /** @phpstan-ignore return.void */
        return LazyCollection::make(static fn (): Generator => $streamReader->stream($filePath));
    }

    /**
     * Transform: Convert raw JSON item to database row format
     *
     * @param  array<string, mixed>  $item
     */
    private function transform(array $item): UsdaFoodImportRowData
    {
        $description = $item['description'] ?? null;
        $foodCategory = $item['foodCategory'] ?? null;

        return UsdaFoodImportRowData::from([
            'id' => $item['fdcId'],
            'description' => is_string($description) ? $description : null,
            'food_category' => is_array($foodCategory) && isset($foodCategory['description']) ? $foodCategory['description'] : null,
            'publication_date' => $this->parseDate(is_string($item['publicationDate'] ?? null) ? $item['publicationDate'] : null),
            'nutrients' => json_encode($item['foodNutrients'] ?? []) ?: '[]',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Load: Insert transformed rows into database
     *
     * @param  LazyCollection<int, UsdaFoodImportRowData>  $rows
     */
    private function load(LazyCollection $rows, string $table): void
    {
        DB::table($table)->upsert(
            $rows->map(fn (UsdaFoodImportRowData $row): array => $row->toArray())->all(),
            ['id'],
            ['description', 'food_category', 'publication_date', 'nutrients', 'updated_at']
        );
    }

    private function parseDate(?string $date): ?string
    {
        if ($date === null) {
            return null;
        }

        $parsed = \Illuminate\Support\Facades\Date::createFromFormat('n/j/Y', $date);

        return $parsed ? $parsed->format('Y-m-d') : null;
    }
}
