<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Data\Billing\SnapFunnelReportData;
use App\Enums\SnapFunnelStage;
use App\Models\SnapAnalyticsEvent;
use App\Models\SnapJourney;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Cashier\Subscription;

final readonly class BuildSnapFunnelReport
{
    /** @var array<string, list<SnapFunnelStage>> */
    public const array PATHS = [
        'scan' => [
            SnapFunnelStage::UploadSelected,
            SnapFunnelStage::AnalysisRequested,
            SnapFunnelStage::ResultViewed,
            SnapFunnelStage::OfferViewed,
        ],
        'save' => [
            SnapFunnelStage::ResultViewed,
            SnapFunnelStage::SaveClicked,
            SnapFunnelStage::AuthCompleted,
            SnapFunnelStage::MealSaved,
        ],
        'upgrade' => [
            SnapFunnelStage::OfferViewed,
            SnapFunnelStage::UpgradeClicked,
            SnapFunnelStage::AuthCompleted,
            SnapFunnelStage::CheckoutClicked,
            SnapFunnelStage::CheckoutStarted,
            SnapFunnelStage::FirstPayment,
        ],
    ];

    /** @var list<string> */
    public const array SEGMENTS = ['device', 'source', 'placement', 'plan'];

    /** @var list<string> */
    private const array HISTORY = [
        '2026-11-07: plan-allowance-copy-v1 explains everyday use, total free scans and shared rolling allowances in English, French and Mongolian. Use the deployment timestamp for before/after cohorts; prices, allowances and funnel event meanings are unchanged. Compare device, source, placement and plan mix, and treat differences as observational.',
        '2026-11-07: buyers can pay before verifying their email. The chosen plan and billing interval survive sign-up and log-in, repeat checkout clicks reopen the same Stripe session, and a waiting meal opens once the plan is active.',
        '2026-11-07: the guest result page added a Save button to the meal card, and its mobile action bar now leads with saving, with the plan offer as a link beneath it. Umami save clicks carry a placement from this date.',
        '2026-11-04: guest results moved to /tools/snap-to-track/result, so Umami result and offer counts before and after this date come from different pages.',
        '2026-10-24: Umami events changed meaning. snap_to_track_upload_click became snap_to_track_upload_selected (a photo was chosen), snap_to_track_payment_verified became snap_to_track_checkout_returned (a browser return, not a payment), result and offer views became on-screen and de-duplicated, offer and upgrade events dropped the exhausted property, and save clicks stopped also sending snap_to_track_auth_started. Umami totals across this date are not comparable.',
    ];

    public function handle(CarbonImmutable $from, CarbonImmutable $to, bool $includeInternal = false, ?string $segmentBy = null): SnapFunnelReportData
    {
        $journeys = SnapJourney::query()
            ->with('events')
            ->whereBetween('started_at', [$from, $to])
            ->unless($includeInternal, fn (Builder $query): Builder => $query->where('internal', false))
            ->oldest('started_at')
            ->get();

        /** @var Collection<string, Collection<int, SnapAnalyticsEvent>> $timelines */
        $timelines = $journeys->mapWithKeys(fn (SnapJourney $journey): array => [
            $journey->id => $journey->events
                ->reject(fn (SnapAnalyticsEvent $event): bool => ! $includeInternal && $this->isTestPayment($event))
                ->sortBy([['created_at', 'asc'], ['id', 'asc']])
                ->values()
                ->toBase(),
        ]);

        $ledgerStart = SnapJourney::query()->min('started_at');
        $ledgerStartedAt = is_string($ledgerStart) ? CarbonImmutable::parse($ledgerStart) : null;

        return new SnapFunnelReportData(
            from: $from->toIso8601String(),
            to: $to->toIso8601String(),
            windowDays: config()->integer('snap.journey_days'),
            includesInternal: $includeInternal,
            journeys: $journeys->count(),
            openJourneys: $journeys->filter(fn (SnapJourney $journey): bool => $journey->expires_at->isFuture())->count(),
            ledgerStartedAt: $ledgerStartedAt?->toIso8601String(),
            paths: $this->paths($timelines->values()),
            segmentBy: $segmentBy,
            segments: $segmentBy === null ? [] : $this->segments($journeys->toBase(), $timelines, $segmentBy),
            problems: $this->problems($timelines->values()),
            rawEvents: $this->tally($timelines->flatMap(fn (Collection $events): array => $events->map(fn (SnapAnalyticsEvent $event): string => $event->name)->all())),
            payments: $this->payments($from, $to),
            notes: [
                $ledgerStartedAt instanceof CarbonImmutable
                    ? "{$ledgerStartedAt->toDateString()}: the journey ledger starts. Journeys and confirmed first payments before this date were never recorded and are not estimated."
                    : 'No journeys have been recorded yet.',
                ...self::HISTORY,
            ],
        );
    }

    /**
     * @param  Collection<int, Collection<int, SnapAnalyticsEvent>>  $timelines
     * @return array<string, list<array{stage: string, label: string, journeys: int}>>
     */
    private function paths(Collection $timelines): array
    {
        $paths = [];

        foreach (self::PATHS as $name => $stages) {
            $reached = $timelines->map(fn (Collection $events): int => $this->reachedSteps($events, $stages));

            $paths[$name] = array_map(fn (SnapFunnelStage $stage, int $step): array => [
                'stage' => $stage->value,
                'label' => $stage->label(),
                'journeys' => $reached->filter(fn (int $steps): bool => $steps > $step)->count(),
            ], $stages, array_keys($stages));
        }

        return $paths;
    }

    /**
     * @param  Collection<int, SnapAnalyticsEvent>  $events
     * @param  list<SnapFunnelStage>  $stages
     */
    private function reachedSteps(Collection $events, array $stages): int
    {
        $reachedAt = null;

        foreach ($stages as $step => $stage) {
            $event = $events->first(fn (SnapAnalyticsEvent $event): bool => $event->name === $stage->value
                && (! $reachedAt instanceof CarbonImmutable || $event->created_at->greaterThanOrEqualTo($reachedAt)));

            if ($event instanceof SnapAnalyticsEvent) {
                $reachedAt = $event->created_at;

                continue;
            }

            if ($stage !== SnapFunnelStage::AuthCompleted || ! $this->has($events, $stage)) {
                return $step;
            }
        }

        return count($stages);
    }

    /**
     * @param  Collection<int, SnapJourney>  $journeys
     * @param  Collection<string, Collection<int, SnapAnalyticsEvent>>  $timelines
     * @return array<string, array{journeys: int, paths: array<string, list<array{stage: string, label: string, journeys: int}>>}>
     */
    private function segments(Collection $journeys, Collection $timelines, string $segmentBy): array
    {
        $segments = [];

        foreach ($journeys as $journey) {
            $segments[$this->segment($journey, $timelines->get($journey->id, collect()), $segmentBy)][] = $timelines->get($journey->id, collect());
        }

        ksort($segments);

        return array_map(fn (array $group): array => [
            'journeys' => count($group),
            'paths' => $this->paths(collect($group)),
        ], $segments);
    }

    /** @param  Collection<int, SnapAnalyticsEvent>  $events */
    private function segment(SnapJourney $journey, Collection $events, string $segmentBy): string
    {
        return match ($segmentBy) {
            'source' => $journey->acquisition_source ?? 'direct',
            'placement' => $this->firstProperty($events, [SnapFunnelStage::UpgradeClicked, SnapFunnelStage::OfferViewed], 'placement') ?? 'none',
            'plan' => $this->firstProperty($events, [SnapFunnelStage::CheckoutStarted, SnapFunnelStage::UpgradeClicked], 'plan') ?? 'none',
            default => $journey->device,
        };
    }

    /**
     * @param  Collection<int, Collection<int, SnapAnalyticsEvent>>  $timelines
     * @return list<array{label: string, journeys: int}>
     */
    private function problems(Collection $timelines): array
    {
        $rows = [];

        foreach ([[SnapFunnelStage::UploadRejected, 'reason'], [SnapFunnelStage::AnalysisFailed, 'failure'], [SnapFunnelStage::CheckoutFailed, 'reason']] as [$stage, $property]) {
            $journeysByValue = $this->tally($timelines->flatMap(fn (Collection $events): array => $events
                ->where('name', $stage->value)
                ->map(fn (SnapAnalyticsEvent $event): string => $this->property($event, $property) ?? 'unknown')
                ->unique()->values()->all()));

            foreach ($journeysByValue as $value => $journeys) {
                $rows[] = ['label' => "{$stage->label()} · {$value}", 'journeys' => $journeys];
            }
        }

        $count = fn (callable $matches): int => $timelines->filter($matches)->count();

        $rows[] = ['label' => 'Analysis requested, no outcome recorded', 'journeys' => $count(fn (Collection $events): bool => $this->hasUnfinishedAnalysis($events))];
        $rows[] = ['label' => SnapFunnelStage::CheckoutCancelled->label(), 'journeys' => $count(fn (Collection $events): bool => $this->has($events, SnapFunnelStage::CheckoutCancelled))];
        $rows[] = ['label' => 'Checkout opened, never returned or paid', 'journeys' => $count(fn (Collection $events): bool => $this->has($events, SnapFunnelStage::CheckoutStarted)
            && ! $this->has($events, SnapFunnelStage::CheckoutReturned) && ! $this->has($events, SnapFunnelStage::CheckoutCancelled)
            && ! $this->has($events, SnapFunnelStage::FirstPayment))];
        $rows[] = ['label' => 'Returned from checkout, payment not confirmed', 'journeys' => $count(fn (Collection $events): bool => $this->has($events, SnapFunnelStage::CheckoutReturned)
            && ! $this->has($events, SnapFunnelStage::FirstPayment))];

        return $rows;
    }

    /** @return array{live: int, attributed: int, staff: int, unattributed: int, test_mode: int, subscriptions: int, subscriptions_paid: int, subscriptions_unpaid: array<string, int>} */
    private function payments(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $payments = SnapAnalyticsEvent::query()->with('journey')
            ->where('name', SnapFunnelStage::FirstPayment->value)
            ->whereBetween('created_at', [$from, $to])
            ->get();
        $live = $payments->reject(fn (SnapAnalyticsEvent $payment): bool => $this->isTestPayment($payment));
        $paidSubscriptions = $payments->map(fn (SnapAnalyticsEvent $payment): string => Str::after($payment->event_key, SnapFunnelStage::FirstPayment->value.':'));
        $subscriptions = Subscription::query()->whereBetween('created_at', [$from, $to])->pluck('stripe_status', 'stripe_id');
        $unpaid = $subscriptions->reject(fn (mixed $status, int|string $subscription): bool => $paidSubscriptions->contains((string) $subscription));

        return [
            'live' => $live->count(),
            'attributed' => $live->filter(fn (SnapAnalyticsEvent $payment): bool => $payment->journey instanceof SnapJourney && ! $payment->journey->internal)->count(),
            'staff' => $live->filter(fn (SnapAnalyticsEvent $payment): bool => $payment->journey instanceof SnapJourney && $payment->journey->internal)->count(),
            'unattributed' => $live->reject(fn (SnapAnalyticsEvent $payment): bool => $payment->journey instanceof SnapJourney)->count(),
            'test_mode' => $payments->count() - $live->count(),
            'subscriptions' => $subscriptions->count(),
            'subscriptions_paid' => $subscriptions->count() - $unpaid->count(),
            'subscriptions_unpaid' => $this->tally($unpaid->map(fn (mixed $status): string => is_string($status) ? $status : 'unknown')),
        ];
    }

    /**
     * @param  Collection<array-key, string>  $values
     * @return array<string, int>
     */
    private function tally(Collection $values): array
    {
        $tally = [];

        foreach ($values as $value) {
            $tally[$value] = ($tally[$value] ?? 0) + 1;
        }

        ksort($tally);

        return $tally;
    }

    /** @param  Collection<int, SnapAnalyticsEvent>  $events */
    private function hasUnfinishedAnalysis(Collection $events): bool
    {
        $requestOf = fn (SnapAnalyticsEvent $event): string => Str::afterLast($event->event_key, ':');
        $requested = $events->where('name', SnapFunnelStage::AnalysisRequested->value)->map($requestOf);
        $finished = $events->whereIn('name', [SnapFunnelStage::AnalysisSucceeded->value, SnapFunnelStage::AnalysisFailed->value])->map($requestOf);

        return $requested->diff($finished)->isNotEmpty();
    }

    /** @param  Collection<int, SnapAnalyticsEvent>  $events */
    private function has(Collection $events, SnapFunnelStage $stage): bool
    {
        return $events->contains(fn (SnapAnalyticsEvent $event): bool => $event->name === $stage->value);
    }

    /**
     * @param  Collection<int, SnapAnalyticsEvent>  $events
     * @param  list<SnapFunnelStage>  $stages
     */
    private function firstProperty(Collection $events, array $stages, string $property): ?string
    {
        foreach ($stages as $stage) {
            $value = $this->property($events->first(fn (SnapAnalyticsEvent $event): bool => $event->name === $stage->value), $property);

            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    private function property(?SnapAnalyticsEvent $event, string $property): ?string
    {
        $value = $event?->properties[$property] ?? null;

        return is_string($value) ? $value : null;
    }

    private function isTestPayment(SnapAnalyticsEvent $event): bool
    {
        return $event->name === SnapFunnelStage::FirstPayment->value && ($event->properties['live'] ?? false) !== true;
    }
}
