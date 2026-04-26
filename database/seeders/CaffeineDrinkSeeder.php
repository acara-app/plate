<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CaffeineDrink;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use RuntimeException;

final class CaffeineDrinkSeeder extends Seeder
{
    private const string CSV_PATH = 'database/data/caffeine_drinks.csv';

    private const array REQUIRED_COLUMNS = [
        'name',
        'slug',
        'category',
        'volume_oz',
        'caffeine_mg',
        'source',
        'license_url',
        'attribution',
        'verified_at',
    ];

    private const array PROVENANCE_COLUMNS = [
        'source',
        'license_url',
        'verified_at',
    ];

    public function run(): void
    {
        $path = base_path(self::CSV_PATH);

        if (! is_file($path)) {
            throw new RuntimeException("Caffeine drinks CSV not found at {$path}.");
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException("Unable to open caffeine drinks CSV at {$path}.");
        }

        try {
            $header = fgetcsv($handle);

            if ($header === false || $header === null) {
                throw new RuntimeException('Caffeine drinks CSV is empty.');
            }

            $missingColumns = array_diff(self::REQUIRED_COLUMNS, $header);

            if ($missingColumns !== []) {
                throw new RuntimeException(
                    'Caffeine drinks CSV is missing required columns: '.implode(', ', $missingColumns).'.'
                );
            }

            $lineNumber = 1;

            while (($row = fgetcsv($handle)) !== false) {
                $lineNumber++;

                if ($row === [null] || $row === []) {
                    continue;
                }

                if (count($row) !== count($header)) {
                    throw new RuntimeException(
                        "Caffeine drinks CSV row {$lineNumber} has ".count($row).' columns, expected '.count($header).'.'
                    );
                }

                /** @var array<string, string> $record */
                $record = array_combine($header, $row);

                foreach (self::PROVENANCE_COLUMNS as $column) {
                    if (mb_trim((string) ($record[$column] ?? '')) === '') {
                        throw new RuntimeException(
                            "Caffeine drinks CSV row {$lineNumber} ('{$record['slug']}') is missing required '{$column}'."
                        );
                    }
                }

                CaffeineDrink::query()->updateOrCreate(
                    ['slug' => $record['slug']],
                    [
                        'name' => $record['name'],
                        'category' => $record['category'] !== '' ? $record['category'] : null,
                        'volume_oz' => $record['volume_oz'] !== '' ? $record['volume_oz'] : null,
                        'caffeine_mg' => $record['caffeine_mg'],
                        'source' => $record['source'],
                        'license_url' => $record['license_url'],
                        'attribution' => $record['attribution'] !== '' ? $record['attribution'] : null,
                        'verified_at' => CarbonImmutable::parse($record['verified_at']),
                    ]
                );
            }
        } finally {
            fclose($handle);
        }
    }
}
