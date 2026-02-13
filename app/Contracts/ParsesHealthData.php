<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Ai\Agents\HealthDataParserAgent;
use App\DataObjects\HealthLogData;
use Illuminate\Container\Attributes\Bind;

#[Bind(HealthDataParserAgent::class)]
interface ParsesHealthData
{
    public function parse(string $message): HealthLogData;
}
