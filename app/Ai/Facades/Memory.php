<?php

declare(strict_types=1);

namespace App\Ai\Facades;

use App\Contracts\Ai\Memory\ArchiveMemoriesTool;
use App\Contracts\Ai\Memory\CategorizeMemoriesTool;
use App\Contracts\Ai\Memory\ConsolidateMemoriesTool;
use App\Contracts\Ai\Memory\DecayMemoriesTool;
use App\Contracts\Ai\Memory\DeleteMemoryTool;
use App\Contracts\Ai\Memory\GetImportantMemoriesTool;
use App\Contracts\Ai\Memory\GetMemoryStatTool;
use App\Contracts\Ai\Memory\GetMemoryTool;
use App\Contracts\Ai\Memory\GetRelatedMemoriesTool;
use App\Contracts\Ai\Memory\LinkMemoriesTool;
use App\Contracts\Ai\Memory\ReflectOnMemoriesTool;
use App\Contracts\Ai\Memory\RestoreMemoriesTool;
use App\Contracts\Ai\Memory\SearchMemoryTool;
use App\Contracts\Ai\Memory\StoreMemoryTool;
use App\Contracts\Ai\Memory\UpdateMemoryTool;
use App\Contracts\Ai\Memory\ValidateMemoryTool;
use App\DataObjects\Memory\MemoryData;
use App\DataObjects\Memory\MemorySearchResultData;
use App\DataObjects\Memory\MemoryStatsData;
use App\DataObjects\Memory\MemoryValidationResultData;
use App\DataObjects\Memory\RelatedMemoryData;
use BadMethodCallException;

/**
 * Static facade for memory operations.
 *
 * Resolves the appropriate tool contract from the container and invokes it.
 *
 * @method static string store(string $content, array<string, mixed> $metadata = [], array<float>|null $vector = null, int $importance = 1, array<string> $categories = [], \DateTimeInterface|null $expiresAt = null)
 * @method static array<int, MemorySearchResultData> search(string $query, int $limit = 5, float $minRelevance = 0.7, array<string, mixed> $filter = [], bool $includeArchived = false)
 * @method static MemoryData get(string $memoryId, bool $includeArchived = false)
 * @method static bool update(string $memoryId, string|null $content = null, array<string, mixed>|null $metadata = null, int|null $importance = null)
 * @method static int delete(string|null $memoryId = null, array<string, mixed> $filter = [])
 * @method static array<string, array<string>|null> categorize(array<string> $memoryIds, bool $persistCategories = true)
 * @method static string consolidate(array<string> $memoryIds, string $synthesizedContent, array<string, mixed>|null $metadata = null, int|null $importance = null, bool $deleteOriginals = true)
 * @method static array<string> reflect(int $lookbackWindow = 50, string|null $context = null, array<string> $categories = [])
 * @method static array<int, MemoryData> getImportant(int $threshold = 8, int $limit = 10, array<string> $categories = [], bool $includeArchived = false)
 * @method static MemoryStatsData getStats()
 * @method static bool link(array<string> $memoryIds, string $relationship = 'related', bool $bidirectional = true)
 * @method static array<int, RelatedMemoryData> getRelated(string $memoryId, int $depth = 1, array<string> $relationships = [], bool $includeArchived = false)
 * @method static array{decayed_count: int, archived_count: int, avg_importance_before: float, avg_importance_after: float} decay(int $ageThresholdDays = 30, float $decayFactor = 0.9, int $minImportance = 1, bool $archiveDecayed = true)
 * @method static MemoryValidationResultData validate(string $memoryId, string|null $context = null)
 * @method static int archive(array<string> $memoryIds)
 * @method static int restore(array<string> $memoryIds)
 */
final class Memory
{
    /**
     * Map method names to their tool contracts.
     *
     * @var array<string, class-string>
     */
    private static array $tools = [
        'store' => StoreMemoryTool::class,
        'search' => SearchMemoryTool::class,
        'get' => GetMemoryTool::class,
        'update' => UpdateMemoryTool::class,
        'delete' => DeleteMemoryTool::class,
        'categorize' => CategorizeMemoriesTool::class,
        'consolidate' => ConsolidateMemoriesTool::class,
        'reflect' => ReflectOnMemoriesTool::class,
        'getImportant' => GetImportantMemoriesTool::class,
        'getStats' => GetMemoryStatTool::class,
        'link' => LinkMemoriesTool::class,
        'getRelated' => GetRelatedMemoriesTool::class,
        'decay' => DecayMemoriesTool::class,
        'validate' => ValidateMemoryTool::class,
        'archive' => ArchiveMemoriesTool::class,
        'restore' => RestoreMemoriesTool::class,
    ];

    /**
     * @param  array<int, mixed>  $arguments
     */
    public static function __callStatic(string $method, array $arguments): mixed
    {
        throw_unless(isset(self::$tools[$method]), BadMethodCallException::class, "Method Memory::{$method}() does not exist.");

        /** @var object $tool */
        $tool = resolve(self::$tools[$method]);

        /** @phpstan-ignore-next-line */
        return $tool->execute(...$arguments);
    }
}
