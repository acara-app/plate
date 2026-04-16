<?php

declare(strict_types=1);

namespace App\Ai\Tools\Memory;

use App\Contracts\Ai\Memory\DecayMemoriesTool;
use App\Models\Memory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class DecayMemories implements DecayMemoriesTool
{
    /**
     * @return array{
     *     decayed_count: int,
     *     archived_count: int,
     *     avg_importance_before: float,
     *     avg_importance_after: float
     * }
     */
    public function execute(
        int $ageThresholdDays = 30,
        float $decayFactor = 0.9,
        int $minImportance = 1,
        bool $archiveDecayed = true,
    ): array {
        $userId = (int) (Auth::id() ?? 0);

        $query = Memory::query()
            ->where('is_archived', false)
            ->where('is_pinned', false)
            ->where('created_at', '<', now()->subDays($ageThresholdDays));

        if ($userId > 0) {
            $query->where('user_id', $userId);
        }

        $targets = $query->get();

        if ($targets->isEmpty()) {
            return [
                'decayed_count' => 0,
                'archived_count' => 0,
                'avg_importance_before' => 0.0,
                'avg_importance_after' => 0.0,
            ];
        }

        $avgBefore = (float) $targets->avg('importance');

        $decayed = 0;
        $archived = 0;
        $newImportances = [];

        DB::transaction(function () use ($targets, $decayFactor, $minImportance, $archiveDecayed, &$decayed, &$archived, &$newImportances): void {
            foreach ($targets as $memory) {
                $newImportance = max(1, (int) floor($memory->importance * $decayFactor));

                $updates = ['importance' => $newImportance];

                if ($archiveDecayed && $newImportance < $minImportance) {
                    $updates['is_archived'] = true;
                    $archived++;
                }

                $memory->forceFill($updates)->save();
                $newImportances[] = $newImportance;
                $decayed++;
            }
        });

        $avgAfter = $newImportances === [] ? 0.0 : array_sum($newImportances) / count($newImportances);

        return [
            'decayed_count' => $decayed,
            'archived_count' => $archived,
            'avg_importance_before' => round($avgBefore, 2),
            'avg_importance_after' => round($avgAfter, 2),
        ];
    }
}
