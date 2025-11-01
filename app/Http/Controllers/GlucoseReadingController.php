<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeleteGlucoseReadingAction;
use App\Actions\GetUserGlucoseReadingsAction;
use App\Actions\RecordGlucoseReadingAction;
use App\Actions\UpdateGlucoseReadingAction;
use App\Enums\ReadingType;
use App\Http\Requests\StoreGlucoseReadingRequest;
use App\Http\Requests\UpdateGlucoseReadingRequest;
use App\Models\GlucoseReading;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class GlucoseReadingController
{
    public function __construct(
        private RecordGlucoseReadingAction $recordGlucoseReading,
        private GetUserGlucoseReadingsAction $getUserGlucoseReadings,
        private UpdateGlucoseReadingAction $updateGlucoseReading,
        private DeleteGlucoseReadingAction $deleteGlucoseReading,
        #[CurrentUser()] private User $currentUser,
    ) {}

    public function index(): Response
    {
        $user = $this->currentUser;

        $readings = $this->getUserGlucoseReadings->handle($user);

        return Inertia::render('glucose/index', [
            'readings' => $readings,
            'readingTypes' => collect(ReadingType::cases())->map(fn (ReadingType $type): array => [
                'value' => $type->value,
                'label' => $type->value,
            ]),
        ]);
    }

    /**
     * Display the glucose dashboard with visualizations and analytics.
     */
    public function dashboard(): Response
    {
        $user = $this->currentUser;

        // Get all readings for visualization (not paginated)
        $allReadings = $user->glucoseReadings()
            ->orderBy('measured_at', 'desc')
            ->get()
            ->map(fn (GlucoseReading $reading): array => [
                'id' => $reading->id,
                'reading_value' => $reading->reading_value,
                'reading_type' => $reading->reading_type->value,
                'measured_at' => $reading->measured_at->toISOString(),
                'notes' => $reading->notes,
                'created_at' => $reading->created_at->toISOString(),
            ]);

        return Inertia::render('glucose/dashboard', [
            'readings' => $allReadings,
            'readingTypes' => collect(ReadingType::cases())->map(fn (ReadingType $type): array => [
                'value' => $type->value,
                'label' => $type->value,
            ]),
        ]);
    }

    /**
     * Store a newly created glucose reading.
     */
    public function store(StoreGlucoseReadingRequest $request): RedirectResponse
    {
        $user = $this->currentUser;

        $data = $request->validated();

        $this->recordGlucoseReading->handle(
            $data + ['user_id' => $user->id]
        );

        return back()->with('success', 'Glucose reading recorded successfully.');
    }

    /**
     * Update the specified glucose reading.
     */
    public function update(UpdateGlucoseReadingRequest $request, GlucoseReading $glucoseReading): RedirectResponse
    {
        // Ensure the user owns this reading
        abort_if($glucoseReading->user_id !== $this->currentUser->id, 403);

        $data = $request->validated();

        $this->updateGlucoseReading->handle($glucoseReading, $data);

        return back()->with('success', 'Glucose reading updated successfully.');
    }

    /**
     * Remove the specified glucose reading.
     */
    public function destroy(\Illuminate\Http\Request $request, GlucoseReading $glucoseReading): RedirectResponse
    {
        $user = $request->user();

        // Ensure the user owns this reading
        abort_if($glucoseReading->user_id !== $user?->id, 403);

        $this->deleteGlucoseReading->handle($glucoseReading);

        return back()->with('success', 'Glucose reading deleted successfully.');
    }
}
