<?php

declare(strict_types=1);

namespace App\Providers;

use App\Ai\Tools\Memory\ArchiveMemories;
use App\Ai\Tools\Memory\CategorizeMemories;
use App\Ai\Tools\Memory\ConsolidateMemories;
use App\Ai\Tools\Memory\DecayMemories;
use App\Ai\Tools\Memory\DeleteMemory;
use App\Ai\Tools\Memory\GetImportantMemories;
use App\Ai\Tools\Memory\GetMemory;
use App\Ai\Tools\Memory\GetMemoryStat;
use App\Ai\Tools\Memory\GetRelatedMemories;
use App\Ai\Tools\Memory\LinkMemories;
use App\Ai\Tools\Memory\ReflectOnMemories;
use App\Ai\Tools\Memory\RestoreMemories;
use App\Ai\Tools\Memory\SearchMemory;
use App\Ai\Tools\Memory\StoreMemory;
use App\Ai\Tools\Memory\UpdateMemory;
use App\Ai\Tools\Memory\ValidateMemory;
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
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class MemoryServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    private const array BINDINGS = [
        StoreMemoryTool::class => StoreMemory::class,
        SearchMemoryTool::class => SearchMemory::class,
        GetMemoryTool::class => GetMemory::class,
        UpdateMemoryTool::class => UpdateMemory::class,
        DeleteMemoryTool::class => DeleteMemory::class,
        CategorizeMemoriesTool::class => CategorizeMemories::class,
        ConsolidateMemoriesTool::class => ConsolidateMemories::class,
        ReflectOnMemoriesTool::class => ReflectOnMemories::class,
        ValidateMemoryTool::class => ValidateMemory::class,
        LinkMemoriesTool::class => LinkMemories::class,
        GetRelatedMemoriesTool::class => GetRelatedMemories::class,
        DecayMemoriesTool::class => DecayMemories::class,
        ArchiveMemoriesTool::class => ArchiveMemories::class,
        RestoreMemoriesTool::class => RestoreMemories::class,
        GetImportantMemoriesTool::class => GetImportantMemories::class,
        GetMemoryStatTool::class => GetMemoryStat::class,
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/memory.php', 'memory');

        foreach (self::BINDINGS as $contract => $concrete) {
            $this->app->bind($contract, $concrete);
        }
    }

    public function boot(): void
    {
        RateLimiter::for('memory-extraction', static fn () => Limit::perMinute(10));
        RateLimiter::for(
            'memory-consolidation',
            static fn () => Limit::perMinute((int) config('memory.consolidation.jobs_per_minute', 10)), /** @phpstan-ignore cast.int */
        );
    }
}
