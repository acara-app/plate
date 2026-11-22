<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\VisualDataset;
use Closure;
use Illuminate\Support\Str;

final class TurnDataRegistry
{
    private bool $collecting = false;

    /**
     * @var array<string, VisualDataset>
     */
    private array $datasets = [];

    /**
     * @template TResult
     *
     * @param  Closure(): TResult  $callback
     * @return TResult
     */
    public function collect(Closure $callback): mixed
    {
        $previous = [$this->collecting, $this->datasets];
        $this->collecting = true;
        $this->datasets = [];

        try {
            return $callback();
        } finally {
            [$this->collecting, $this->datasets] = $previous;
        }
    }

    public function isCollecting(): bool
    {
        return $this->collecting;
    }

    public function register(string $name, VisualDataset $dataset): void
    {
        if (! $this->collecting) {
            return;
        }

        $base = mb_trim((string) preg_replace('/[^a-z0-9]+/', '_', Str::lower(Str::snake($name))), '_');
        $base = $base === '' ? 'dataset' : $base;

        $handle = $base;

        for ($copy = 2; isset($this->datasets[$handle]); $copy++) {
            $handle = "{$base}_{$copy}";
        }

        $this->datasets[$handle] = $dataset;
    }

    /**
     * @return array<string, VisualDataset>
     */
    public function datasets(): array
    {
        return $this->datasets;
    }

    /**
     * @param  list<string>  $described
     */
    public function footer(array $described): string
    {
        $lines = [];

        foreach (array_diff_key($this->datasets, array_flip($described)) as $handle => $dataset) {
            $lines[] = $dataset->describe($handle);
        }

        return $lines === [] ? '' : "\n\nDatasets for visuals:\n".implode("\n", $lines);
    }
}
