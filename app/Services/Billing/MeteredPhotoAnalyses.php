<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Actions\Billing\EnforceAiUsageLimit;
use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Contracts\Ai\ResolvesAgentModel;
use App\Contracts\Billing\ManagesPhotoAnalyses;
use App\Contracts\Billing\ResolvesUserTier;
use App\Data\Billing\PhotoAnalysisContext;
use App\Data\Billing\PhotoEntitlement;
use App\Data\Billing\PhotoModel;
use App\Data\Billing\PhotoOffer;
use App\Data\FoodAnalysisData;
use App\Exceptions\Billing\PhotoLimitExceeded;
use App\Models\AiUsage;
use App\Models\SnapAnalyticsEvent;
use App\Models\SnapPhotoAccount;
use App\Models\SnapPhotoPeriod;
use App\Models\SnapPhotoRequest;
use App\Models\SubscriptionProduct;
use App\Models\User;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final readonly class MeteredPhotoAnalyses implements ManagesPhotoAnalyses
{
    public function __construct(private PhotoPlanCatalog $catalog) {}

    public function enabled(): bool
    {
        return $this->catalog->isSelling();
    }

    public function isPaying(User $user): bool
    {
        return resolve(ResolvesUserTier::class)->resolve($user)->isPaying();
    }

    public function entitlement(?User $user, ?string $guestId = null): PhotoEntitlement
    {
        if ($user instanceof User && $this->isPaying($user)) {
            return new PhotoEntitlement(mode: 'premium');
        }

        if (! $this->enabled()) {
            return new PhotoEntitlement;
        }

        if (! $user instanceof User && $guestId !== null && ! SnapPhotoAccount::query()->whereKey("guest:{$guestId}")->exists()) {
            return $this->meteredEntitlement(config()->integer('snap.free_trial_scans', 2), 0, null, 'trial');
        }

        return DB::transaction(fn (): PhotoEntitlement => $this->allowance($this->period($this->subject($user, $guestId))), 3);
    }

    public function analyze(PhotoAnalysisContext $context, Closure $analyze): FoodAnalysisData
    {
        if ($context->user instanceof User && $this->isPaying($context->user)) {
            abort_if(! $this->catalog->hasReadyPaidModel(), 503, 'Premium analysis is temporarily unavailable.');
            $model = resolve(ResolvesAgentModel::class)->resolve(FoodPhotoAnalyzerAgent::class, $context->user);
            resolve(EnforceAiUsageLimit::class)->handle($context->user, $model);

            return $analyze(PhotoModel::fromAgentModel($model, config()->integer('snap.premium.max_tokens', 4096)));
        }

        if (! $this->enabled()) {
            return $analyze(PhotoModel::standard());
        }

        $attempt = (string) Str::uuid();
        $mode = 'trial';

        try {
            $reservation = DB::transaction(function () use ($context, $attempt, &$mode): SnapPhotoRequest {
                $subject = $this->subject($context->user, $context->guestId);
                $period = $this->period($subject);
                $mode = $period->mode;
                $identities = [$subject];
                if ($context->guestId !== null && SnapPhotoAccount::query()->whereKey("guest:{$context->guestId}")->where('owner', $subject)->exists()) {
                    $identities[] = "guest:{$context->guestId}";
                }

                $existing = SnapPhotoRequest::query()->whereIn('subject', $identities)->where('request_key', $context->requestId)->first();

                if ($existing instanceof SnapPhotoRequest) {
                    abort_unless(hash_equals($existing->fingerprint, $context->fingerprint), 409, 'This request ID belongs to a different photo.');
                    if ($existing->status === 'succeeded') {
                        abort_if($existing->result === null || $existing->created_at->addDay()->isPast(), 409, 'This result has expired. Please start a new scan.');

                        return $existing;
                    }

                    abort_if($existing->status === 'pending' && $existing->expires_at->isFuture(), 409, 'This photo is still being analyzed.');
                }

                $allowance = $this->allowance($period);
                throw_if($allowance->exhausted(), PhotoLimitExceeded::class, $allowance);

                $reservation = $existing ?? new SnapPhotoRequest;
                $reservation->fill([
                    'period_id' => $period->id, 'subject' => $subject, 'request_key' => $context->requestId,
                    'fingerprint' => $context->fingerprint, 'guest_id' => $context->guestId,
                    'status' => 'pending', 'source' => $context->source, 'result' => null,
                    'attempt' => $attempt, 'created_at' => now('UTC'), 'expires_at' => now('UTC')->addMinutes(15),
                ])->save();

                return $reservation;
            }, 3);
        } catch (PhotoLimitExceeded $photoLimitExceeded) {
            $this->record('limit:'.hash('sha256', ($context->user->id ?? $context->guestId).':'.$photoLimitExceeded->entitlement->resetsAt), 'snap_to_track_limit_hit', ['mode' => $photoLimitExceeded->entitlement->mode]);
            throw $photoLimitExceeded;
        }

        if ($reservation->status === 'succeeded') {
            return FoodAnalysisData::from(json_decode($reservation->result ?? '', true, flags: JSON_THROW_ON_ERROR));
        }

        $previousGroup = Context::get('photo_usage_group');
        Context::add('photo_usage_group', "scan:{$reservation->id}");
        try {
            $result = $analyze(PhotoModel::standard());
            if ($result->items->count() === 0) {
                $this->pendingReservation($reservation, $attempt)->update(['status' => 'failed']);

                return $result;
            }

            $updated = $this->pendingReservation($reservation, $attempt)->update([
                'status' => 'succeeded', 'result' => Crypt::encryptString($result->toJson()),
            ]);
            throw_if($updated !== 1, RuntimeException::class, 'The analysis reservation expired. Please retry.');

            $cost = (float) AiUsage::query()->where('usage_group', "scan:{$reservation->id}")->sum('cost');
            if ($cost > $this->costAlertThreshold()) {
                Log::warning('snap_scan_cost_exceeded', ['mode' => $mode, 'cost_usd' => $cost]);
            }

            $this->record("scan:{$reservation->id}", 'snap_to_track_scan_completed', ['mode' => $mode, 'source' => $context->source]);

            return $result;
        } catch (Throwable $throwable) {
            $this->pendingReservation($reservation, $attempt)->update(['status' => 'failed', 'result' => null]);
            throw $throwable;
        } finally {
            $previousGroup === null ? Context::forget('photo_usage_group') : Context::add('photo_usage_group', $previousGroup);
        }
    }

    /** @return Builder<SnapPhotoRequest> */
    private function pendingReservation(SnapPhotoRequest $reservation, string $attempt): Builder
    {
        return SnapPhotoRequest::query()->whereKey($reservation->id)->where('attempt', $attempt)
            ->where('status', 'pending')->where('expires_at', '>', now('UTC'));
    }

    private function subject(?User $user, ?string $guestId): string
    {
        abort_if(! $user instanceof User && $guestId === null, 422, 'A browser session or account is required.');
        $guestSubject = $guestId === null ? null : "guest:{$guestId}";
        $userSubject = $user instanceof User ? "user:{$user->id}" : null;
        $subjects = array_filter([$guestSubject, $userSubject]);
        sort($subjects);
        foreach ($subjects as $subject) {
            SnapPhotoAccount::query()->insertOrIgnore(['subject' => $subject]);
        }

        $accounts = SnapPhotoAccount::query()->whereIn('subject', $subjects)->orderBy('subject')->lockForUpdate()->get()->keyBy('subject');
        $owner = $guestSubject === null ? null : $accounts->get($guestSubject)?->owner;
        if ($userSubject !== null && $guestSubject !== null && $owner === null) {
            SnapPhotoAccount::query()->whereKey($guestSubject)->update(['owner' => $userSubject]);
            $guestPeriod = $this->period($guestSubject);
            $userPeriod = $this->period($userSubject);
            SnapPhotoRequest::query()->where('period_id', $guestPeriod->id)->update(['period_id' => $userPeriod->id]);
            $owner = $userSubject;
        }

        $subject = $userSubject ?? $owner ?? $guestSubject;
        if (! $accounts->has($subject)) {
            SnapPhotoAccount::query()->whereKey($subject)->lockForUpdate()->first();
        }

        return $subject;
    }

    private function period(string $subject): SnapPhotoPeriod
    {
        $start = CarbonImmutable::create(2000, 1, 1, 0, 0, 0, 'UTC');
        SnapPhotoPeriod::query()->insertOrIgnore([
            'subject' => $subject, 'starts_at' => $start, 'ends_at' => null, 'mode' => 'trial',
            'allowance' => config()->integer('snap.free_trial_scans', 2),
        ]);

        return SnapPhotoPeriod::query()->where('subject', $subject)->where('starts_at', $start)->where('mode', 'trial')->lockForUpdate()->sole();
    }

    private function allowance(SnapPhotoPeriod $period): PhotoEntitlement
    {
        $used = SnapPhotoRequest::query()->where('period_id', $period->id)
            ->where(fn (Builder $query): Builder => $query->where('status', 'succeeded')
                ->orWhere(fn (Builder $pending): Builder => $pending->where('status', 'pending')->where('expires_at', '>', now('UTC'))))
            ->count();

        return $this->meteredEntitlement($period->allowance, $used, $period->ends_at?->toIso8601String(), $period->mode);
    }

    private function meteredEntitlement(int $limit, int $used, ?string $resetsAt, string $mode): PhotoEntitlement
    {
        $product = $this->catalog->product();
        $offer = $product instanceof SubscriptionProduct ? new PhotoOffer($product->id, $product->name, $product->formatted_price) : null;

        return new PhotoEntitlement(true, $limit, $used, $resetsAt, $mode, $mode === 'trial' && $offer instanceof PhotoOffer, $offer);
    }

    private function costAlertThreshold(): float
    {
        $threshold = config('snap.cost_alert_usd', 0.03);

        return is_numeric($threshold) ? (float) $threshold : 0.03;
    }

    /** @param array<string, string> $properties */
    private function record(string $key, string $name, array $properties): void
    {
        SnapAnalyticsEvent::query()->insertOrIgnore([
            'event_key' => $key, 'name' => $name, 'properties' => json_encode($properties, JSON_THROW_ON_ERROR), 'created_at' => now('UTC'),
        ]);
    }
}
