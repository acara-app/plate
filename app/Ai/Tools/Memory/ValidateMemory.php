<?php

declare(strict_types=1);

namespace App\Ai\Tools\Memory;

use App\Ai\Agents\Memory\MemoryValidatorAgent;
use App\Ai\Exceptions\Memory\MemoryNotFoundException;
use App\Contracts\Ai\Memory\ValidateMemoryTool;
use App\Data\Memory\MemoryValidationResultData;
use App\Models\Memory;
use Illuminate\Support\Facades\Auth;

final readonly class ValidateMemory implements ValidateMemoryTool
{
    public function __construct(private MemoryValidatorAgent $agent) {}

    public function execute(string $memoryId, ?string $context = null): MemoryValidationResultData
    {
        $userId = (int) (Auth::id() ?? 0);

        $query = Memory::query()->where('id', $memoryId);
        if ($userId > 0) {
            $query->where('user_id', $userId);
        }

        $memory = $query->first();

        throw_unless($memory instanceof Memory, MemoryNotFoundException::class, $memoryId);

        $prompt = "Memory to validate:\n".$memory->content;

        if ($context !== null && $context !== '') {
            $prompt .= "\n\nContext:\n".$context;
        }

        return $this->agent->validateMemory($prompt);
    }
}
