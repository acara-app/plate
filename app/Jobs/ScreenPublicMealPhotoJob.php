<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Ai\Classifiers\MealPhotoClassifier;
use App\Models\MealPhotoSample;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Storage;
use Throwable;

#[Backoff(30, 120)]
#[Tries(3)]
final class ScreenPublicMealPhotoJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $path) {}

    public function handle(MealPhotoClassifier $classifier): void
    {
        $answers = MealPhotoClassifier::answers($classifier->classify($this->path));

        if (! MealPhotoClassifier::passes($answers)) {
            Storage::disk('s3')->delete($this->path);

            return;
        }

        MealPhotoSample::query()->firstOrCreate(['path' => $this->path], [
            'user_id' => null,
            'version' => MealPhotoClassifier::VERSION,
            'answers' => $answers,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Storage::disk('s3')->delete($this->path);
    }
}
