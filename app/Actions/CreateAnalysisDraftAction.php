<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\FoodAnalysisData;
use App\Enums\AnalysisDraftSource;
use App\Models\AnalysisDraft;
use Illuminate\Support\Str;

final readonly class CreateAnalysisDraftAction
{
    public function handle(FoodAnalysisData $analysis, AnalysisDraftSource $source, ?int $claimedUserId = null): string
    {
        $token = Str::random(64);

        AnalysisDraft::query()->create([
            'token_hash' => AnalysisDraft::hashToken($token),
            'schema_version' => AnalysisDraft::SCHEMA_VERSION,
            'source' => $source,
            'payload' => $analysis->toArray(),
            'user_id' => $claimedUserId,
            'claimed_at' => $claimedUserId !== null ? now() : null,
            'expires_at' => now()->addMinutes(AnalysisDraft::TTL_MINUTES),
        ]);

        return $token;
    }
}
