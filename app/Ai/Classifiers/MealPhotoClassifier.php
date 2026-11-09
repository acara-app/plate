<?php

declare(strict_types=1);

namespace App\Ai\Classifiers;

use App\Enums\MealPhotoQuestion;
use App\Models\MealPhoto;
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

    private const string STATE = 'A photo someone took to log a meal in a nutrition tracking app.';

    public function classify(MealPhoto $photo): ClassificationResponse
    {
        $questions = [];

        foreach (MealPhotoQuestion::cases() as $question) {
            $questions[$question->value] = $this->question($question);
        }

        Context::addHidden('ai_classifier', self::class);

        try {
            return Classification::of(self::STATE, [Image::fromStorage($photo->path, 's3')])
                ->questions($questions)
                ->classify(
                    provider: config()->string('ai.photo_classification.provider'),
                    model: config()->string('ai.photo_classification.model'),
                );
        } finally {
            Context::forgetHidden('ai_classifier');
        }
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
        };
    }
}
