<?php

declare(strict_types=1);

namespace App\Models;

use App\Actions\CreateAnalysisDraftAction;
use App\Contracts\Billing\ResolvesFailedPhotoAnalysis;
use App\Data\Billing\PhotoAnalysisContext;
use App\Enums\AnalysisDraftSource;
use App\Enums\PhotoAnalysisStatus;
use Carbon\CarbonInterface;
use Database\Factories\PhotoAnalysisRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @property int $id
 * @property string $request_id
 * @property string|null $retry_request_id
 * @property int|null $user_id
 * @property string|null $guest_hash
 * @property string|null $guest_id
 * @property string $fingerprint
 * @property string $source
 * @property string $mime_type
 * @property string|null $image_base64
 * @property string|null $draft_token
 * @property PhotoAnalysisStatus $status
 * @property string|null $failure_code
 * @property CarbonInterface $deadline_at
 * @property CarbonInterface $expires_at
 * @property CarbonInterface|null $started_at
 * @property CarbonInterface|null $completed_at
 * @property CarbonInterface|null $failed_at
 * @property CarbonInterface $created_at
 */
#[Hidden(['image_base64', 'guest_id', 'draft_token'])]
final class PhotoAnalysisRequest extends Model
{
    /** @use HasFactory<PhotoAnalysisRequestFactory> */
    use HasFactory;

    use MassPrunable;

    public const int TTL_MINUTES = 60;

    /** @var list<string> */
    protected $guarded = [];

    public function ownedBy(?User $user, ?string $guestId): bool
    {
        if ($this->user_id !== null) {
            return $user?->id === $this->user_id;
        }

        return $guestId !== null && $this->guest_hash !== null && hash_equals($this->guest_hash, hash('sha256', $guestId));
    }

    public function isPending(): bool
    {
        return in_array($this->status, [PhotoAnalysisStatus::Queued, PhotoAnalysisStatus::Processing], true);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function hasFailed(): bool
    {
        return $this->status === PhotoAnalysisStatus::Failed;
    }

    public function canRetry(): bool
    {
        return $this->hasFailed() && ! $this->isExpired();
    }

    public function failureMessage(): string
    {
        return match ($this->failure_code) {
            'no_food' => 'No food items were detected. Try a clearer photo with better lighting.',
            'expired' => 'This photo has expired. Choose a photo to start a new scan.',
            'limit_reached' => 'Your scan allowance is used up. Choose a plan to continue.',
            'timeout', 'queue_timeout', 'interrupted' => 'This analysis could not finish. Your photo is ready to retry without uploading again.',
            default => 'This analysis could not finish. Please try again.',
        };
    }

    public function context(): PhotoAnalysisContext
    {
        return new PhotoAnalysisContext(
            $this->user_id === null ? null : User::query()->find($this->user_id),
            $this->guest_id,
            $this->request_id,
            $this->source,
            $this->fingerprint,
        );
    }

    public function markFailed(string $code, bool $onlyIfStale = false): bool
    {
        $updated = DB::transaction(function () use ($code, $onlyIfStale): bool {
            $current = self::query()->lockForUpdate()->find($this->id);

            if ($current === null || ! $current->isPending()) {
                return false;
            }

            if ($onlyIfStale && ! $current->isExpired() && $current->deadline_at->isFuture()) {
                return false;
            }

            $context = $current->context();
            $result = resolve(ResolvesFailedPhotoAnalysis::class)->handle($context);

            if ($result !== null && $result->items->count() > 0 && ! $current->isExpired()) {
                $token = resolve(CreateAnalysisDraftAction::class)->handle($result, AnalysisDraftSource::PublicSnapToTrack, $context->user?->id);
                $current->update(['status' => PhotoAnalysisStatus::Succeeded, 'draft_token' => $token, 'image_base64' => null, 'completed_at' => now()]);

                return true;
            }

            $current->update([
                'status' => PhotoAnalysisStatus::Failed,
                'failure_code' => $code,
                'failed_at' => now(),
                ...$current->isExpired() ? ['image_base64' => null, 'draft_token' => null] : [],
            ]);

            return true;
        });

        if (! $updated) {
            return false;
        }

        $this->refresh();
        $this->recordOutcome();

        return true;
    }

    public function recoverIfStale(): bool
    {
        if (! $this->isPending() || (! $this->isExpired() && $this->deadline_at->isFuture())) {
            return false;
        }

        $code = $this->isExpired() ? 'expired' : ($this->status === PhotoAnalysisStatus::Queued ? 'queue_timeout' : 'timeout');

        return $this->markFailed($code, onlyIfStale: true);
    }

    public function recordOutcome(): void
    {
        Log::info('photo_analysis_finished', [
            'request_id' => $this->request_id,
            'source' => $this->source,
            'outcome' => $this->status->value,
            'failure_code' => $this->failure_code,
            'total_ms' => max(0, (int) $this->created_at->diffInMilliseconds(now())),
            'processing_ms' => $this->started_at === null ? null : max(0, (int) $this->started_at->diffInMilliseconds(now())),
        ]);
    }

    /** @return Builder<self> */
    public function prunable(): Builder
    {
        return self::query()->whereNowOrPast('expires_at');
    }

    /** @return array<string, string> */
    public function casts(): array
    {
        return [
            'user_id' => 'integer',
            'guest_id' => 'encrypted',
            'image_base64' => 'encrypted',
            'draft_token' => 'encrypted',
            'status' => PhotoAnalysisStatus::class,
            'deadline_at' => 'datetime',
            'expires_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    /** @param Builder<self> $query */
    #[Scope]
    protected function metadata(Builder $query): void
    {
        $query->select([
            'id', 'request_id', 'retry_request_id', 'user_id', 'guest_hash', 'guest_id', 'fingerprint', 'source', 'mime_type',
            'draft_token', 'status', 'failure_code', 'deadline_at', 'expires_at', 'started_at', 'completed_at',
            'failed_at', 'created_at', 'updated_at',
        ]);
    }
}
