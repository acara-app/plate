<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\Billing\PhotoAnalysisContext;
use App\Enums\PhotoAnalysisStatus;
use App\Jobs\ProcessPhotoAnalysis;
use App\Models\PhotoAnalysisRequest;
use App\Models\User;
use App\Utilities\Base64Image;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final readonly class StartPhotoAnalysis
{
    public function handle(string $requestId, string $base64, string $mimeType, PhotoAnalysisContext $context): PhotoAnalysisRequest
    {
        if (mb_strlen($base64, '8bit') > 4 * (int) ceil(10 * 1024 * 1024 / 3)) {
            throw ValidationException::withMessages(['photo' => 'Choose a photo up to 10MB to analyze.']);
        }

        $image = Base64Image::decode($base64);

        if (! Str::isUuid($requestId) || $context->requestId !== $requestId || ! $image instanceof Base64Image || $image->byteLength() > 10 * 1024 * 1024 || $image->mimeType !== $mimeType || ! in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true) || ! hash_equals($context->fingerprint, hash('sha256', $base64))) {
            throw ValidationException::withMessages(['photo' => 'Choose a JPG, PNG or WebP photo up to 10MB to analyze.']);
        }

        abort_if(! $context->user instanceof User && $context->guestId === null, 422, 'A browser session or account is required.');

        $driver = config('queue.connections.'.config()->string('queue.default').'.driver');
        abort_if(! Queue::isFake() && in_array($driver, ['sync', 'null', 'deferred', 'background'], true), 503, 'Photo analysis needs a running background queue. Please try again shortly.');

        $createdId = null;

        try {
            return DB::transaction(function () use ($requestId, $base64, $mimeType, $context, &$createdId): PhotoAnalysisRequest {
                $request = PhotoAnalysisRequest::query()->firstOrCreate(['request_id' => $requestId], [
                    'user_id' => $context->user?->id,
                    'guest_hash' => $context->guestId === null ? null : hash('sha256', $context->guestId),
                    'guest_id' => $context->guestId,
                    'fingerprint' => $context->fingerprint,
                    'source' => $context->source,
                    'mime_type' => $mimeType,
                    'image_base64' => $base64,
                    'status' => PhotoAnalysisStatus::Queued,
                    'deadline_at' => now()->addMinutes(10),
                    'expires_at' => now()->addMinutes(PhotoAnalysisRequest::TTL_MINUTES),
                ]);

                abort_unless($request->ownedBy($context->user, $context->guestId), 404);
                abort_unless(hash_equals($request->fingerprint, $context->fingerprint), 409, 'This request belongs to a different photo.');
                abort_if($request->isExpired(), 410, 'This photo has expired.');

                if ($request->wasRecentlyCreated) {
                    $createdId = $request->id;
                    dispatch(new ProcessPhotoAnalysis($request->id))->afterCommit();
                }

                return $request;
            });
        } catch (Throwable $throwable) {
            if ($createdId !== null) {
                PhotoAnalysisRequest::query()->find($createdId)?->markFailed('dispatch_failed');
            }

            throw $throwable;
        }
    }

    public function retry(PhotoAnalysisRequest $previous, ?User $user, ?string $guestId): PhotoAnalysisRequest
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
                $retry = $this->handle($requestId, $previous->image_base64, $previous->mime_type, $context);
                $retryId = $retry->id;
                $retry->update(['expires_at' => $previous->expires_at, 'deadline_at' => min($retry->deadline_at, $previous->expires_at)]);
                $previous->update(['retry_request_id' => $requestId, 'image_base64' => null]);

                return $retry;
            });
        } catch (Throwable $throwable) {
            if ($retryId !== null) {
                PhotoAnalysisRequest::query()->find($retryId)?->markFailed('dispatch_failed');
            }

            throw $throwable;
        }
    }
}
