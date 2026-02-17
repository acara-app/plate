<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Actions\SaveHealthLogAction;
use App\DataObjects\HealthLogData;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Container\Attributes\Bind;

#[Bind(SaveHealthLogAction::class)]
interface SavesHealthLog
{
    public function handle(User $user, HealthLogData $data, ?CarbonInterface $measuredAt = null): void;
}
