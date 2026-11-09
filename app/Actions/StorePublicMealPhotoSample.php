<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\ScreenPublicMealPhotoJob;
use App\Models\MealPhotoSample;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class StorePublicMealPhotoSample
{
    private const int MAX_SIDE = 1280;

    public function __construct(private ResizePhoto $resizePhoto) {}

    public function handle(string $imageBytes): void
    {
        $jpeg = $this->resizePhoto->handle($imageBytes, self::MAX_SIDE)
            ?? throw new RuntimeException('The meal photo could not be read.');

        $path = MealPhotoSample::PUBLIC_DIRECTORY.'/'.Str::uuid().'.jpg';

        throw_unless(
            Storage::disk('s3')->put($path, $jpeg),
            RuntimeException::class,
            'Could not store the meal photo.',
        );

        dispatch(new ScreenPublicMealPhotoJob($path))->afterCommit();
    }
}
