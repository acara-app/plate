<?php

declare(strict_types=1);

namespace App\Services;

use Generator;

final readonly class JsonStreamReader
{
    /**
     * Stream JSON array from file line by line
     * Expects format: {"key": [{...}, {...}]}
     *
     * @return Generator<int, array<string, mixed>, mixed, void>
     */
    public function stream(string $path): Generator
    {
        if (! is_file($path)) {
            return;
        }

        $handle = @fopen($path, 'r');

        if ($handle === false) {
            return;
        }

        try {
            // Skip the first line which contains the opening key and bracket
            // e.g. {"FoundationFoods": [
            fgets($handle);

            while (($line = fgets($handle)) !== false) {
                $line = mb_trim($line);

                // Check for end of array
                if ($line === ']}' || $line === ']') {
                    break;
                }

                // Remove trailing comma if present
                if (str_ends_with($line, ',')) {
                    $line = mb_substr($line, 0, -1);
                }

                // Skip empty lines or lines that are just brackets if file format varies
                if (in_array($line, ['', '0', '[', '{'], true)) {
                    continue;
                }

                $data = json_decode($line, true);

                if (is_array($data)) {
                    /** @var array<string, mixed> $data */
                    yield $data;
                }
            }
        } finally {
            fclose($handle);
        }
    }
}
