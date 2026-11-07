<?php

declare(strict_types=1);

namespace App\Enums;

enum PhotoAnalysisFailure: string
{
    case NoFood = 'no_food';
    case InvalidPhoto = 'invalid_photo';
    case LimitReached = 'limit_reached';
    case ProviderError = 'provider_error';
    case DispatchFailed = 'dispatch_failed';
    case Timeout = 'timeout';
    case QueueTimeout = 'queue_timeout';
    case Interrupted = 'interrupted';
    case Expired = 'expired';

    public function message(): string
    {
        return match ($this) {
            self::NoFood => 'No food items were detected. Try a clearer photo with better lighting.',
            self::Expired => 'This photo has expired. Choose a photo to start a new scan.',
            self::LimitReached => 'Your scan allowance is used up. Choose a plan to continue.',
            self::Timeout, self::QueueTimeout, self::Interrupted => 'This analysis could not finish. Your photo is ready to retry without uploading again.',
            self::InvalidPhoto, self::ProviderError, self::DispatchFailed => 'This analysis could not finish. Please try again.',
        };
    }
}
