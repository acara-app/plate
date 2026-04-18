<?php

declare(strict_types=1);

namespace App\Contracts\Memory;

interface DispatchesMemoryExtraction
{
    public function dispatchIfEligible(int $userId): void;
}
