<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\MealPhotoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property-read int $id
 * @property int $user_id
 * @property int|null $analysis_draft_id
 * @property string|null $health_group_id
 * @property string $disk
 * @property string $path
 * @property-read User $user
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class MealPhoto extends Model
{
    /** @use HasFactory<MealPhotoFactory> */
    use HasFactory;

    use Prunable;

    public const string DISK = 's3';

    public const string DIRECTORY = 'meal-photos';

    protected $guarded = [];

    public static function directoryFor(User $user): string
    {
        return self::DIRECTORY.'/'.$user->id;
    }

    public static function deleteForGroup(int $userId, string $groupId): void
    {
        self::query()
            ->where('user_id', $userId)
            ->where('health_group_id', $groupId)
            ->get()
            ->each(fn (self $photo): bool => (bool) $photo->deleteWithFile());
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deleteWithFile(): ?bool
    {
        Storage::disk($this->disk)->delete($this->path);

        return $this->delete();
    }

    /**
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return self::query()
            ->whereNull('health_group_id')
            ->where('created_at', '<', now()->subDay());
    }

    protected function pruning(): void
    {
        Storage::disk($this->disk)->delete($this->path);
    }
}
