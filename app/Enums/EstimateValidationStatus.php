<?php

declare(strict_types=1);

namespace App\Enums;

enum EstimateValidationStatus: string
{
    case AiGenerated = 'ai_generated';
    case UserCorrected = 'user_corrected';
    case ExpertValidated = 'expert_validated';
}
