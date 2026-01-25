<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AllergySeverity;
use App\Enums\GlucoseUnit;
use App\Http\Requests\StoreDietaryPreferencesRequest;
use App\Http\Requests\StoreHealthConditionsRequest;
use App\Http\Requests\StoreMedicationsRequest;
use App\Models\DietaryPreference;
use App\Models\HealthCondition;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ProfileController
{
    public function __construct(
        #[CurrentUser] private User $user,
    ) {
        //
    }

    public function showDietaryPreferences(): Response
    {
        $profile = $this->user->profile;

        $preferences = DietaryPreference::all()->groupBy('type');

        $selectedPreferencesData = [];
        if ($profile) {
            foreach ($profile->dietaryPreferences as $preference) {
                /** @var object{severity: string|null, notes: string|null} $pivot */
                $pivot = $preference->pivot;
                $selectedPreferencesData[$preference->id] = [
                    'severity' => $pivot->severity,
                    'notes' => $pivot->notes,
                ];
            }
        }

        return Inertia::render('profile/dietary-preferences', [
            'profile' => $profile,
            'selectedPreferences' => $profile?->dietaryPreferences->pluck('id')->toArray() ?? [],
            'selectedPreferencesData' => $selectedPreferencesData,
            'preferences' => $preferences,
            'severityOptions' => collect(AllergySeverity::cases())->map(fn (AllergySeverity $severity): array => [
                'value' => $severity->value,
                'label' => $severity->label(),
                'description' => $severity->description(),
            ]),
        ]);
    }

    public function storeDietaryPreferences(StoreDietaryPreferencesRequest $request): RedirectResponse
    {
        $user = $this->user;

        /** @var UserProfile $profile */
        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);

        /** @var array<int, int> $preferenceIds */
        $preferenceIds = $request->validated('dietary_preference_ids') ?? [];
        /** @var array<int, string|null> $severities */
        $severities = $request->validated('severities') ?? [];
        /** @var array<int, string|null> $notes */
        $notes = $request->validated('notes') ?? [];

        $syncData = [];
        foreach ($preferenceIds as $index => $preferenceId) {
            $syncData[$preferenceId] = [
                'severity' => $severities[$index] ?? null,
                'notes' => $notes[$index] ?? null,
            ];
        }

        $profile->dietaryPreferences()->sync($syncData);

        return back()->with('success', 'Dietary preferences updated successfully.');
    }

    public function showHealthConditions(): Response
    {
        $profile = $this->user->profile;

        return Inertia::render('profile/health-conditions', [
            'profile' => $profile,
            'selectedConditions' => $profile?->healthConditions->pluck('id')->toArray() ?? [],
            'healthConditions' => HealthCondition::query()->orderBy('order')->get(),
            'glucoseUnitOptions' => collect(GlucoseUnit::cases())->map(fn (GlucoseUnit $unit): array => [
                'value' => $unit->value,
                'label' => $unit->label(),
            ]),
            'selectedGlucoseUnit' => $profile?->units_preference?->value,
        ]);
    }

    public function storeHealthConditions(StoreHealthConditionsRequest $request): RedirectResponse
    {
        $user = $this->user;

        /** @var UserProfile $profile */
        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);

        /** @var array<int, int> $conditionIds */
        $conditionIds = $request->validated('health_condition_ids') ?? [];
        /** @var array<int, string|null> $notes */
        $notes = $request->validated('notes') ?? [];

        $syncData = [];
        foreach ($conditionIds as $index => $conditionId) {
            $syncData[$conditionId] = [
                'notes' => $notes[$index] ?? null,
            ];
        }

        $profile->healthConditions()->sync($syncData);

        /** @var string|null $glucoseUnit */
        $glucoseUnit = $request->validated('units_preference');
        if ($glucoseUnit !== null) {
            $profile->update(['units_preference' => $glucoseUnit]);
        }

        return back()->with('success', 'Health conditions updated successfully.');
    }

    public function showMedications(): Response
    {
        $profile = $this->user->profile;

        return Inertia::render('profile/medications', [
            'profile' => $profile,
            'medications' => $profile->medications ?? [],
        ]);
    }

    public function storeMedications(StoreMedicationsRequest $request): RedirectResponse
    {
        $user = $this->user;

        /** @var UserProfile $profile */
        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);

        $profile->medications()->delete();

        /** @var array<int, array{name: string, dosage?: string|null, frequency?: string|null, purpose?: string|null, started_at?: string|null}> $medications */
        $medications = collect($request->validated('medications') ?? [])
            ->filter(fn ($med) => ! empty($med['name']))
            ->values()
            ->all();

        foreach ($medications as $medication) {
            if (! empty($medication['name'])) {
                $profile->medications()->create([
                    'name' => $medication['name'],
                    'dosage' => $medication['dosage'] ?? null,
                    'frequency' => $medication['frequency'] ?? null,
                    'purpose' => $medication['purpose'] ?? null,
                    'started_at' => $medication['started_at'] ?? null,
                ]);
            }
        }

        return back()->with('success', 'Medications updated successfully.');
    }
}
