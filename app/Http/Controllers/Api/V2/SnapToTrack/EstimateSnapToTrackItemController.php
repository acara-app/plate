<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\SnapToTrack;

use App\Actions\EstimateFoodItemNutritionAction;
use App\Exceptions\Billing\UsageLimitExceededException;
use App\Http\Requests\SnapToTrack\EstimateSnapToTrackItemRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Throwable;

final readonly class EstimateSnapToTrackItemController
{
    public function __construct(private EstimateFoodItemNutritionAction $estimateFoodItemNutrition) {}

    public function __invoke(EstimateSnapToTrackItemRequest $request, #[CurrentUser] User $user): JsonResponse
    {
        try {
            $estimate = $this->estimateFoodItemNutrition->handle($user, $request->foodName(), $request->grams());
        } catch (UsageLimitExceededException $usageLimitExceededException) {
            throw $usageLimitExceededException;
        } catch (Throwable $throwable) {
            report($throwable);

            return response()->json(['error' => 'estimate_unavailable'], 503);
        }

        return response()->json($estimate->toArray());
    }
}
