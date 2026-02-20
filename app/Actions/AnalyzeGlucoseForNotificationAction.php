<?php

declare(strict_types=1);

namespace App\Actions;

use App\Ai\GlucoseDataAnalyzer;
use App\DataObjects\GlucoseAnalysis\GlucoseAnalysisData;
use App\DataObjects\GlucoseNotificationAnalysisData;
use App\DataObjects\UserSettingsData;
use App\Models\User;

final readonly class AnalyzeGlucoseForNotificationAction
{
    public function __construct(private GlucoseDataAnalyzer $analyzer) {}

    public function handle(User $user, ?int $daysBack = null): GlucoseNotificationAnalysisData
    {
        $settings = $user->notification_settings;
        $daysBack ??= $this->getAnalysisWindowDays();

        $analysisData = $this->analyzer->handle($user, $daysBack);

        if (! $settings->glucoseNotificationsEnabled || ! $analysisData->hasData) {
            return new GlucoseNotificationAnalysisData(
                shouldNotify: false,
                concerns: [],
                analysisData: $analysisData,
            );
        }

        $concerns = $this->evaluateConcerns($settings, $analysisData);

        return new GlucoseNotificationAnalysisData(
            shouldNotify: $concerns !== [],
            concerns: $concerns,
            analysisData: $analysisData,
        );
    }

    /**
     * Evaluate concerns based on user thresholds and analysis patterns.
     *
     * @return array<int, string>
     */
    private function evaluateConcerns(UserSettingsData $settings, GlucoseAnalysisData $analysisData): array
    {
        $concerns = [];
        $highThreshold = $settings->effectiveHighThreshold();
        $lowThreshold = $settings->effectiveLowThreshold();
        $highReadingsPercentTrigger = $this->getHighReadingsPercentTrigger();

        // Check for high readings exceeding configured trigger percentage
        if ($analysisData->timeInRange->abovePercentage >= $highReadingsPercentTrigger) {
            $concerns[] = sprintf(
                '%.0f%% of your readings were above %d mg/dL in the past %d days.',
                $analysisData->timeInRange->abovePercentage,
                $highThreshold,
                $analysisData->daysAnalyzed
            );
        }

        // Check for low readings (below hypoglycemia threshold)
        if ($analysisData->timeInRange->belowPercentage > 0 && $analysisData->patterns->hypoglycemiaRisk !== 'none') {
            $concerns[] = sprintf(
                '%.0f%% of your readings were below %d mg/dL, indicating potential hypoglycemia risk.',
                $analysisData->timeInRange->belowPercentage,
                $lowThreshold
            );
        }

        // Check for consistently high or low patterns
        if ($analysisData->patterns->consistentlyHigh && $analysisData->averages->overall !== null) {
            $concerns[] = sprintf(
                'Your average glucose of %.1f mg/dL is consistently elevated.',
                $analysisData->averages->overall
            );
        }

        if ($analysisData->patterns->consistentlyLow && $analysisData->averages->overall !== null) {
            $concerns[] = sprintf(
                'Your average glucose of %.1f mg/dL is consistently low.',
                $analysisData->averages->overall
            );
        }

        // Check for high variability (only if combined with other concerning patterns)
        if ($analysisData->patterns->highVariability &&
            ($analysisData->patterns->consistentlyHigh || $analysisData->patterns->postMealSpikes || $analysisData->timeInRange->percentage < 70)) {
            $concerns[] = 'High glucose variability detected, indicating inconsistent blood sugar control.';
        }

        // Check for post-meal spikes (only if average post-meal is concerning)
        if ($analysisData->patterns->postMealSpikes &&
            $analysisData->averages->postMeal !== null &&
            $analysisData->averages->postMeal > $highThreshold) {
            $concerns[] = 'Frequent post-meal glucose spikes detected.';
        }

        // Check for rising trend
        if ($analysisData->trend->direction === 'rising' && $analysisData->trend->slopePerWeek !== null && $analysisData->trend->slopePerWeek > 5) {
            $concerns[] = sprintf(
                'Your glucose levels are trending upward by %.1f mg/dL per week.',
                $analysisData->trend->slopePerWeek
            );
        }

        return $concerns;
    }

    private function getAnalysisWindowDays(): int
    {
        $value = config('glucose.analysis_window_days');

        return is_int($value) ? $value : 7;
    }

    private function getHighReadingsPercentTrigger(): float
    {
        $value = config('glucose.high_readings_percent_trigger');

        return is_numeric($value) ? (float) $value : 30.0;
    }
}
