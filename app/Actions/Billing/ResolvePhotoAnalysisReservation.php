<?php

declare(strict_types=1);

namespace App\Actions\Billing;

use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final readonly class ResolvePhotoAnalysisReservation
{
    public function handle(?int $userId, ?string $guestId, string $requestId, string $fingerprint, string $source): ?string
    {
        if ($userId === null && $guestId === null) {
            return null;
        }

        if (! Schema::hasTable('snap_photo_requests') || ! Schema::hasTable('snap_photo_accounts')) {
            return null;
        }

        $member = $userId === null ? null : 'user:'.$userId;
        $guest = $guestId === null ? null : 'guest:'.$guestId;

        return DB::transaction(function () use ($member, $guest, $guestId, $requestId, $fingerprint, $source): ?string {
            $reservation = DB::table('snap_photo_requests')
                ->where('request_key', $requestId)
                ->where('fingerprint', $fingerprint)
                ->where('source', $source)
                ->when($guestId !== null, fn (Builder $query): Builder => $query->where('guest_id', $guestId))
                ->where(function (Builder $query) use ($member, $guest): void {
                    if ($member !== null) {
                        $query->where('subject', $member);

                        if ($guest !== null) {
                            $query->orWhere(fn (Builder $guestRequest): Builder => $guestRequest
                                ->where('subject', $guest)
                                ->whereExists(fn (Builder $account): Builder => $account->selectRaw('1')
                                    ->from('snap_photo_accounts')->where('subject', $guest)->where('owner', $member)));
                        }

                        return;
                    }

                    $query->where('subject', $guest)
                        ->orWhereIn('subject', DB::table('snap_photo_accounts')->select('owner')
                            ->where('subject', $guest)->whereNotNull('owner'));
                })
                ->lockForUpdate()->first();

            if ($reservation === null) {
                return null;
            }

            if ($reservation->status === 'succeeded' && is_string($reservation->result) && is_string($reservation->created_at)
                && CarbonImmutable::parse($reservation->created_at)->addDay()->isFuture()) {
                return $reservation->result;
            }

            if ($reservation->status === 'pending') {
                DB::table('snap_photo_requests')->where('id', $reservation->id)
                    ->where('attempt', $reservation->attempt)->where('status', 'pending')
                    ->update(['status' => 'failed', 'result' => null]);
            }

            return null;
        }, 3);
    }
}
