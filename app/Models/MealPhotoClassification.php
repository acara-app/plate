<?php

declare(strict_types=1);

namespace App\Models;

use App\Ai\Classifiers\MealPhotoClassifier;
use Carbon\CarbonInterface;
use Database\Factories\MealPhotoClassificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property int $meal_photo_id
 * @property string $version
 * @property string $provider
 * @property string $model
 * @property array<string, array<string, mixed>> $answers
 * @property-read MealPhoto $mealPhoto
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class MealPhotoClassification extends Model
{
    /** @use HasFactory<MealPhotoClassificationFactory> */
    use HasFactory;

    protected $guarded = [];

    public static function deleteForUser(int $userId): void
    {
        self::query()
            ->whereIn('meal_photo_id', MealPhoto::query()->select('id')->where('user_id', $userId))
            ->delete();
    }

    /**
     * @return BelongsTo<MealPhoto, $this>
     */
    public function mealPhoto(): BelongsTo
    {
        return $this->belongsTo(MealPhoto::class);
    }

    public function passesScreening(): bool
    {
        return MealPhotoClassifier::passes($this->answers);
    }

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'meal_photo_id' => 'integer',
            'answers' => 'array',
        ];
    }
}
