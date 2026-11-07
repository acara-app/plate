<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use App\Models\SnapPhotoAccount;
use App\Models\SnapPhotoRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final readonly class ResolvePhotoAnalysisReservation
{
    public function handle(?int $userId, ?string $guestId, string $requestId, string $fingerprint, string $source): ?string
    {
        if ($userId === null && $guestId === null) {
            return null;
        }

        $member = $userId === null ? null : "user:{$userId}";
        $guest = $guestId === null ? null : "guest:{$guestId}";

        return DB::transaction(function () use ($member, $guest, $guestId, $requestId, $fingerprint, $source): ?string {
            $reservation = SnapPhotoRequest::query()
                ->where('request_key', $requestId)
                ->where('fingerprint', $fingerprint)
                ->where('source', $source)
                ->when($guestId !== null, fn (Builder $query): Builder => $query->where('guest_id', $guestId))
                ->where(function (Builder $query) use ($member, $guest): void {
                    if ($member !== null) {
                        $query->where('subject', $member);

                        if ($guest !== null) {
                            $query->orWhere(fn (Builder $guestRequest): Builder => $guestRequest->where('subject', $guest)
                                ->whereExists(SnapPhotoAccount::query()->whereKey($guest)->where('owner', $member)->toBase()));
                        }

                        return;
                    }

                    $query->where('subject', $guest)
                        ->orWhereIn('subject', SnapPhotoAccount::query()->whereKey($guest)->whereNotNull('owner')->select('owner'));
                })
                ->lockForUpdate()
                ->first();

            if ($reservation === null) {
                return null;
            }

            if ($reservation->status === 'succeeded') {
                return $reservation->created_at->addDay()->isFuture() ? $reservation->result : null;
            }

            if ($reservation->status === 'pending') {
                SnapPhotoRequest::query()->whereKey($reservation->id)
                    ->where('attempt', $reservation->attempt)->where('status', 'pending')
                    ->update(['status' => 'failed', 'result' => null]);
            }

            return null;
        }, 3);
    }
}
