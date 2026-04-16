<?php

declare(strict_types=1);

namespace App\Contracts\Ai\Memory;

use App\Services\Null\NullMemoryExtractionDispatcher;
use Illuminate\Container\Attributes\Bind;

#[Bind(NullMemoryExtractionDispatcher::class)]
interface DispatchesMemoryExtraction
{
    public function dispatchIfEligible(int $userId): void;
}
