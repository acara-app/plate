<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\CaffeineLimitData;
use Illuminate\Support\Str;
use InvalidArgumentException;

final readonly class ResolveCaffeineLimit
{
    private const int ADULT_REFERENCE_LIMIT_MG = 400;

    private const int ADULT_REFERENCE_HEIGHT_CM = 170;

    private const int PREGNANCY_CONTEXT_CAP_MG = 200;

    private const int ROUNDING_INCREMENT_MG = 25;

    /**
     * @var array<string, float>
     */
    private const array SENSITIVITY_MULTIPLIERS = [
        'low' => 1.0,
        'normal' => 0.75,
        'high' => 0.5,
    ];

    /**
     * @var array<string, string>
     */
    private const array SENSITIVITY_LABELS = [
        'low' => 'Low sensitivity',
        'normal' => 'Normal sensitivity',
        'high' => 'High sensitivity',
    ];

    public function handle(int $heightCm, string $sensitivity, ?string $context = null): CaffeineLimitData
    {
        throw_if($heightCm < 90 || $heightCm > 230, InvalidArgumentException::class, 'Height must be between 90 and 230 centimeters.');

        throw_unless(array_key_exists($sensitivity, self::SENSITIVITY_MULTIPLIERS), InvalidArgumentException::class, 'Sensitivity must be low, normal, or high.');

        $hasCautionContext = $this->hasCautionContext($context);
        $baseLimitMg = $this->heightAdjustedBaseLimit($heightCm);

        if ($hasCautionContext) {
            $baseLimitMg = min($baseLimitMg, self::PREGNANCY_CONTEXT_CAP_MG);
        }

        $limitMg = $this->roundToIncrement($baseLimitMg * self::SENSITIVITY_MULTIPLIERS[$sensitivity]);

        return new CaffeineLimitData(
            heightCm: $heightCm,
            sensitivity: $sensitivity,
            sensitivityLabel: self::SENSITIVITY_LABELS[$sensitivity],
            limitMg: $limitMg,
            status: $hasCautionContext ? 'context_limited' : 'height_adjusted_limit',
            hasCautionContext: $hasCautionContext,
            contextLabel: $hasCautionContext ? 'Pregnancy or breastfeeding context detected' : null,
            reasons: $this->buildReasons($heightCm, $sensitivity, $hasCautionContext, $baseLimitMg, $limitMg),
            sourceSummary: 'Adult guidance starts from a 400 mg daily reference point, then uses height as a conservative body-size proxy before sensitivity and context adjustment.',
        );
    }

    /**
     * @return array<int, string>
     */
    private function buildReasons(
        int $heightCm,
        string $sensitivity,
        bool $hasCautionContext,
        int $baseLimitMg,
        int $limitMg,
    ): array {
        $reasons = [
            sprintf('Your height of %d cm adjusts the adult reference limit to %d mg before sensitivity.', $heightCm, $baseLimitMg),
            sprintf('Your %s sensitivity setting adjusts the limit to %d mg.', $sensitivity, $limitMg),
        ];

        if ($hasCautionContext) {
            $reasons[] = 'Your context suggests a lower-caffeine situation, so the base limit is capped before sensitivity adjustment.';
        }

        return $reasons;
    }

    private function heightAdjustedBaseLimit(int $heightCm): int
    {
        $heightFactor = min(1.0, $heightCm / self::ADULT_REFERENCE_HEIGHT_CM);

        return $this->roundToIncrement(self::ADULT_REFERENCE_LIMIT_MG * $heightFactor);
    }

    private function roundToIncrement(float $value): int
    {
        return (int) round($value / self::ROUNDING_INCREMENT_MG) * self::ROUNDING_INCREMENT_MG;
    }

    private function hasCautionContext(?string $context): bool
    {
        if ($context === null || mb_trim($context) === '') {
            return false;
        }

        $context = Str::of($context)->lower()->toString();

        $pregnancy = Str::contains($context, ['pregnant', 'pregnancy'])
            && ! Str::contains($context, ['not pregnant', 'no pregnancy']);

        $breastfeeding = Str::contains($context, ['breastfeeding', 'breast feeding', 'nursing'])
            && ! Str::contains($context, ['not breastfeeding', 'not breast feeding', 'not nursing']);

        $tryingToConceive = Str::contains($context, ['trying to conceive', 'trying for pregnancy', 'planning pregnancy', 'ttc'])
            && ! Str::contains($context, ['not trying to conceive', 'not ttc']);

        return $pregnancy || $breastfeeding || $tryingToConceive;
    }
}
