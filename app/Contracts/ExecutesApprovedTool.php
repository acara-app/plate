<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\AgentApproval;

interface ExecutesApprovedTool
{
    /**
     * @return array<string, mixed>
     */
    public function handle(AgentApproval $approval): array;
}
