<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AcknowledgePrivacyPolicy;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class AcknowledgePrivacyPolicyController
{
    public function __construct(private AcknowledgePrivacyPolicy $acknowledgePrivacyPolicy) {}

    public function __invoke(#[CurrentUser] User $user): RedirectResponse
    {
        $this->acknowledgePrivacyPolicy->handle($user);

        return back();
    }
}
