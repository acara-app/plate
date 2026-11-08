<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\SnapPhotoAccountFactory;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

/**
 * @property string $subject
 * @property string|null $owner
 */
#[WithoutIncrementing]
#[WithoutTimestamps]
final class SnapPhotoAccount extends Model
{
    /** @use HasFactory<SnapPhotoAccountFactory> */
    use HasFactory;

    use Prunable;

    protected $primaryKey = 'subject';

    protected $keyType = 'string';

    public static function guestCookieExpiredBefore(): CarbonImmutable
    {
        return CarbonImmutable::today('UTC')->subDays(config()->integer('snap.guest_cookie_days'));
    }

    /**
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return self::query()
            ->where('subject', 'like', 'guest:%')
            ->whereNull('owner')
            ->whereNotIn('subject', SnapPhotoRequest::query()
                ->select('subject')
                ->where('subject', 'like', 'guest:%')
                ->groupBy('subject')
                ->havingRaw('min(created_at) >= ?', [self::guestCookieExpiredBefore()]));
    }

    protected function pruning(): void
    {
        $periods = SnapPhotoPeriod::query()->where('subject', $this->subject)->pluck('id');

        SnapPhotoRequest::query()->where('subject', $this->subject)->orWhereIn('period_id', $periods)->delete();
        SnapPhotoPeriod::query()->whereIn('id', $periods)->delete();
    }
}
