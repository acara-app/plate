<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\GenerateGroceryListAction;
use App\Enums\GroceryListStatus;
use App\Models\GroceryList;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

final class GenerateGroceryListJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public int $maxExceptions = 3;

    public function __construct(
        public readonly GroceryList $groceryList,
    ) {}

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            new WithoutOverlapping((string) $this->groceryList->id),
        ];
    }

    public function uniqueId(): string
    {
        return (string) $this->groceryList->id;
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 60, 120];
    }

    public function handle(GenerateGroceryListAction $action): void
    {
        $action->generateItems($this->groceryList);
    }

    public function failed(Throwable $exception): void
    {
        $this->groceryList->update(['status' => GroceryListStatus::Failed]);
    }
}
