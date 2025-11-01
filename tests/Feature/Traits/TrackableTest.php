<?php

declare(strict_types=1);

use App\Enums\JobStatus;
use App\Jobs\ProcessMealPlanJob;
use App\Models\User;

it('initializes job tracking', function (): void {
    $user = User::factory()->create();
    $job = new ProcessMealPlanJob($user->id);

    $tracking = $job->initializeTracking($user->id, ProcessMealPlanJob::JOB_TYPE);

    expect($tracking)->not->toBeNull()
        ->and($tracking->user_id)->toBe($user->id)
        ->and($tracking->job_type)->toBe(ProcessMealPlanJob::JOB_TYPE)
        ->and($tracking->status)->toBe(JobStatus::Pending)
        ->and($tracking->progress)->toBe(0);
});

it('starts tracking with message', function (): void {
    $user = User::factory()->create();
    $job = new ProcessMealPlanJob($user->id);
    $job->initializeTracking($user->id, ProcessMealPlanJob::JOB_TYPE);

    $job->startTracking('Starting...');

    expect($job->getTracking()->fresh())
        ->status->toBe(JobStatus::Processing)
        ->message->toBe('Starting...');
});

it('updates tracking progress', function (): void {
    $user = User::factory()->create();
    $job = new ProcessMealPlanJob($user->id);
    $job->initializeTracking($user->id, ProcessMealPlanJob::JOB_TYPE);

    $job->updateTrackingProgress(50, 'Halfway there');

    expect($job->getTracking()->fresh())
        ->progress->toBe(50)
        ->message->toBe('Halfway there');
});

it('completes tracking', function (): void {
    $user = User::factory()->create();
    $job = new ProcessMealPlanJob($user->id);
    $job->initializeTracking($user->id, ProcessMealPlanJob::JOB_TYPE);

    $job->completeTracking('Done!');

    expect($job->getTracking()->fresh())
        ->status->toBe(JobStatus::Completed)
        ->progress->toBe(100)
        ->message->toBe('Done!');
});

it('fails tracking', function (): void {
    $user = User::factory()->create();
    $job = new ProcessMealPlanJob($user->id);
    $job->initializeTracking($user->id, ProcessMealPlanJob::JOB_TYPE);

    $job->failTracking('Error occurred');

    expect($job->getTracking()->fresh())
        ->status->toBe(JobStatus::Failed)
        ->message->toBe('Error occurred');
});

it('returns null tracking when not initialized', function (): void {
    $user = User::factory()->create();
    $job = new ProcessMealPlanJob($user->id);

    expect($job->getTracking())->toBeNull();
});
