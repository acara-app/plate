<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\MealPhotoSampleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property-read int $id
 * @property int $user_id
 * @property int|null $meal_photo_id
 * @property string $path
 * @property string $version
 * @property array<string, array<string, mixed>> $answers
 * @property-read User $user
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class MealPhotoSample extends Model
{
    /** @use HasFactory<MealPhotoSampleFactory> */
    use HasFactory;

    use Prunable;

    public const string DIRECTORY = 'meal-photo-samples';

    public const int RETENTION_MONTHS = 24;

    protected $guarded = [];

    public static function directoryFor(User $user): string
    {
        return self::DIRECTORY.'/'.$user->id;
    }

    public static function deleteForUser(User $user): void
    {
        Storage::disk('s3')->deleteDirectory(self::directoryFor($user));

        self::query()->where('user_id', $user->id)->delete();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return Builder<self>
     */
    public function prunable(): Builder
    {
        return self::query()->where('created_at', '<', now()->subMonths(self::RETENTION_MONTHS));
    }

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'user_id' => 'integer',
            'meal_photo_id' => 'integer',
            'answers' => 'array',
        ];
    }

    protected function pruning(): void
    {
        Storage::disk('s3')->delete($this->path);
    }
}
