<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AnalysisDraft;
use App\Models\MealPhoto;
use App\Models\User;
use GdImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

final readonly class StoreMealPhotoAction
{
    private const int MAX_SIDE = 1280;

    private const int JPEG_QUALITY = 82;

    public function handle(User $user, string $imageBytes, string $draftToken): MealPhoto
    {
        $path = MealPhoto::directoryFor($user).'/'.Str::uuid().'.jpg';

        throw_unless(
            Storage::disk(MealPhoto::DISK)->put($path, $this->normalizedJpeg($imageBytes)),
            RuntimeException::class,
            'Could not store the meal photo.',
        );

        return MealPhoto::query()->create([
            'user_id' => $user->id,
            'analysis_draft_id' => AnalysisDraft::query()->where('token_hash', AnalysisDraft::hashToken($draftToken))->value('id'),
            'disk' => MealPhoto::DISK,
            'path' => $path,
        ]);
    }

    private function normalizedJpeg(string $imageBytes): string
    {
        $image = @imagecreatefromstring($imageBytes);
        throw_unless($image instanceof GdImage, RuntimeException::class, 'The meal photo could not be read.');

        $image = $this->upright($image, $imageBytes);
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1.0, self::MAX_SIDE / max($width, $height));

        if ($scale < 1.0) {
            $scaled = imagescale($image, max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)));
            $image = $scaled instanceof GdImage ? $scaled : $image;
        }

        $canvas = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagefill($canvas, 0, 0, (int) imagecolorallocate($canvas, 255, 255, 255));
        imagecopy($canvas, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

        ob_start();
        imagejpeg($canvas, null, self::JPEG_QUALITY);

        return (string) ob_get_clean();
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
