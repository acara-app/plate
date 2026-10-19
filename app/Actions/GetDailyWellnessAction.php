<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\DailyWellnessData;
use App\Enums\DailyCheckInMetric;
use App\Enums\HealthEntrySource;
use App\Enums\SleepQuality;
use App\Enums\StressLevel;
use App\Models\HealthSyncSample;
use App\Models\SleepSession;
use App\Models\User;
use App\Services\CumulativeSampleDeduplicator;
use App\Services\HealthMetricRegistry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final readonly class GetDailyWellnessAction
{
    public const int STEP_GOAL = 10_000;

    private const string STEP_COUNT_TYPE = 'stepCount';

    private const array ASLEEP_STAGES = [
        SleepSession::STAGE_ASLEEP_CORE,
        SleepSession::STAGE_ASLEEP_DEEP,
        SleepSession::STAGE_ASLEEP_REM,
        SleepSession::STAGE_ASLEEP_UNSPECIFIED,
    ];

    public function __construct(
        private HealthMetricRegistry $registry,
        private CumulativeSampleDeduplicator $deduplicator,
    ) {}

    public function handle(User $user, CarbonImmutable $day): DailyWellnessData
    {
        $start = $day->startOfDay();
        $checkIns = $this->checkIns($user, $start);
        $syncedSleepHours = $this->syncedSleepHours($user, $start);
        $sleepQuality = $checkIns->get(DailyCheckInMetric::SleepQuality->value);
        $stressLevel = $checkIns->get(DailyCheckInMetric::StressLevel->value);

        return new DailyWellnessData(
            steps: $this->steps($user, $start),
            stepGoal: self::STEP_GOAL,
            sleepHours: $syncedSleepHours > 0 ? round($syncedSleepHours, 1) : $checkIns->get(DailyCheckInMetric::SleepHours->value),
            sleepSynced: $syncedSleepHours > 0,
            sleepQuality: $sleepQuality === null ? null : SleepQuality::tryFrom((int) $sleepQuality),
            stressLevel: $stressLevel === null ? null : StressLevel::tryFrom((int) $stressLevel),
        );
    }

    /**
     * @return Collection<string, float>
     */
    private function checkIns(User $user, CarbonImmutable $start): Collection
    {
        /** @var Collection<string, float> */
        return $user->healthSyncSamples()
            ->where('entry_source', HealthEntrySource::Web)
            ->whereIn('type_identifier', array_column(DailyCheckInMetric::cases(), 'value'))
            ->where('measured_at', '>=', $start->utc())
            ->where('measured_at', '<', $start->addDay()->utc())
            ->oldest('measured_at')
            ->pluck('value', 'type_identifier');
    }

    private function steps(User $user, CarbonImmutable $start): int
    {
        $samples = $user->healthSyncSamples()
            ->where('type_identifier', self::STEP_COUNT_TYPE)
            ->where('measured_at', '>=', $start->utc())
            ->where('measured_at', '<', $start->addDay()->utc())
            ->get();

        $sourcePreference = $this->registry->descriptorOrUnknown(self::STEP_COUNT_TYPE)->sourcePreference;

        $stepCounts = $this->deduplicator->handle($samples, $sourcePreference)
            ->map(static fn (HealthSyncSample $sample): float => $sample->value)
            ->all();

        return (int) round(array_sum($stepCounts));
    }

    private function syncedSleepHours(User $user, CarbonImmutable $start): float
    {
        /** @var Collection<int, SleepSession> $sessions */
        $sessions = $user->sleepSessions()
            ->forNight($start->subDay(), $start->getTimezone()->getName())
            ->get();

        $asleepHours = $this->coveredHours($sessions->whereIn('stage', self::ASLEEP_STAGES));

        return $asleepHours > 0
            ? $asleepHours
            : $this->coveredHours($sessions->where('stage', SleepSession::STAGE_IN_BED));
    }

    /**
     * @param  Collection<int, SleepSession>  $sessions
     */
    private function coveredHours(Collection $sessions): float
    {
        $coveredSeconds = 0;
        $coveredUntil = PHP_INT_MIN;

        foreach ($sessions->sortBy(static fn (SleepSession $session): int => $session->started_at->getTimestamp()) as $session) {
            $start = max($session->started_at->getTimestamp(), $coveredUntil);
            $end = $session->ended_at->getTimestamp();

            if ($end > $start) {
                $coveredSeconds += $end - $start;
                $coveredUntil = $end;
            }
        }

        return $coveredSeconds / 3600;
    }
}
