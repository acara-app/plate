<?php

declare(strict_types=1);

namespace App\Ai;

use App\Actions\GetUserProfileContextAction;
use App\Models\User;
use App\Utilities\LanguageUtil;

final readonly class SingleMealPromptBuilder
{
    public function __construct(
        private GetUserProfileContextAction $profileContext,
    ) {}

    public function handle(
        User $user,
        string $mealType,
        ?string $cuisine = null,
        ?int $maxCalories = null,
        ?string $specificRequest = null,
    ): string {
        $profileData = $this->profileContext->handle($user);
        $contextString = $profileData['context'];

        ['label' => $language, 'code' => $languageCode] = LanguageUtil::resolve($user->locale);

        return view('ai.agents.generate-single-meal', [
            'profileContext' => $contextString,
            'mealType' => $mealType,
            'cuisine' => $cuisine,
            'maxCalories' => $maxCalories,
            'specificRequest' => $specificRequest,
            'language' => $language,
            'languageCode' => $languageCode,
        ])->render();
    }
}
