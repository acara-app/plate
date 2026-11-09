<?php

declare(strict_types=1);

namespace App\Ai\Classifiers;

use App\Enums\Benchmark\DishType;
use App\Enums\MealPhotoQuestion;
use Illuminate\Support\Facades\Context;
use Laravel\Ai\Classification;
use Laravel\Ai\Classification\Boolean;
use Laravel\Ai\Classification\Choice;
use Laravel\Ai\Classification\Score;
use Laravel\Ai\Contracts\Question;
use Laravel\Ai\Files\Image;
use Laravel\Ai\Responses\ClassificationResponse;

final readonly class MealPhotoClassifier
{
    public const string VERSION = '2026-11-09';

    public const string FOOD = 'food';

    public const float MIN_FOOD_PROBABILITY = 0.8;

    public const float MAX_FACE_PROBABILITY = 0.1;

    public const float MAX_PERSONAL_DETAILS_PROBABILITY = 0.1;

    public const int MIN_IMAGE_QUALITY_LEVEL = 2;

    private const string STATE = 'A photo someone took to log a meal in a nutrition tracking app.';

    /**
     * @param  array<string, mixed>  $answers
     */
    public static function passes(array $answers): bool
    {
        return (self::probability($answers, MealPhotoQuestion::Subject, self::FOOD) ?? 0.0) >= self::MIN_FOOD_PROBABILITY
            && (self::probability($answers, MealPhotoQuestion::Face) ?? 1.0) < self::MAX_FACE_PROBABILITY
            && (self::probability($answers, MealPhotoQuestion::PersonalDetails) ?? 1.0) < self::MAX_PERSONAL_DETAILS_PROBABILITY
            && (self::imageQualityLevel($answers) ?? 0) >= self::MIN_IMAGE_QUALITY_LEVEL;
    }

    public function classify(string $path): ClassificationResponse
    {
        $questions = [];

        foreach (MealPhotoQuestion::cases() as $question) {
            $questions[$question->value] = $this->question($question);
        }

        Context::addHidden('ai_classifier', self::class);

        try {
            return Classification::of(self::STATE, [Image::fromStorage($path, 's3')])
                ->questions($questions)
                ->classify(
                    provider: config()->string('ai.photo_classification.provider'),
                    model: config()->string('ai.photo_classification.model'),
                );
        } finally {
            Context::forgetHidden('ai_classifier');
        }
    }

    /**
     * @param  array<string, mixed>  $answers
     */
    private static function probability(array $answers, MealPhotoQuestion $question, ?string $option = null): ?float
    {
        $probability = data_get($answers, $option === null
            ? "{$question->value}.probability"
            : "{$question->value}.probabilities.{$option}");

        return is_numeric($probability) ? (float) $probability : null;
    }

    /**
     * @param  array<string, mixed>  $answers
     */
    private static function imageQualityLevel(array $answers): ?int
    {
        $probabilities = data_get($answers, MealPhotoQuestion::ImageQuality->value.'.probabilities');

        if (! is_array($probabilities) || $probabilities === []) {
            return null;
        }

        return (int) array_search(max($probabilities), $probabilities, true);
    }

    private function question(MealPhotoQuestion $question): Question
    {
        return match ($question) {
            MealPhotoQuestion::Subject => new Choice('What is the main subject of this photo?', [
                self::FOOD => 'Food or drinks ready to eat or drink, such as a plate, bowl, snack, or glass.',
                'packaged_food' => 'Food shown mainly by its package, wrapper, can, or bottle.',
                'nutrition_label' => 'A nutrition facts panel or an ingredient list.',
                'printed_text' => 'A menu, receipt, recipe, or other printed or handwritten text.',
                'screen' => 'A screenshot, or a photo of a screen, even one showing food.',
                'other' => 'Anything else, such as people, pets, places, objects, or a blank image.',
            ]),
            MealPhotoQuestion::Face => new Boolean(
                'Is any human face visible, even partly, small, blurred, in the background, in a reflection, or in a picture or on a screen?',
                [
                    'true' => 'At least part of a human face appears somewhere in the photo.',
                    'false' => 'No human face appears anywhere in the photo.',
                ],
            ),
            MealPhotoQuestion::PersonalDetails => new Boolean(
                'Can any personal information be read, such as a name, address, phone number, email, card or account number, ID document, prescription, or private message?',
                [
                    'true' => 'Some personal information can be read.',
                    'false' => 'Nothing personal can be read.',
                ],
            ),
            MealPhotoQuestion::ImageQuality => new Score('How well can someone tell which foods are shown and how much there is?', [
                'Not at all: too dark, too blurry, or the food is cut off.',
                'Poorly: the food is blurry, dark, partly hidden, or cropped.',
                'Well: the food is clear and almost fully in frame.',
                'Very well: sharp, well lit, with the whole meal in frame.',
            ]),
            MealPhotoQuestion::FoodType => new Choice('What kind of food or drink is mainly shown?', [
                'fruit' => 'Fruit, fresh or cut.',
                'vegetables' => 'Vegetables or a salad.',
                'grains' => 'Rice, noodles, pasta, bread, or cereal.',
                'protein' => 'Meat, fish, eggs, tofu, or beans.',
                'dairy' => 'Milk, yogurt, or cheese.',
                'snack' => 'A snack such as chips, nuts, crackers, or a bar.',
                'dessert' => 'A dessert or sweets such as cake, ice cream, or candy.',
                'drink' => 'A drink such as coffee, tea, juice, or a smoothie.',
                'mixed_meal' => 'A meal combining several of these, such as rice with meat and vegetables.',
            ]),
            MealPhotoQuestion::Cuisine => new Choice('Which cuisine does this food come from?', [
                'chinese' => 'Chinese.',
                'japanese' => 'Japanese.',
                'korean' => 'Korean.',
                'mongolian' => 'Mongolian, such as buuz, khuushuur, or tsuivan.',
                'southeast_asian' => 'Southeast Asian, such as Thai, Vietnamese, or Filipino.',
                'south_asian' => 'South Asian, such as Indian or Pakistani.',
                'middle_eastern' => 'Middle Eastern or North African.',
                'mediterranean' => 'Mediterranean, such as Italian, Greek, or Spanish.',
                'european' => 'Other European, such as French, German, or British.',
                'north_american' => 'North American, such as burgers, barbecue, or diner food.',
                'latin_american' => 'Latin American, such as Mexican or Brazilian.',
                'african' => 'Sub-Saharan African.',
                'none' => 'No particular cuisine, such as plain fruit, a packaged snack, or a simple drink.',
            ]),
            MealPhotoQuestion::DishType => new Choice('Are the foods separate, or combined into one dish?', [
                DishType::Whole->value => 'Separate foods you can see one by one, such as rice next to chicken.',
                DishType::Mixed->value => 'Foods combined into one dish, such as soup, stew, curry, or a sandwich.',
            ]),
        };
    }
}
