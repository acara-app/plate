<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\GenerateGroceryListAction;
use App\Models\GroceryList;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

final class GenerateGroceryListJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly GroceryList $groceryList,
    ) {}

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            new WithoutOverlapping($this->groceryList->id),
        ];
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): int
    {
        return $this->groceryList->id;
    }

    public function handle(GenerateGroceryListAction $action): void
    {
        $action->generateItems($this->groceryList);
    }
}
