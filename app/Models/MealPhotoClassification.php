<?php

declare(strict_types=1);

namespace App\Models;

use App\Ai\Classifiers\MealPhotoClassifier;
use App\Enums\MealPhotoQuestion;
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

    public const float MIN_FOOD_PROBABILITY = 0.8;

    public const float MAX_FACE_PROBABILITY = 0.1;

    public const float MAX_PERSONAL_DETAILS_PROBABILITY = 0.1;

    public const int MIN_IMAGE_QUALITY_LEVEL = 2;

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
        return ($this->probability(MealPhotoQuestion::Subject, MealPhotoClassifier::FOOD) ?? 0.0) >= self::MIN_FOOD_PROBABILITY
            && ($this->probability(MealPhotoQuestion::Face) ?? 1.0) < self::MAX_FACE_PROBABILITY
            && ($this->probability(MealPhotoQuestion::PersonalDetails) ?? 1.0) < self::MAX_PERSONAL_DETAILS_PROBABILITY
            && ($this->imageQualityLevel() ?? 0) >= self::MIN_IMAGE_QUALITY_LEVEL;
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

    private function probability(MealPhotoQuestion $question, ?string $option = null): ?float
    {
        $probability = data_get($this->answers, $option === null
            ? "{$question->value}.probability"
            : "{$question->value}.probabilities.{$option}");

        return is_numeric($probability) ? (float) $probability : null;
    }

    private function imageQualityLevel(): ?int
    {
        $probabilities = data_get($this->answers, MealPhotoQuestion::ImageQuality->value.'.probabilities');

        if (! is_array($probabilities) || $probabilities === []) {
            return null;
        }

        return (int) array_search(max($probabilities), $probabilities, true);
    }
}
