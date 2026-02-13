<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\GlucoseUnit;
use App\Models\HealthEntry;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * One-time migration command to convert existing mmol/L glucose values to mg/dL.
 *
 * This command should be run after deploying the glucose unit normalization fix.
 * It converts all glucose values stored in mmol/L to mg/dL for users who have
 * mmol/L as their unit preference.
 */
final class MigrateGlucoseValuesToMgDlCommand extends Command
{
    protected $signature = 'glucose:migrate-to-mgdl {--dry-run : Show what would be migrated without making changes}';

    protected $description = 'Migrate existing mmol/L glucose values to mg/dL in the database';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Running in dry-run mode. No changes will be made.');
        }

        $this->info('Starting glucose value migration to mg/dL...');

        // Get users with mmol/L preference
        $usersWithMmolL = User::query()
            ->whereHas('profile', function ($query): void {
                $query->where('units_preference', GlucoseUnit::MmolL->value);
            })
            ->withCount(['diabetesLogs as glucose_logs_count' => function ($query): void {
                $query->whereNotNull('glucose_value');
            }])
            ->get();

        $totalUsers = $usersWithMmolL->count();
        $this->info("Found {$totalUsers} users with mmol/L preference.");

        if ($totalUsers === 0) {
            $this->info('No users with mmol/L preference found. Nothing to migrate.');

            return self::SUCCESS;
        }

        $totalMigrated = 0;
        $totalSkipped = 0;

        foreach ($usersWithMmolL as $user) {
            $migrated = $this->migrateUserGlucoseValues($user, $dryRun);
            $totalMigrated += $migrated['migrated'];
            $totalSkipped += $migrated['skipped'];
        }

        $this->newLine();
        $this->info('Migration complete!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Users Processed', $totalUsers],
                ['Values Migrated', $totalMigrated],
                ['Values Skipped', $totalSkipped],
            ]
        );

        if ($dryRun) {
            $this->newLine();
            $this->warn('This was a dry run. Run without --dry-run to apply changes.');
        }

        return self::SUCCESS;
    }

    /**
     * Migrate glucose values for a specific user.
     *
     * @return array{migrated: int, skipped: int}
     */
    private function migrateUserGlucoseValues(User $user, bool $dryRun): array
    {
        $migrated = 0;
        $skipped = 0;

        // Get all glucose logs for this user
        $logs = HealthEntry::query()
            ->where('user_id', $user->id)
            ->whereNotNull('glucose_value')
            ->cursor();

        foreach ($logs as $log) {
            $originalValue = (float) $log->glucose_value;

            // Determine if this value is likely in mmol/L
            // mmol/L range: typically 1.1-33.3 (realistic range: 2-20)
            // mg/dL range: 20-600
            // Values in 1.1-33.3 that are NOT already in mg/dL range should be converted
            if ($this->shouldConvertToMgDl($originalValue)) {
                $convertedValue = GlucoseUnit::mmolLToMgDl($originalValue);

                if ($dryRun) {
                    $this->line("  [DRY-RUN] User {$user->id}, Log {$log->id}: {$originalValue} mmol/L → {$convertedValue} mg/dL");
                } else {
                    $log->update(['glucose_value' => $convertedValue]);
                    $this->line("  Migrated User {$user->id}, Log {$log->id}: {$originalValue} mmol/L → {$convertedValue} mg/dL");
                }
                $migrated++;
            } else {
                $skipped++;
            }
        }

        if ($migrated > 0 || $skipped > 0) {
            $this->info("User {$user->id}: {$migrated} migrated, {$skipped} skipped");
        }

        return ['migrated' => $migrated, 'skipped' => $skipped];
    }

    /**
     * Determine if a value should be converted from mmol/L to mg/dL.
     *
     * Logic:
     * - Values 1.0-33.3 are likely mmol/L (within mmol/L validation range)
     * - Values > 50 are definitely mg/dL (too high for mmol/L)
     * - Values 33.3-50 are ambiguous but likely mg/dL (would be 600+ mg/dL otherwise)
     * - Values < 1.0 are invalid anyway
     */
    private function shouldConvertToMgDl(float $value): bool
    {
        // mmol/L validation range is 1.1-33.3
        // If value is within this range, it's likely mmol/L
        // If value is > 33.3, it's likely already in mg/dL
        return $value >= 1.0 && $value <= 33.3;
    }
}
