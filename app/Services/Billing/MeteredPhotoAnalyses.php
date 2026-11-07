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
use App\Enums\SubscriptionTier;
use App\Exceptions\Billing\PhotoLimitExceeded;
use App\Models\AiUsage;
use App\Models\SubscriptionProduct;
use App\Models\User;
use App\Services\Ai\ConfiguredAgentModels;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use stdClass;
use Throwable;
use UnexpectedValueException;

final class MeteredPhotoAnalyses implements ManagesPhotoAnalyses
{
    public function enabled(): bool
    {
        return $this->premiumReady() && $this->purchaseProduct() instanceof SubscriptionProduct
            && filled(config('cashier.key')) && filled(config('cashier.secret')) && filled(config('cashier.webhook.secret'));
    }

    public function premiumReady(): bool
    {
        try {
            $model = resolve(ConfiguredAgentModels::class)->forAgent(FoodPhotoAnalyzerAgent::class, 'paid');

            return $model->supports('vision') && $model->supports('structured_output');
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    public function purchaseProduct(?string $name = null): ?SubscriptionProduct
    {
        return SubscriptionProduct::query()->where('purchasable', true)
            ->where(fn (EloquentBuilder $query): EloquentBuilder => $query
                ->where(fn (EloquentBuilder $starter): EloquentBuilder => $starter->where('name', 'Starter')
                    ->where('price', 2.99)->where('stripe_lookup_key', 'acara-plate-starter-monthly-v1'))
                ->orWhere(fn (EloquentBuilder $snap): EloquentBuilder => $snap->where('name', 'Snap Pro')
                    ->where('price', 9)->where('stripe_lookup_key', 'acara-plate-snap-pro-monthly-v1')))
            ->when($name !== null, fn (EloquentBuilder $query): EloquentBuilder => $query->where('name', $name))
            ->whereNotNull('stripe_price_id')->where('stripe_price_id', '!=', '')->orderBy('price')->first();
    }

    public function paid(User $user): bool
    {
        $entitlement = resolve(ResolvesUserTier::class)->resolve($user);

        return ! $entitlement->isUnrestricted() && ! $entitlement->isPaymentPending() && $entitlement->tier !== SubscriptionTier::Free;
    }

    public function entitlement(?User $user, ?string $guestId = null): PhotoEntitlement
    {
        if ($user instanceof User && $this->paid($user)) {
            return new PhotoEntitlement(mode: 'premium');
        }

        if (! $this->enabled()) {
            return new PhotoEntitlement;
        }

        return DB::transaction(fn (): PhotoEntitlement => $this->allowance($this->period($this->subject($user, $guestId))), 3);
    }

    public function analyze(PhotoAnalysisContext $context, Closure $analyze): FoodAnalysisData
    {
        if ($context->user instanceof User && $this->paid($context->user)) {
            abort_if(! $this->premiumReady(), 503, 'Premium analysis is temporarily unavailable.');
            $model = resolve(ResolvesAgentModel::class)->resolve(FoodPhotoAnalyzerAgent::class, $context->user);
            resolve(EnforceAiUsageLimit::class)->handle($context->user, $model);

            return $analyze(PhotoModel::fromAgentModel($model, config()->integer('snap.premium.max_tokens', 4096)));
        }

        if (! $this->enabled()) {
            return $analyze(PhotoModel::standard());
        }

        $attempt = (string) Str::uuid();
        try {
            $reservation = DB::transaction(function () use ($context, $attempt): stdClass {
                $subject = $this->subject($context->user, $context->guestId);
                $period = $this->period($subject);
                $identities = [$subject];
                if ($context->guestId !== null && DB::table('snap_photo_accounts')->where('subject', 'guest:'.$context->guestId)->where('owner', $subject)->exists()) {
                    $identities[] = 'guest:'.$context->guestId;
                }

                $existing = DB::table('snap_photo_requests')->whereIn('subject', $identities)->where('request_key', $context->requestId)->first();

                if ($existing !== null) {
                    abort_unless(hash_equals($this->text($existing, 'fingerprint'), $context->fingerprint), 409, 'This request ID belongs to a different photo.');
                    if ($existing->status === 'succeeded') {
                        abort_if($existing->result === null || CarbonImmutable::parse($this->text($existing, 'created_at'))->addDay()->isPast(), 409, 'This result has expired. Please start a new scan.');

                        return $existing;
                    }

                    abort_if($existing->status === 'pending' && CarbonImmutable::parse($this->text($existing, 'expires_at'))->isFuture(), 409, 'This photo is still being analyzed.');
                }

                $allowance = $this->allowance($period);
                throw_if($allowance->exhausted(), PhotoLimitExceeded::class, $allowance);

                $values = [
                    'period_id' => $period->id, 'subject' => $subject, 'request_key' => $context->requestId,
                    'fingerprint' => $context->fingerprint, 'guest_id' => $context->guestId,
                    'status' => 'pending', 'source' => $context->source, 'result' => null,
                    'attempt' => $attempt, 'created_at' => now('UTC'), 'expires_at' => now('UTC')->addMinutes(15),
                ];
                if ($existing === null) {
                    $id = DB::table('snap_photo_requests')->insertGetId($values);
                } else {
                    $id = $existing->id;
                    DB::table('snap_photo_requests')->where('id', $id)->update($values);
                }

                return (object) ['id' => $id, 'status' => 'pending', 'mode' => $period->mode];
            }, 3);
        } catch (PhotoLimitExceeded $photoLimitExceeded) {
            $this->record('limit:'.hash('sha256', ($context->user->id ?? $context->guestId).':'.$photoLimitExceeded->entitlement->resetsAt), 'snap_to_track_limit_hit', ['mode' => $photoLimitExceeded->entitlement->mode]);
            throw $photoLimitExceeded;
        }

        if ($reservation->status === 'succeeded') {
            return FoodAnalysisData::from(json_decode(Crypt::decryptString($this->text($reservation, 'result')), true, flags: JSON_THROW_ON_ERROR));
        }

        $reservationId = $this->integer($reservation, 'id');
        $mode = $this->text($reservation, 'mode');
        $previousGroup = Context::get('photo_usage_group');
        Context::add('photo_usage_group', 'scan:'.$reservationId);
        try {
            $result = $analyze(PhotoModel::standard());
            if ($result->items->count() === 0) {
                $this->request($reservationId, $attempt)->update(['status' => 'failed']);

                return $result;
            }

            $updated = $this->request($reservationId, $attempt)->update([
                'status' => 'succeeded', 'result' => Crypt::encryptString($result->toJson()),
            ]);
            throw_if($updated !== 1, RuntimeException::class, 'The analysis reservation expired. Please retry.');

            $cost = (float) AiUsage::query()->where('usage_group', 'scan:'.$reservationId)->sum('cost');
            if ($cost > config()->float('snap.cost_alert_usd', 0.03)) {
                Log::warning('snap_scan_cost_exceeded', ['mode' => $mode, 'cost_usd' => $cost]);
            }

            $this->record('scan:'.$reservationId, 'snap_to_track_scan_completed', ['mode' => $mode, 'source' => $context->source]);

            return $result;
        } catch (Throwable $throwable) {
            $this->request($reservationId, $attempt)->update(['status' => 'failed', 'result' => null]);
            throw $throwable;
        } finally {
            $previousGroup === null ? Context::forget('photo_usage_group') : Context::add('photo_usage_group', $previousGroup);
        }
    }

    private function request(int $id, string $attempt): Builder
    {
        return DB::table('snap_photo_requests')->where('id', $id)->where('attempt', $attempt)->where('status', 'pending')->where('expires_at', '>', now('UTC'));
    }

    private function subject(?User $user, ?string $guestId): string
    {
        abort_if(! $user instanceof User && $guestId === null, 422, 'A browser session or account is required.');
        $guestSubject = $guestId === null ? null : 'guest:'.$guestId;
        $userSubject = $user instanceof User ? 'user:'.$user->id : null;
        $subjects = array_filter([$guestSubject, $userSubject]);
        sort($subjects);
        foreach ($subjects as $subject) {
            DB::table('snap_photo_accounts')->insertOrIgnore(['subject' => $subject]);
        }

        $accounts = DB::table('snap_photo_accounts')->whereIn('subject', $subjects)->orderBy('subject')->lockForUpdate()->get()->keyBy('subject');
        $owner = $guestSubject === null ? null : $this->nullableText($accounts->sole('subject', $guestSubject), 'owner');
        if ($userSubject !== null && $guestSubject !== null && $owner === null) {
            DB::table('snap_photo_accounts')->where('subject', $guestSubject)->update(['owner' => $userSubject]);
            $guestPeriod = $this->period($guestSubject);
            $userPeriod = $this->period($userSubject);
            DB::table('snap_photo_requests')->where('period_id', $guestPeriod->id)->update(['period_id' => $userPeriod->id]);
            $owner = $userSubject;
        }

        $subject = $userSubject ?? $owner ?? $guestSubject;
        if (! $accounts->has($subject)) {
            DB::table('snap_photo_accounts')->where('subject', $subject)->lockForUpdate()->first();
        }

        return $subject;
    }

    private function period(string $subject): stdClass
    {
        $start = CarbonImmutable::create(2000, 1, 1, 0, 0, 0, 'UTC');
        DB::table('snap_photo_periods')->insertOrIgnore([
            'subject' => $subject, 'starts_at' => $start, 'ends_at' => null, 'mode' => 'trial',
            'allowance' => config()->integer('snap.free_trial_scans', 2),
        ]);

        return DB::table('snap_photo_periods')->where('subject', $subject)->where('starts_at', $start)->where('mode', 'trial')->lockForUpdate()->sole();
    }

    private function allowance(stdClass $period): PhotoEntitlement
    {
        $used = DB::table('snap_photo_requests')->where('period_id', $period->id)
            ->where(fn (Builder $query): Builder => $query->where('status', 'succeeded')
                ->orWhere(fn (Builder $pending): Builder => $pending->where('status', 'pending')->where('expires_at', '>', now('UTC'))))->count();

        $product = $this->enabled() ? $this->purchaseProduct() : null;
        $offer = $product instanceof SubscriptionProduct ? new PhotoOffer($product->id, $product->name, $product->formatted_price) : null;

        $mode = $this->text($period, 'mode');
        $endsAt = $this->nullableText($period, 'ends_at');

        return new PhotoEntitlement(
            true, $this->integer($period, 'allowance'), $used,
            $endsAt === null ? null : CarbonImmutable::parse($endsAt, 'UTC')->toIso8601String(),
            $mode, $mode === 'trial' && $offer instanceof PhotoOffer, $offer,
        );
    }

    /** @param array<string, string> $properties */
    private function record(string $key, string $name, array $properties): void
    {
        DB::table('snap_analytics_events')->insertOrIgnore([
            'event_key' => $key, 'name' => $name, 'properties' => json_encode($properties, JSON_THROW_ON_ERROR), 'created_at' => now('UTC'),
        ]);
    }

    private function text(stdClass $row, string $column): string
    {
        $value = $row->{$column};

        return is_string($value) ? $value : throw new UnexpectedValueException("Expected the {$column} column to hold text.");
    }

    private function nullableText(stdClass $row, string $column): ?string
    {
        return $row->{$column} === null ? null : $this->text($row, $column);
    }

    private function integer(stdClass $row, string $column): int
    {
        $value = $row->{$column};

        return is_int($value) ? $value : throw new UnexpectedValueException("Expected the {$column} column to hold an integer.");
    }
}
