<?php

declare(strict_types=1);

namespace App\Http\Controllers\SnapToTrack;

use App\Actions\StoreMealPhotoAction;
use App\Enums\AnalysisDraftSource;
use App\Http\Requests\SnapToTrack\StoreSnapToTrackPhotoRequest;
use App\Models\AnalysisDraft;
use App\Models\MealPhoto;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Throwable;

final readonly class StoreSnapToTrackPhotoController
{
    public function __construct(private StoreMealPhotoAction $storeMealPhoto) {}

    public function __invoke(StoreSnapToTrackPhotoRequest $request, #[CurrentUser] User $user, string $draft): RedirectResponse
    {
        $draftModel = AnalysisDraft::query()
            ->where('token_hash', AnalysisDraft::hashToken($draft))
            ->where('user_id', $user->id)
            ->where('source', AnalysisDraftSource::PublicSnapToTrack)
            ->whereNotNull('health_group_id')
            ->first();
        $photo = $request->file('photo');

        if (! $draftModel instanceof AnalysisDraft || ! $photo instanceof UploadedFile || MealPhoto::query()->where('analysis_draft_id', $draftModel->id)->exists()) {
            return back();
        }

        try {
            $this->storeMealPhoto->handle($user, (string) $photo->get(), $draft, $draftModel->health_group_id);
        } catch (Throwable $throwable) {
            report($throwable);
        }

        return back();
    }
}
