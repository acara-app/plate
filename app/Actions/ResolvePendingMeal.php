<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\PendingMealData;
use App\Models\AnalysisDraft;
use App\Models\User;

final readonly class ResolvePendingMeal
{
    public function handle(string $draftToken, User $user): ?PendingMealData
    {
        $draft = AnalysisDraft::query()->where('token_hash', AnalysisDraft::hashToken($draftToken))->first();

        if (! $draft instanceof AnalysisDraft || $draft->isConsumed() || ($draft->user_id !== null && $draft->user_id !== $user->id)) {
            return null;
        }

        if ($draft->isExpired()) {
            return new PendingMealData(expired: true);
        }

        return new PendingMealData(expired: false, reviewUrl: route('snap-to-track.review', ['draft' => $draftToken], absolute: false));
    }
}
