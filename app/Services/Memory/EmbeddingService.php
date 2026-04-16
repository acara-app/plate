<?php

declare(strict_types=1);

namespace App\Services\Memory;

use Laravel\Ai\Embeddings;

final readonly class EmbeddingService
{
    /**
     * @return array<int, float>
     */
    public function generate(string $text): array
    {
        $response = Embeddings::for([$text])
            ->dimensions($this->dimensions())
            ->timeout($this->timeout())
            ->generate();

        /** @var array<int, float> $vector */
        $vector = $response->embeddings[0] ?? [];

        return $vector;
    }

    /**
     * @param  array<int, string>  $texts
     * @return array<int, array<int, float>>
     */
    public function generateBatch(array $texts): array
    {
        if ($texts === []) {
            return [];
        }

        $response = Embeddings::for(array_values($texts))
            ->dimensions($this->dimensions())
            ->timeout($this->timeout())
            ->generate();

        /** @var array<int, array<int, float>> $embeddings */
        $embeddings = $response->embeddings;

        return $embeddings;
    }

    private function dimensions(): int
    {
        /** @phpstan-ignore cast.int */
        return (int) config('memory.embeddings.dimensions', 1536);
    }

    private function timeout(): int
    {
        /** @phpstan-ignore cast.int */
        return (int) config('memory.embeddings.timeout', 30);
    }
}
