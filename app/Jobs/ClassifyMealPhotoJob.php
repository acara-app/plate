<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\ClassifyMealPhoto;
use App\Models\MealPhoto;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\Attributes\Tries;

#[Backoff(30, 120)]
#[DeleteWhenMissingModels]
#[Tries(3)]
final class ClassifyMealPhotoJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly MealPhoto $photo) {}

    public function handle(ClassifyMealPhoto $classifyMealPhoto): void
    {
        $classifyMealPhoto->handle($this->photo);
    }
}
