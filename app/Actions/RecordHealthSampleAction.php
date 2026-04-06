<?php

declare(strict_types=1);

namespace App\Actions;

use App\DataObjects\HealthLogData;
use App\Enums\HealthEntrySource;
use App\Models\HealthSyncSample;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final readonly class RecordHealthSampleAction
{
    public function handle(HealthLogData $data, User $user, HealthEntrySource $source): HealthSyncSample
    {
        return DB::transaction(function () use ($data, $user, $source): HealthSyncSample {
            $samples = $data->toSampleArrays();

            throw_if($samples === [], InvalidArgumentException::class, 'No health samples to record.');

            $groupId = count($samples) > 1 ? (string) Str::uuid() : null;
            $measuredAt = $data->measuredAt ?? now();
            $primary = null;

            foreach ($samples as $sample) {
                $created = HealthSyncSample::query()->create([
                    'user_id' => $user->id,
                    'type_identifier' => $sample['type_identifier'],
                    'value' => $sample['value'],
                    'unit' => $sample['unit'],
                    'measured_at' => $measuredAt,
                    'entry_source' => $source,
                    'metadata' => empty($sample['metadata']) ? null : $sample['metadata'],
                    'notes' => $data->notes,
                    'group_id' => $groupId,
                ]);

                $primary ??= $created;
            }

            /** @var HealthSyncSample $primary */
            return $primary;
        });
    }
}
