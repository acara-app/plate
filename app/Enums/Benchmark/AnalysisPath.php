<?php

declare(strict_types=1);

namespace App\Enums\Benchmark;

enum AnalysisPath: string
{
    case Raw = 'raw';
    case Enriched = 'enriched';

    public function label(): string
    {
        return match ($this) {
            self::Raw => 'Raw model',
            self::Enriched => 'With reference lookup',
        };
    }
}
