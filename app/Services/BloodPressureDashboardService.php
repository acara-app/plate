<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

final readonly class BloodPressureDashboardService
{
    /**
     * @return array{systolic: int, diastolic: int, measured_at: string}|null
     */
    public function latestPair(User $user): ?array
    {
        $systolic = $user->healthSyncSamples()
            ->where('type_identifier', 'bloodPressureSystolic')
            ->whereNotNull('group_id')
            ->latest('measured_at')
            ->first();

        if ($systolic === null || $systolic->group_id === null) {
            return $this->latestPairFallback($user);
        }

        $diastolic = $user->healthSyncSamples()
            ->where('type_identifier', 'bloodPressureDiastolic')
            ->where('group_id', $systolic->group_id)
            ->first();

        if ($diastolic === null) {
            return $this->latestPairFallback($user);
        }

        return [
            'systolic' => (int) $systolic->value,
            'diastolic' => (int) $diastolic->value,
            'measured_at' => $systolic->measured_at->toIso8601String(),
        ];
    }

    /**
     * @return array{systolic: int, diastolic: int, measured_at: string}|null
     */
    private function latestPairFallback(User $user): ?array
    {
        $systolic = $user->healthSyncSamples()
            ->where('type_identifier', 'bloodPressureSystolic')
            ->latest('measured_at')
            ->first();

        if ($systolic === null) {
            return null;
        }

        $diastolic = $user->healthSyncSamples()
            ->where('type_identifier', 'bloodPressureDiastolic')
            ->whereBetween('measured_at', [
                $systolic->measured_at->copy()->subSeconds(30),
                $systolic->measured_at->copy()->addSeconds(30),
            ])
            ->first();

        if ($diastolic === null) {
            return null;
        }

        return [
            'systolic' => (int) $systolic->value,
            'diastolic' => (int) $diastolic->value,
            'measured_at' => $systolic->measured_at->toIso8601String(),
        ];
    }
}
