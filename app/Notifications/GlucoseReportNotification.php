<?php

declare(strict_types=1);

namespace App\Notifications;

use App\DataObjects\GlucoseNotificationAnalysisData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class GlucoseReportNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly GlucoseNotificationAnalysisData $analysisResult,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $analysis = $this->analysisResult->analysisData;

        return (new MailMessage)
            ->subject('Your Weekly Glucose Report')
            ->markdown('emails.glucose-report', [
                'daysAnalyzed' => $analysis->daysAnalyzed,
                'averageGlucose' => $analysis->averages->overall,
                'timeInRangePercentage' => $analysis->timeInRange->percentage,
                'aboveRangePercentage' => $analysis->timeInRange->abovePercentage,
                'belowRangePercentage' => $analysis->timeInRange->belowPercentage,
                'totalReadings' => $analysis->totalReadings,
                'concerns' => $this->analysisResult->concerns,
                'mealPlanUrl' => $this->generateMealPlanUrl(),
            ]);
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $analysis = $this->analysisResult->analysisData;

        return [
            'type' => 'glucose_report',
            'days_analyzed' => $analysis->daysAnalyzed,
            'total_readings' => $analysis->totalReadings,
            'average_glucose' => $analysis->averages->overall,
            'time_in_range_percentage' => $analysis->timeInRange->percentage,
            'above_range_percentage' => $analysis->timeInRange->abovePercentage,
            'below_range_percentage' => $analysis->timeInRange->belowPercentage,
            'concerns' => $this->analysisResult->concerns,
            'has_concerns' => $this->analysisResult->concerns !== [],
        ];
    }

    /**
     * Generate a URL for the diabetes insights page.
     */
    private function generateMealPlanUrl(): string
    {
        return route('health-entries.insights', absolute: true);
    }
}
