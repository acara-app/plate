<?php

declare(strict_types=1);

namespace App\Data\CaffeineGuidance;

use Spatie\LaravelData\Data;

/**
 * @codeCoverageIgnore
 */
final class SafetyNoteData extends Data
{
    /**
     * @param  list<string>  $items
     */
    public function __construct(
        public string $title,
        public string $body,
        public array $items,
    ) {}

    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(): array
    {
        return [
            'items' => ['present', 'array'],
            'items.*' => ['required', 'string'],
        ];
    }
}
