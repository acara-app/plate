<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\SnapToTrack;

use App\Actions\LogReviewedMealAction;
use App\Data\ReviewedMealData;
use App\Enums\HealthEntrySource;
use App\Http\Requests\Api\V2\SnapToTrack\StoreSnapToTrackMealRequest;
use App\Models\AnalysisDraft;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

final readonly class StoreSnapToTrackMealController
{
    public function __construct(private LogReviewedMealAction $logReviewedMeal) {}

    public function __invoke(
        StoreSnapToTrackMealRequest $request,
        #[CurrentUser] User $user,
        string $draft,
    ): JsonResponse {
        $draftModel = AnalysisDraft::query()
            ->where('token_hash', AnalysisDraft::hashToken($draft))
            ->where('user_id', $user->id)
            ->where('schema_version', AnalysisDraft::SCHEMA_VERSION)
            ->first();

        if (! $draftModel instanceof AnalysisDraft) {
            return $this->unavailable('invalid', 404);
        }

        $replay = $draftModel->isConsumed();
        $meal = ReviewedMealData::fromValidated($request->validated());

        try {
            $groupId = $this->logReviewedMeal->handle(
                $draftModel,
                $meal->toHealthLogData($draftModel),
                $user,
                HealthEntrySource::Mobile,
            );
        } catch (InvalidArgumentException) {
            return $this->unavailable('consumed', 409);
        }

        return response()->json([
            'group_id' => $groupId,
            'measured_at' => $meal->measuredAt->toIso8601String(),
            'totals' => [
                'calories' => $meal->totalOf('calories'),
                'protein' => $meal->totalOf('protein'),
                'carbs' => $meal->totalOf('carbs'),
                'fat' => $meal->totalOf('fat'),
            ],
        ], $replay ? 200 : 201);
    }

    private function unavailable(string $reason, int $status): JsonResponse
    {
        return response()->json([
            'error' => 'draft_unavailable',
            'reason' => $reason,
        ], $status);
    }
}
