<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Billing\PhotoAnalysisContext;
use App\Enums\PhotoAnalysisFailure;
use App\Models\PhotoAnalysisRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

final readonly class RetryPhotoAnalysis
{
    public function __construct(private StartPhotoAnalysis $start, private FailPhotoAnalysis $failures) {}

    public function handle(PhotoAnalysisRequest $previous, ?User $user, ?string $guestId): PhotoAnalysisRequest
    {
        $retryId = null;

        try {
            return DB::transaction(function () use ($previous, $user, $guestId, &$retryId): PhotoAnalysisRequest {
                $previous = PhotoAnalysisRequest::query()->lockForUpdate()->findOrFail($previous->id);
                abort_unless($previous->ownedBy($user, $guestId), 404);

                if ($previous->retry_request_id !== null) {
                    $retry = PhotoAnalysisRequest::query()->where('request_id', $previous->retry_request_id)->firstOrFail();
                    abort_unless($retry->ownedBy($user, $guestId), 404);

                    return $retry;
                }

                abort_unless($previous->canRetry() && $previous->image_base64 !== null, 410, 'This photo has expired or cannot be retried.');
                $requestId = (string) Str::uuid();
                $context = new PhotoAnalysisContext($user, $previous->guest_id, $requestId, $previous->source, $previous->fingerprint);
                $retry = $this->start->handle($requestId, $previous->image_base64, $previous->mime_type, $context);
                $retryId = $retry->id;
                $retry->update(['expires_at' => $previous->expires_at, 'deadline_at' => min($retry->deadline_at, $previous->expires_at)]);
                $previous->update(['retry_request_id' => $requestId, 'image_base64' => null]);

                return $retry;
            });
        } catch (Throwable $throwable) {
            $request = $retryId === null ? null : PhotoAnalysisRequest::query()->metadata()->find($retryId);
            if ($request !== null) {
                $this->failures->handle($request, PhotoAnalysisFailure::DispatchFailed);
            }

            throw $throwable;
        }
    }
}
