<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class StoreResizedPhoto
{
    private const int MAX_SIDE = 1280;

    public function __construct(private ResizePhoto $resizePhoto) {}

    public function handle(string $imageBytes, string $directory): string
    {
        $jpeg = $this->resizePhoto->handle($imageBytes, self::MAX_SIDE)
            ?? throw new RuntimeException('The meal photo could not be read.');

        $path = $directory.'/'.Str::uuid().'.jpg';

        throw_unless(
            Storage::disk('s3')->put($path, $jpeg),
            RuntimeException::class,
            'Could not store the meal photo.',
        );

        return $path;
    }
}
