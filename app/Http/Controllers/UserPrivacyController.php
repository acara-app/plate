<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RecordConsent;
use App\Enums\ConsentPurpose;
use App\Http\Requests\UpdateUserPrivacyRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class UserPrivacyController
{
    public function __construct(private RecordConsent $recordConsent) {}

    public function edit(#[CurrentUser] User $user): Response
    {
        return Inertia::render('user-privacy/edit', [
            'improveFoodRecognition' => $user->consentDecision(ConsentPurpose::ImproveFoodRecognition) === true,
        ]);
    }

    public function update(UpdateUserPrivacyRequest $request, #[CurrentUser] User $user): RedirectResponse
    {
        $this->recordConsent->handle(
            $user,
            ConsentPurpose::ImproveFoodRecognition,
            $request->boolean('improve_food_recognition'),
            $request->source(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('common.privacy_settings.saved')]);

        return back();
    }
}
