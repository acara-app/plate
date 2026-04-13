<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\MobileSync\SleepEventData;
use App\Models\SleepSession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

/** @codeCoverageIgnore */
final readonly class SyncSleepEventsAction
{
    public function __construct(
        private CollectAffectedUtcDatesAction $collectAffectedUtcDates,
    ) {}

    /**
     * @param  list<SleepEventData>  $events
     * @return array{created: int, updated: int, affected_utc_dates: list<string>}
     */
    public function handle(User $user, array $events, ?string $timezone = null): array
    {
        $created = 0;
        $updated = 0;
        $affectedUtcDates = [];

        DB::transaction(function () use ($user, $events, $timezone, &$created, &$updated, &$affectedUtcDates): void {
            $uuidCache = $this->preloadByUuid($user, $events);

            foreach ($events as $event) {
                if ($event->type !== 'sleepAnalysis') {
                    continue;
                }

                $startedAt = CarbonImmutable::instance(Date::parse($event->started_at)->utc());
                $endedAt = CarbonImmutable::instance(Date::parse($event->ended_at)->utc());

                $attrs = [
                    'started_at' => $startedAt,
                    'ended_at' => $endedAt,
                    'stage' => $event->stage,
                    'source' => $event->source,
                    'timezone' => $timezone,
                    'sample_uuid' => $event->sample_uuid,
                ];

                if ($event->sample_uuid !== null && isset($uuidCache[$event->sample_uuid])) {
                    $uuidCache[$event->sample_uuid]->update($attrs);
                    $updated++;
                    $this->collectAffectedUtcDates->handle($startedAt, $endedAt, $affectedUtcDates);

                    continue;
                }

                $existing = $user->sleepSessions()
                    ->where('started_at', $startedAt)
                    ->where('stage', $event->stage)
                    ->first();

                if ($existing !== null) {
                    $existing->update($attrs);
                    $updated++;
                    $this->collectAffectedUtcDates->handle($startedAt, $endedAt, $affectedUtcDates);

                    continue;
                }

                $session = SleepSession::query()->create([
                    ...$attrs,
                    'user_id' => $user->id,
                ]);

                if ($event->sample_uuid !== null) {
                    $uuidCache[$event->sample_uuid] = $session;
                }

                $created++;
                $this->collectAffectedUtcDates->handle($startedAt, $endedAt, $affectedUtcDates);
            }
        });

        return [
            'created' => $created,
            'updated' => $updated,
            'affected_utc_dates' => array_keys($affectedUtcDates),
        ];
    }

    /**
     * @param  list<SleepEventData>  $events
     * @return array<string, SleepSession>
     */
    private function preloadByUuid(User $user, array $events): array
    {
        $uuids = collect($events)->pluck('sample_uuid')->filter()->unique()->values()->all();

        if ($uuids === []) {
            return [];
        }

        $keyed = [];

        foreach ($user->sleepSessions()->whereIn('sample_uuid', $uuids)->get() as $session) {
            if ($session->sample_uuid !== null) {
                $keyed[$session->sample_uuid] = $session;
            }
        }

        return $keyed;
    }
}
