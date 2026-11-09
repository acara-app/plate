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
use Laravel\Ai\Responses\Data\Answer;

#[Backoff(30, 120)]
#[Tries(3)]
final class ScreenPublicMealPhotoJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $path) {}

    public function handle(MealPhotoClassifier $classifier): void
    {
        $answers = $classifier->classify($this->path)->collect()
            ->map(fn (Answer $answer): array => $answer->toArray())
            ->all();

        if (! MealPhotoClassifier::passes($answers)) {
            Storage::disk('s3')->delete($this->path);

            return;
        }

        MealPhotoSample::query()->create([
            'user_id' => null,
            'path' => $this->path,
            'version' => MealPhotoClassifier::VERSION,
            'answers' => $answers,
        ]);
    }

    public function failed(): void
    {
        Storage::disk('s3')->delete($this->path);
    }
}
