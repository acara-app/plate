<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\AnalyzeGlucoseForNotificationAction;
use App\Actions\DeleteDiabetesLogAction;
use App\Actions\GetUserDiabetesLogsAction;
use App\Actions\RecordDiabetesLogAction;
use App\Actions\UpdateDiabetesLogAction;
use App\Enums\GlucoseReadingType;
use App\Enums\GlucoseUnit;
use App\Enums\InsulinType;
use App\Http\Requests\StoreDiabetesLogRequest;
use App\Http\Requests\UpdateDiabetesLogRequest;
use App\Models\DiabetesLog;
use App\Models\Meal;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class DiabetesLogController
{
    public function __construct(
        private RecordDiabetesLogAction $recordDiabetesLog,
        private GetUserDiabetesLogsAction $getUserDiabetesLogs,
        private UpdateDiabetesLogAction $updateDiabetesLog,
        private DeleteDiabetesLogAction $deleteDiabetesLog,
        private AnalyzeGlucoseForNotificationAction $analyzeAction,
        #[CurrentUser()] private User $currentUser,
    ) {}

    public function index(): Response
    {
        $user = $this->currentUser;

        $logs = $this->getUserDiabetesLogs->handle($user);

        return Inertia::render('diabetes-log/index', [
            'logs' => $logs,
            'glucoseReadingTypes' => collect(GlucoseReadingType::cases())->map(fn (GlucoseReadingType $type): array => [
                'value' => $type->value,
                'label' => $type->value,
            ]),
            'insulinTypes' => collect(InsulinType::cases())->map(fn (InsulinType $type): array => [
                'value' => $type->value,
                'label' => ucfirst($type->value),
            ]),
            'glucoseUnit' => $user->profile?->glucose_unit->value ?? GlucoseUnit::MgDl->value,
            'recentMedications' => $this->getRecentMedications($user),
            'recentInsulins' => $this->getRecentInsulins($user),
            'todaysMeals' => $this->getTodaysMeals($user),
        ]);
    }

    /**
     * Display the diabetes log dashboard with visualizations and analytics.
     */
    public function dashboard(): Response
    {
        $user = $this->currentUser;

        // Get all logs for visualization (not paginated)
        $allLogs = $user->diabetesLogs()
            ->latest('measured_at')
            ->get()
            ->map(fn (DiabetesLog $log): array => [
                'id' => $log->id,
                'glucose_value' => $log->glucose_value,
                'glucose_reading_type' => $log->glucose_reading_type?->value,
                'measured_at' => $log->measured_at->toISOString(),
                'notes' => $log->notes,
                'insulin_units' => $log->insulin_units,
                'insulin_type' => $log->insulin_type?->value,
                'medication_name' => $log->medication_name,
                'medication_dosage' => $log->medication_dosage,
                'weight' => $log->weight,
                'blood_pressure_systolic' => $log->blood_pressure_systolic,
                'blood_pressure_diastolic' => $log->blood_pressure_diastolic,
                'a1c_value' => $log->a1c_value,
                'carbs_grams' => $log->carbs_grams,
                'exercise_type' => $log->exercise_type,
                'exercise_duration_minutes' => $log->exercise_duration_minutes,
                'created_at' => $log->created_at->toISOString(),
            ]);

        return Inertia::render('diabetes-log/tracking', [
            'logs' => $allLogs,
            'glucoseReadingTypes' => collect(GlucoseReadingType::cases())->map(fn (GlucoseReadingType $type): array => [
                'value' => $type->value,
                'label' => $type->value,
            ]),
            'insulinTypes' => collect(InsulinType::cases())->map(fn (InsulinType $type): array => [
                'value' => $type->value,
                'label' => ucfirst($type->value),
            ]),
            'glucoseUnit' => $user->profile?->glucose_unit->value ?? GlucoseUnit::MgDl->value,
            'recentMedications' => $this->getRecentMedications($user),
            'recentInsulins' => $this->getRecentInsulins($user),
            'todaysMeals' => $this->getTodaysMeals($user),
        ]);
    }

    /**
     * Display the diabetes insights (merged from glucose action).
     */
    public function insights(): Response
    {
        $analysisResult = $this->analyzeAction->handle($this->currentUser);

        return Inertia::render('diabetes-log/insights', [
            'glucoseAnalysis' => $analysisResult->analysisData,
            'concerns' => $analysisResult->concerns,
            'hasMealPlan' => $this->currentUser->has_meal_plan,
            'mealPlan' => $this->currentUser->mealPlans()->latest()->first(),
        ]);
    }

    /**
     * Store a newly created diabetes log.
     */
    public function store(StoreDiabetesLogRequest $request): RedirectResponse
    {
        $user = $this->currentUser;

        $data = $request->validated();

        $this->recordDiabetesLog->handle(
            $data + ['user_id' => $user->id]
        );

        return back()->with('success', 'Diabetes log entry recorded successfully.');
    }

    /**
     * Update the specified diabetes log.
     */
    public function update(UpdateDiabetesLogRequest $request, DiabetesLog $diabetesLog): RedirectResponse
    {
        // Ensure the user owns this log
        abort_if($diabetesLog->user_id !== $this->currentUser->id, 403);

        $data = $request->validated();

        $this->updateDiabetesLog->handle($diabetesLog, $data);

        return back()->with('success', 'Diabetes log entry updated successfully.');
    }

    /**
     * Remove the specified diabetes log.
     */
    public function destroy(Request $request, DiabetesLog $diabetesLog): RedirectResponse
    {
        $user = $request->user();

        // Ensure the user owns this log
        abort_if($diabetesLog->user_id !== $user?->id, 403);

        $this->deleteDiabetesLog->handle($diabetesLog);

        return back()->with('success', 'Diabetes log entry deleted successfully.');
    }

    /**
     * Get recent unique medications from user's diabetes logs for quick-add chips.
     *
     * @return array<int, array{name: string, dosage: string, label: string}>
     */
    private function getRecentMedications(User $user): array
    {
        /** @var array<int, array{name: string, dosage: string, label: string}> */
        return $user->diabetesLogs()
            ->whereNotNull('medication_name')
            ->whereNotNull('medication_dosage')
            ->latest()
            ->get(['medication_name', 'medication_dosage'])
            ->unique(fn (DiabetesLog $log): string => "{$log->medication_name}|{$log->medication_dosage}")
            ->take(5)
            ->map(fn (DiabetesLog $log): array => [
                'name' => (string) $log->medication_name,
                'dosage' => (string) $log->medication_dosage,
                'label' => "{$log->medication_name} {$log->medication_dosage}",
            ])
            ->values()
            ->all();
    }

    /**
     * Get recent unique insulin entries from user's diabetes logs for quick-add chips.
     *
     * @return array<int, array{units: float, type: string, label: string}>
     */
    private function getRecentInsulins(User $user): array
    {
        /** @var array<int, array{units: float, type: string, label: string}> */
        return $user->diabetesLogs()
            ->whereNotNull('insulin_units')
            ->whereNotNull('insulin_type')
            ->latest()
            ->get(['insulin_units', 'insulin_type'])
            ->unique(fn (DiabetesLog $log): string => "{$log->insulin_units}|{$log->insulin_type?->value}")
            ->take(5)
            ->map(fn (DiabetesLog $log): array => [
                'units' => (float) $log->insulin_units,
                'type' => (string) $log->insulin_type?->value,
                'label' => "{$log->insulin_units}u {$log->insulin_type?->value}",
            ])
            ->values()
            ->all();
    }

    /**
     * Get today's meals from active meal plan for quick carb import.
     *
     * @return array<int, array{id: int, name: string, type: string, carbs: float, label: string}>
     */
    private function getTodaysMeals(User $user): array
    {
        $mealPlan = $user->mealPlans()
            ->latest()
            ->first();

        if ($mealPlan === null) {
            return [];
        }

        // Calculate what day of the meal plan today is
        $startDate = $mealPlan->created_at->startOfDay();
        $today = now()->startOfDay();
        $dayNumber = (int) $startDate->diffInDays($today) + 1;

        // Clamp to valid range
        if ($dayNumber < 1 || $dayNumber > $mealPlan->duration_days) {
            $dayNumber = (($dayNumber - 1) % $mealPlan->duration_days) + 1;
        }

        /** @var array<int, array{id: int, name: string, type: string, carbs: float, label: string}> */
        return $mealPlan->mealsForDay($dayNumber)
            ->map(fn (Meal $meal): array => [
                'id' => $meal->id,
                'name' => (string) $meal->name,
                'type' => ucfirst((string) $meal->type->value),
                'carbs' => (float) ($meal->carbs_grams ?? 0),
                'label' => ucfirst((string) $meal->type->value) . ' - ' . ($meal->carbs_grams ?? 0) . 'g carbs',
            ])
            ->values()
            ->all();
    }
}
