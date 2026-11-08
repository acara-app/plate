<?php

declare(strict_types=1);

namespace App\Actions;

use GdImage;

final readonly class ResizePhoto
{
    private const int MAX_PIXELS = 50_000_000;

    private const int BYTES_PER_PIXEL = 5;

    private const int JPEG_QUALITY = 82;

    public function handle(string $imageBytes, int $maxSide): ?string
    {
        $size = @getimagesizefromstring($imageBytes);

        if ($size === false || ! $this->fitsInMemory($size[0], $size[1])) {
            return null;
        }

        $image = @imagecreatefromstring($imageBytes);

        if (! $image instanceof GdImage) {
            return null;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1.0, $maxSide / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        imagefill($canvas, 0, 0, (int) imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        ob_start();
        imagejpeg($this->upright($canvas, $imageBytes), null, self::JPEG_QUALITY);

        return (string) ob_get_clean();
    }

    private function fitsInMemory(int $width, int $height): bool
    {
        $limit = ini_parse_quantity(ini_get('memory_limit'));

        return $width * $height <= self::MAX_PIXELS
            && ($limit <= 0 || memory_get_usage(true) + $width * $height * self::BYTES_PER_PIXEL < $limit);
    }

    private function upright(GdImage $image, string $imageBytes): GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $stream = fopen('php://memory', 'r+b');

        if ($stream === false) {
            return $image;
        }

        fwrite($stream, $imageBytes);
        rewind($stream);
        $exif = @exif_read_data($stream);
        fclose($stream);

        $orientation = is_array($exif) && is_numeric($exif['Orientation'] ?? null) ? (int) $exif['Orientation'] : 1;

        $angle = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);

        return $rotated instanceof GdImage ? $rotated : $image;
    }
}
