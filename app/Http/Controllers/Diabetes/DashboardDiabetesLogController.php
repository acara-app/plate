<?php

declare(strict_types=1);

namespace App\Http\Controllers\Diabetes;

use App\Http\Layouts\DiabetesLayout;
use App\Models\DiabetesLog;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Inertia\Inertia;
use Inertia\Response;

final readonly class DashboardDiabetesLogController
{
    public function __construct(
        #[CurrentUser()] private User $currentUser,
    ) {}

    public function __invoke(): Response
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
            ...DiabetesLayout::props($user),
        ]);
    }
}
