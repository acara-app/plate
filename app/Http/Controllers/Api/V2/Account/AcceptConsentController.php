<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Account;

use App\Http\Requests\Api\V2\Account\AcceptConsentRequest;
use App\Models\User;
use Illuminate\Http\Response;

final class AcceptConsentController
{
    public function __invoke(AcceptConsentRequest $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $user->update([
            'terms_accepted_at' => now(),
            'privacy_accepted_at' => now(),
            'accepted_disclaimer_at' => now(),
            'consent_version' => config()->string('plate.terms_version'),
            'privacy_version' => config()->string('plate.privacy_policy_version'),
        ]);

        return response()->noContent();
    }
}
