<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate dietary preferences
        if (Schema::hasTable('user_profile_dietary_preference') && Schema::hasTable('dietary_preferences')) {
            $rows = DB::table('user_profile_dietary_preference')
                ->join('dietary_preferences', 'dietary_preferences.id', '=', 'user_profile_dietary_preference.dietary_preference_id')
                ->select([
                    'user_profile_dietary_preference.user_profile_id',
                    'dietary_preferences.name as value',
                    'dietary_preferences.type',
                    'user_profile_dietary_preference.severity',
                    'user_profile_dietary_preference.notes',
                    'user_profile_dietary_preference.created_at',
                    'user_profile_dietary_preference.updated_at',
                ])
                ->get();

            $categoryMap = [
                'allergy' => 'allergy',
                'intolerance' => 'intolerance',
                'pattern' => 'dietary_pattern',
                'dislike' => 'dislike',
                'restriction' => 'restriction',
            ];

            foreach ($rows as $row) {
                $rowData = (array) $row;
                DB::table('user_profile_attributes')->insertOrIgnore([
                    'user_profile_id' => $row->user_profile_id,
                    'category' => isset($rowData['type']) && is_string($rowData['type']) ? ($categoryMap[$rowData['type']] ?? $rowData['type']) : $row->type,
                    'value' => $row->value,
                    'severity' => $row->severity,
                    'notes' => $row->notes,
                    'metadata' => null,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }
        }

        // Migrate health conditions
        if (Schema::hasTable('user_profile_health_condition') && Schema::hasTable('health_conditions')) {
            $rows = DB::table('user_profile_health_condition')
                ->join('health_conditions', 'health_conditions.id', '=', 'user_profile_health_condition.health_condition_id')
                ->select([
                    'user_profile_health_condition.user_profile_id',
                    'health_conditions.name as value',
                    'user_profile_health_condition.notes',
                    'user_profile_health_condition.created_at',
                    'user_profile_health_condition.updated_at',
                ])
                ->get();

            foreach ($rows as $row) {
                DB::table('user_profile_attributes')->insertOrIgnore([
                    'user_profile_id' => $row->user_profile_id,
                    'category' => 'health_condition',
                    'value' => $row->value,
                    'severity' => null,
                    'notes' => $row->notes,
                    'metadata' => null,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }
        }

        // Migrate medications
        if (Schema::hasTable('user_medications')) {
            $rows = DB::table('user_medications')->get();

            foreach ($rows as $row) {
                $metadata = array_filter([
                    'dosage' => $row->dosage,
                    'frequency' => $row->frequency,
                    'purpose' => $row->purpose,
                    'started_at' => $row->started_at,
                ]);

                DB::table('user_profile_attributes')->insertOrIgnore([
                    'user_profile_id' => $row->user_profile_id,
                    'category' => 'medication',
                    'value' => $row->name,
                    'severity' => null,
                    'notes' => null,
                    'metadata' => $metadata !== [] ? json_encode($metadata) : null,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }
        }
    }
};
