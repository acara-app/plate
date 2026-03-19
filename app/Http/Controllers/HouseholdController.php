<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateHouseholdRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class HouseholdController
{
    public function edit(#[CurrentUser] User $user): Response
    {
        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);

        return Inertia::render('household/edit', [
            'householdContext' => $profile->household_context,
        ]);
    }

    public function update(UpdateHouseholdRequest $request, #[CurrentUser] User $user): RedirectResponse
    {
        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);

        $profile->update($request->validated());

        return to_route('household.edit');
    }
}
