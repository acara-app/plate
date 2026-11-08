<?php

declare(strict_types=1);

namespace App\Enums;

enum LegalPage: string
{
    case PrivacyPolicy = 'privacy-policy';
    case TermsOfService = 'terms-of-service';

    public function routeName(): string
    {
        return match ($this) {
            self::PrivacyPolicy => 'privacy',
            self::TermsOfService => 'terms',
        };
    }

    public function versionConfigKey(): string
    {
        return match ($this) {
            self::PrivacyPolicy => 'plate.privacy_policy_version',
            self::TermsOfService => 'plate.terms_version',
        };
    }

    public function translationKey(): string
    {
        return str_replace('-', '_', $this->value);
    }
}
