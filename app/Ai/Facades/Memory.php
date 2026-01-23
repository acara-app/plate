<?php

declare(strict_types=1);

namespace App\Ai\Facades;

use App\Ai\Contracts\Memory\ArchiveMemoriesTool;
use App\Ai\Contracts\Memory\CategorizeMemoriesTool;
use App\Ai\Contracts\Memory\ConsolidateMemoriesTool;
use App\Ai\Contracts\Memory\DecayMemoriesTool;
use App\Ai\Contracts\Memory\DeleteMemoryTool;
use App\Ai\Contracts\Memory\GetImportantMemoriesTool;
use App\Ai\Contracts\Memory\GetMemoryStatTool;
use App\Ai\Contracts\Memory\GetMemoryTool;
use App\Ai\Contracts\Memory\GetRelatedMemoriesTool;
use App\Ai\Contracts\Memory\LinkMemoriesTool;
use App\Ai\Contracts\Memory\ReflectOnMemoriesTool;
use App\Ai\Contracts\Memory\RestoreMemoriesTool;
use App\Ai\Contracts\Memory\SearchMemoryTool;
use App\Ai\Contracts\Memory\StoreMemoryTool;
use App\Ai\Contracts\Memory\UpdateMemoryTool;
use App\Ai\Contracts\Memory\ValidateMemoryTool;
use BadMethodCallException;

/**
 * Static facade for memory operations.
 *
 * Resolves the appropriate tool contract from the container and invokes it.
 *
 * @method static string store(string $content, array $metadata = [], ?array $vector = null, int $importance = 1, array $categories = [], ?\DateTimeInterface $expiresAt = null)
 * @method static array search(string $query, int $limit = 5, float $minRelevance = 0.7, array $filter = [], bool $includeArchived = false)
 * @method static \App\DataObjects\Memory\MemoryData get(string $memoryId, bool $includeArchived = false)
 * @method static bool update(string $memoryId, ?string $content = null, ?array $metadata = null, ?int $importance = null)
 * @method static int delete(?string $memoryId = null, array $filter = [])
 * @method static array categorize(array $memoryIds, bool $persistCategories = true)
 * @method static string consolidate(array $memoryIds, string $synthesizedContent, ?array $metadata = null, ?int $importance = null, bool $deleteOriginals = true)
 * @method static array reflect(int $lookbackWindow = 50, ?string $context = null, array $categories = [])
 * @method static array getImportant(int $threshold = 8, int $limit = 10, array $categories = [], bool $includeArchived = false)
 * @method static \App\DataObjects\Memory\MemoryStatsData getStats()
 * @method static bool link(array $memoryIds, string $relationship = 'related', bool $bidirectional = true)
 * @method static array getRelated(string $memoryId, int $depth = 1, array $relationships = [], bool $includeArchived = false)
 * @method static array decay(int $ageThresholdDays = 30, float $decayFactor = 0.9, int $minImportance = 1, bool $archiveDecayed = true)
 * @method static \App\DataObjects\Memory\MemoryValidationResultData validate(string $memoryId, ?string $context = null)
 * @method static int archive(array $memoryIds)
 * @method static int restore(array $memoryIds)
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

        $tool = resolve(self::$tools[$method]);

        return $tool(...$arguments);
    }
}
