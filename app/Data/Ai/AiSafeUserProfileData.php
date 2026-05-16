<?php

declare(strict_types=1);

namespace App\Data\Ai;

final readonly class AiSafeUserProfileData
{
    /**
     * @param  list<string>  $missingData
     * @param  array<string, mixed>  $sections
     */
    public function __construct(
        public bool $onboardingCompleted,
        public array $missingData,
        private array $sections,
    ) {}

    /**
     * @return list<string>
     */
    public static function supportedSections(): array
    {
        return [
            'all',
            'biometrics',
            'dietary_preferences',
            'goals',
            'health_conditions',
            'medications',
            'household',
            'safety',
        ];
    }

    public function hasSection(string $section): bool
    {
        return in_array($section, self::supportedSections(), true);
    }

    public function section(string $section): mixed
    {
        return match ($section) {
            'all' => $this->sections,
            'safety' => $this->safetySection(),
            default => $this->sections[$section] ?? null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->sections;
    }

    /**
     * @return array{
     *     allergies: array<int, array<string, mixed>>,
     *     health_conditions: mixed,
     *     medications: mixed,
     *     household: mixed
     * }
     */
    private function safetySection(): array
    {
        /** @var array<int, array<string, mixed>> $dietaryPreferences */
        $dietaryPreferences = $this->sections['dietary_preferences'] ?? [];

        return [
            'allergies' => array_values(array_filter(
                $dietaryPreferences,
                static fn (array $attribute): bool => ($attribute['category'] ?? null) === 'allergy',
            )),
            'health_conditions' => $this->sections['health_conditions'] ?? [],
            'medications' => $this->sections['medications'] ?? [],
            'household' => $this->sections['household'] ?? ['summary' => null],
        ];
    }
}
