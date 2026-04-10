<?php

declare(strict_types=1);

namespace App\Actions;

use App\DataObjects\MobileSync\SleepEventData;
use App\Models\SleepSession;
use App\Models\User;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

final readonly class SyncSleepEventsAction
{
    /**
     * @param  list<SleepEventData>  $events
     * @return array{created: int, updated: int}
     */
    public function handle(User $user, array $events, ?string $timezone = null): array
    {
        $created = 0;
        $updated = 0;

        DB::transaction(function () use ($user, $events, $timezone, &$created, &$updated): void {
            $uuidCache = $this->preloadByUuid($user, $events);

            foreach ($events as $event) {
                if ($event->type !== 'sleepAnalysis') {
                    continue;
                }

                $startedAt = Date::parse($event->started_at);
                $endedAt = Date::parse($event->ended_at);

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

                    continue;
                }

                $existing = $user->sleepSessions()
                    ->where('started_at', $startedAt)
                    ->where('stage', $event->stage)
                    ->first();

                if ($existing !== null) {
                    $existing->update($attrs);
                    $updated++;

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
            }
        });

        return ['created' => $created, 'updated' => $updated];
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
