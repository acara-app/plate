<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Data\Billing\PhotoAnalysisContext;
use App\Enums\PhotoAnalysisStatus;
use App\Enums\PhotoPlan;
use App\Enums\SnapFunnelStage;
use App\Models\PhotoAnalysisRequest;
use App\Models\SnapAnalyticsEvent;
use App\Models\SnapJourney;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final readonly class SnapFunnel
{
    public function __construct(private PhotoPlanCatalog $catalog) {}

    public static function plan(?string $productName): ?string
    {
        if ($productName === null) {
            return null;
        }

        return PhotoPlan::fromProductName($productName)->value ?? Str::slug($productName);
    }

    /** @param array<string, bool|int|string|null> $properties */
    public function record(SnapFunnelStage $stage, string $key = '', array $properties = [], ?User $user = null): void
    {
        rescue(function () use ($stage, $key, $properties, $user): void {
            $user ??= $this->currentUser();

            $this->recordFor($user, $this->currentGuestHash(), $stage, $key, $properties, startsJourney: ! $user instanceof User);
        });
    }

    public function recordAnalysisOutcome(PhotoAnalysisRequest $request): void
    {
        rescue(function () use ($request): void {
            $failed = $request->status === PhotoAnalysisStatus::Failed;

            $this->recordFor(
                $request->user,
                $request->guest_hash,
                $failed ? SnapFunnelStage::AnalysisFailed : SnapFunnelStage::AnalysisSucceeded,
                $request->request_id,
                $failed ? ['failure' => $request->failure_code?->value] : [],
                startsJourney: false,
            );
        });
    }

    public function recordOffer(string $key, string $placement, ?string $productName, bool $clicked = false): void
    {
        $offer = ['placement' => $placement, 'plan' => self::plan($productName)];
        $this->record(SnapFunnelStage::OfferViewed, $key, $offer);

        if ($clicked) {
            $this->record(SnapFunnelStage::UpgradeClicked, $key, $offer);
        }
    }

    /** @param array<string, mixed> $invoice */
    public function recordFirstPayment(array $invoice, bool $live): void
    {
        rescue(function () use ($invoice, $live): void {
            $this->writeFirstPayment($invoice, $live);
        });
    }

    /** @return array<string, string> */
    public function checkoutMetadata(User $user): array
    {
        return rescue(function () use ($user): array {
            $journey = $this->openJourney($user, $this->currentGuestHash());

            return $journey instanceof SnapJourney ? ['snap_journey' => $journey->id] : [];
        }, []);
    }

    /** @param array<string, mixed> $invoice */
    private function writeFirstPayment(array $invoice, bool $live): void
    {
        $subscription = data_get($invoice, 'parent.subscription_details.subscription', data_get($invoice, 'subscription'));
        $amount = data_get($invoice, 'amount_paid');

        if (! is_string($subscription) || ! is_int($amount) || $amount <= 0 || data_get($invoice, 'status') !== 'paid' || data_get($invoice, 'billing_reason') !== 'subscription_create') {
            return;
        }

        $customer = data_get($invoice, 'customer');

        if (! is_string($customer) || ! User::query()->where('stripe_id', $customer)->exists()) {
            return;
        }

        $metadata = data_get($invoice, 'parent.subscription_details.metadata', data_get($invoice, 'subscription_details.metadata'));
        $metadata = is_array($metadata) ? $metadata : [];

        $journeyId = $metadata['snap_journey'] ?? null;
        $journey = is_string($journeyId) && Str::isUuid($journeyId) ? SnapJourney::query()->find($journeyId) : null;
        $plan = $metadata['product_name'] ?? null;
        $currency = data_get($invoice, 'currency');

        $this->write($journey?->id, SnapFunnelStage::FirstPayment, SnapFunnelStage::FirstPayment->value.":{$subscription}", [
            'plan' => self::plan(is_string($plan) ? $plan : null),
            'amount' => $amount,
            'currency' => is_string($currency) ? $currency : null,
            'live' => $live,
        ]);
    }

    /** @param array<string, bool|int|string|null> $properties */
    private function recordFor(?User $user, ?string $guestHash, SnapFunnelStage $stage, string $key, array $properties, bool $startsJourney): void
    {
        if (! $this->catalog->isSelling()) {
            return;
        }

        $journey = $this->openJourney($user, $guestHash);

        if (! $journey instanceof SnapJourney && $startsJourney && $guestHash !== null) {
            $journey = $this->startJourney($guestHash);
        }

        if (! $journey instanceof SnapJourney) {
            return;
        }

        if ($user instanceof User && $journey->user_id === null) {
            $journey->update(['user_id' => $user->id, 'internal' => $journey->internal || $this->isStaff($user)]);
        }

        $this->write($journey->id, $stage, "{$stage->value}:{$journey->id}:{$key}", $properties);
    }

    private function openJourney(?User $user, ?string $guestHash): ?SnapJourney
    {
        $userId = $user?->id;

        if ($guestHash !== null) {
            $journey = SnapJourney::query()->where('guest_hash', $guestHash)->whereFuture('expires_at')
                ->when($userId !== null, fn (Builder $query): Builder => $query->where(
                    fn (Builder $owner): Builder => $owner->whereNull('user_id')->orWhere('user_id', $userId),
                ))
                ->latest('started_at')->latest('id')->first();

            if ($journey instanceof SnapJourney) {
                return $journey;
            }
        }

        if ($userId === null) {
            return null;
        }

        return SnapJourney::query()->where('user_id', $userId)->whereFuture('expires_at')->latest('started_at')->latest('id')->first();
    }

    private function startJourney(string $guestHash): SnapJourney
    {
        $acquisition = session('acquisition');
        $acquired = fn (string $key): ?string => is_array($acquisition) && is_string($acquisition[$key] ?? null) ? $acquisition[$key] : null;

        return SnapJourney::query()->create([
            'guest_hash' => $guestHash,
            'device' => $acquired('device') ?? 'unknown',
            'acquisition_source' => $acquired('source') ?? 'direct',
            'acquisition_medium' => $acquired('medium'),
            'acquisition_campaign' => $acquired('campaign'),
            'started_at' => now(),
            'expires_at' => now()->addDays(config()->integer('snap.journey_days')),
        ]);
    }

    private function currentUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    private function currentGuestHash(): ?string
    {
        $guestId = PhotoAnalysisContext::guestId(request()) ?? session('snap_to_track.analysis_guest_id');

        return is_string($guestId) ? hash('sha256', $guestId) : null;
    }

    private function isStaff(User $user): bool
    {
        return in_array($user->email, config()->array('sponsors.admin_emails'), true);
    }

    /** @param array<string, bool|int|string|null> $properties */
    private function write(?string $journeyId, SnapFunnelStage $stage, string $eventKey, array $properties): void
    {
        SnapAnalyticsEvent::query()->insertOrIgnore([
            'journey_id' => $journeyId,
            'event_key' => $eventKey,
            'name' => $stage->value,
            'properties' => json_encode($properties, JSON_THROW_ON_ERROR),
            'created_at' => now('UTC'),
        ]);
    }
}
