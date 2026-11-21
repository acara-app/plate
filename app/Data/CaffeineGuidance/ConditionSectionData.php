<?php

declare(strict_types=1);

namespace App\Data\CaffeineGuidance;

use App\Enums\CaffeineTone;
use App\Http\Requests\CaffeineAssessmentRequest;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Data;

/**
 * @codeCoverageIgnore
 */
final class ConditionSectionData extends Data
{
    public function __construct(
        #[In(CaffeineAssessmentRequest::ALLOWED_CONDITIONS)]
        public string $condition,
        public string $title,
        public string $body,
        public CaffeineTone $tone,
    ) {}
}
