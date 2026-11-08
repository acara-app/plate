<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ConsentPurpose;
use App\Enums\ConsentSource;
use App\Models\User;

final readonly class AcceptHealthDataConsent
{
    public function __construct(
        private RecordConsent $recordConsent,
        private AcknowledgePrivacyPolicy $acknowledgePrivacyPolicy,
    ) {}

    public function handle(User $user): void
    {
        $this->recordConsent->handle($user, ConsentPurpose::ProcessHealthData, true, ConsentSource::Dashboard);
        $this->acknowledgePrivacyPolicy->handle($user);
    }
}
