<?php

declare(strict_types=1);

namespace App\Traits;

use App\Enums\JobStatus;
use App\Models\JobTracking;

trait Trackable
{
    protected ?JobTracking $tracking = null;

    public function initializeTracking(int $userId, string $jobType): JobTracking
    {
        $this->tracking = JobTracking::query()->create([
            'user_id' => $userId,
            'job_type' => $jobType,
            'status' => JobStatus::Pending,
            'progress' => 0,
        ]);

        return $this->tracking;
    }

    public function startTracking(?string $message = null): void
    {
        if ($this->tracking) {
            $this->tracking->markAsStarted();
            if ($message) {
                $this->tracking->updateProgress(0, $message);
            }
        }
    }

    public function updateTrackingProgress(int $progress, ?string $message = null): void
    {
        if ($this->tracking) {
            $this->tracking->updateProgress($progress, $message);
        }
    }

    public function completeTracking(?string $message = null): void
    {
        if ($this->tracking) {
            $this->tracking->markAsCompleted($message);
        }
    }

    public function failTracking(?string $message = null): void
    {
        if ($this->tracking) {
            $this->tracking->markAsFailed($message);
        }
    }

    public function getTracking(): ?JobTracking
    {
        return $this->tracking;
    }
}
