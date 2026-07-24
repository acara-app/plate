<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\AnalysisDraftResolutionData;
use App\Enums\AnalysisDraftStatus;
use App\Models\AnalysisDraft;
use App\Models\User;
use Illuminate\Contracts\Database\Query\Builder;

final readonly class RestoreAnalysisDraftAction
{
    public function handle(string $token, User $user): AnalysisDraftResolutionData
    {
        $draft = AnalysisDraft::query()
            ->where('token_hash', AnalysisDraft::hashToken($token))
            ->first();

        if (! $draft instanceof AnalysisDraft || $draft->schema_version !== AnalysisDraft::SCHEMA_VERSION) {
            return new AnalysisDraftResolutionData(AnalysisDraftStatus::Invalid);
        }

        if ($draft->user_id !== null && $draft->user_id !== $user->id) {
            return new AnalysisDraftResolutionData(AnalysisDraftStatus::ClaimedByOther);
        }

        if ($draft->isConsumed()) {
            return new AnalysisDraftResolutionData(AnalysisDraftStatus::Consumed, $draft);
        }

        if ($draft->isExpired()) {
            return new AnalysisDraftResolutionData(AnalysisDraftStatus::Expired, $draft);
        }

        $claimed = AnalysisDraft::query()
            ->whereKey($draft->id)
            ->whereNull('consumed_at')
            ->where(function (Builder $query) use ($user): void {
                $query->whereNull('user_id')->orWhere('user_id', $user->id);
            })
            ->update([
                'user_id' => $user->id,
                'claimed_at' => $draft->claimed_at ?? now(),
            ]);

        if ($claimed === 0) {
            return new AnalysisDraftResolutionData(AnalysisDraftStatus::ClaimedByOther);
        }

        return new AnalysisDraftResolutionData(AnalysisDraftStatus::Restored, $draft->refresh());
    }
}
