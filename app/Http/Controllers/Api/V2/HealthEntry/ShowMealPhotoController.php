<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\HealthEntry;

use App\Models\MealPhoto;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class ShowMealPhotoController
{
    public function __invoke(#[CurrentUser] User $user, string $group): StreamedResponse
    {
        $photo = MealPhoto::query()
            ->where('user_id', $user->id)
            ->where('health_group_id', $group)
            ->latest('id')
            ->firstOrFail();

        abort_unless(Storage::disk($photo->disk)->exists($photo->path), 404);

        return Storage::disk($photo->disk)->response($photo->path, null, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
