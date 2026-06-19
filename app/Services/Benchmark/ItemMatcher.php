<?php

declare(strict_types=1);

namespace App\Services\Benchmark;

use App\Data\Benchmark\ItemScore;
use App\Models\ReferenceFood;

final class ItemMatcher
{
    private const float MATCH_THRESHOLD = 0.5;

    /**
     * @param  list<string>  $predictedNames
     * @param  list<string>  $truthNames
     */
    public function score(array $predictedNames, array $truthNames): ItemScore
    {
        if ($truthNames === []) {
            return new ItemScore(recall: null, precision: null);
        }

        if ($predictedNames === []) {
            return new ItemScore(recall: 0.0, precision: null);
        }

        $predictedTokens = array_map($this->tokens(...), $predictedNames);
        $truthTokens = array_map($this->tokens(...), $truthNames);

        $candidates = [];

        foreach ($predictedTokens as $predictedIndex => $tokens) {
            foreach ($truthTokens as $truthIndex => $truthItemTokens) {
                $similarity = $this->overlapCoefficient($tokens, $truthItemTokens);

                if ($similarity >= self::MATCH_THRESHOLD) {
                    $candidates[] = ['predicted' => $predictedIndex, 'truth' => $truthIndex, 'similarity' => $similarity];
                }
            }
        }

        usort($candidates, fn (array $a, array $b): int => $b['similarity'] <=> $a['similarity']);

        $matchedPredicted = [];
        $matchedTruth = [];

        foreach ($candidates as $candidate) {
            if (isset($matchedPredicted[$candidate['predicted']])) {
                continue;
            }

            if (isset($matchedTruth[$candidate['truth']])) {
                continue; // @codeCoverageIgnore
            }

            $matchedPredicted[$candidate['predicted']] = true;
            $matchedTruth[$candidate['truth']] = true;
        }

        return new ItemScore(
            recall: count($matchedTruth) / count($truthNames),
            precision: count($matchedPredicted) / count($predictedNames),
        );
    }

    /**
     * @return list<string>
     */
    private function tokens(string $name): array
    {
        return array_values(array_unique(array_filter(explode(' ', ReferenceFood::normalizeName($name)))));
    }

    /**
     * @param  list<string>  $a
     * @param  list<string>  $b
     */
    private function overlapCoefficient(array $a, array $b): float
    {
        if ($a === [] || $b === []) {
            return 0.0; // @codeCoverageIgnore
        }

        return count(array_intersect($a, $b)) / min(count($a), count($b));
    }
}
