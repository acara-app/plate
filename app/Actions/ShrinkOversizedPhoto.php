<?php

declare(strict_types=1);

namespace App\Actions;

final readonly class ShrinkOversizedPhoto
{
    private const int MAX_SIDE = 2048;

    public function __construct(private ResizePhoto $resizePhoto) {}

    public function handle(string $imageBytes): string
    {
        $size = @getimagesizefromstring($imageBytes);

        if ($size === false || max($size[0], $size[1]) <= self::MAX_SIDE) {
            return $imageBytes;
        }

        return $this->resizePhoto->handle($imageBytes, self::MAX_SIDE) ?? $imageBytes;
    }
}
