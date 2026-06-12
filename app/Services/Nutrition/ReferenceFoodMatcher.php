<?php

declare(strict_types=1);

namespace App\Services\Nutrition;

use Illuminate\Database\Eloquent\Model;
use App\Models\ReferenceFood;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Embeddings;
use Throwable;

final readonly class ReferenceFoodMatcher
{
    private const int EMBEDDING_CANDIDATES = 10;

    public function match(string $name): ?ReferenceMatch
    {
        $normalized = ReferenceFood::normalizeName($name);

        if ($normalized === '') {
            return null;
        }

        return $this->deterministicMatch($normalized)
            ?? $this->embeddingMatch($name);
    }

    private function deterministicMatch(string $normalized): ?ReferenceMatch
    {
        $queryTokens = explode(' ', $normalized);

        $best = null;
        $bestScore = 0.0;
        $bestTokenCount = PHP_INT_MAX;

        foreach ($this->candidates($queryTokens) as $candidate) {
            if ($candidate->match_name === $normalized) {
                return new ReferenceMatch($candidate, 1.0, 'exact');
            }

            $candidateTokens = explode(' ', $candidate->match_name);
            $score = $this->jaccard($queryTokens, $candidateTokens);
            $tokenCount = count($candidateTokens);

            if ($score > $bestScore || ($score === $bestScore && $tokenCount < $bestTokenCount)) {
                $best = $candidate;
                $bestScore = $score;
                $bestTokenCount = $tokenCount;
            }
        }

        if (!$best instanceof Model || $bestScore < $this->threshold()) {
            return null;
        }

        return new ReferenceMatch($best, $bestScore, 'tokens');
    }

    private function embeddingMatch(string $name): ?ReferenceMatch
    {
        if (! $this->embeddingsEnabled()) {
            return null;
        }

        $queryVector = $this->embed($name);

        if ($queryVector === null) {
            return null;
        }

        $best = null;
        $bestScore = 0.0;

        foreach ($this->vectorCandidates($queryVector) as $food) {
            $vector = $food->embedding;
            if (! is_array($vector)) {
                continue;
            }

            if ($vector === []) {
                continue;
            }

            $score = $this->cosine($queryVector, $vector);

            if ($score > $bestScore) {
                $best = $food;
                $bestScore = $score;
            }
        }

        if (!$best instanceof Model || $bestScore < $this->embeddingThreshold()) {
            return null;
        }

        return new ReferenceMatch($best, $bestScore, 'embedding');
    }

    /**
     * @param  list<float>  $queryVector
     * @return Collection<int, ReferenceFood>
     */
    private function vectorCandidates(array $queryVector): Collection
    {
        $query = ReferenceFood::query()
            ->nutritionallyComplete()
            ->whereNotNull('embedding');

        if (DB::connection()->getDriverName() === 'pgsql') {
            try {
                return $query
                    ->whereVectorSimilarTo('embedding', $queryVector, 0.0)
                    ->limit(self::EMBEDDING_CANDIDATES)
                    ->get();
            } catch (Throwable) {
                //
            }
        }

        return $query->orderBy('id')->get();
    }

    /**
     * @param  list<string>  $queryTokens
     * @return Collection<int, ReferenceFood>
     */
    private function candidates(array $queryTokens): Collection
    {
        return ReferenceFood::query()
            ->nutritionallyComplete()
            ->where(function (Builder $query) use ($queryTokens): void {
                foreach ($queryTokens as $token) {
                    $query->orWhere('match_name', 'like', '%'.$token.'%');
                }
            })
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  list<string>  $a
     * @param  list<string>  $b
     */
    private function jaccard(array $a, array $b): float
    {
        $a = array_unique($a);
        $b = array_unique($b);

        $intersection = count(array_intersect($a, $b));
        $union = count(array_unique([...$a, ...$b]));

        return $union === 0 ? 0.0 : $intersection / $union;
    }

    /**
     * @return list<float>|null
     */
    private function embed(string $name): ?array
    {
        try {
            $response = Embeddings::for([$name])
                ->dimensions($this->embeddingDimensions())
                ->cache()
                ->generate();

            $vector = $response->embeddings[0] ?? null;

            return is_array($vector) && $vector !== [] ? array_values($vector) : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  list<float>  $a
     * @param  list<float>  $b
     */
    private function cosine(array $a, array $b): float
    {
        $length = min(count($a), count($b));

        if ($length === 0) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $length; $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] ** 2;
            $normB += $b[$i] ** 2;
        }

        if ($normA === 0.0 || $normB === 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }

    private function threshold(): float
    {
        return config()->float('plate.food_photo_analyzer.reference_lookup.match_threshold', 0.5);
    }

    private function embeddingsEnabled(): bool
    {
        return config()->boolean('plate.food_photo_analyzer.reference_lookup.embeddings.enabled', false);
    }

    private function embeddingDimensions(): int
    {
        return config()->integer('plate.food_photo_analyzer.reference_lookup.embeddings.dimensions', 1536);
    }

    private function embeddingThreshold(): float
    {
        return config()->float('plate.food_photo_analyzer.reference_lookup.embeddings.threshold', 0.8);
    }
}
