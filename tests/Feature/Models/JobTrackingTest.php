<?php

declare(strict_types=1);

use App\Enums\JobStatus;
use App\Jobs\ProcessMealPlanJob;
use App\Models\JobTracking;
use App\Models\User;

it('generates uuid automatically when creating', function (): void {
    $user = User::factory()->create();

    $jobTracking = JobTracking::query()->create([
        'user_id' => $user->id,
        'job_type' => ProcessMealPlanJob::JOB_TYPE,
        'status' => JobStatus::Pending,
        'progress' => 0,
    ]);

    expect($jobTracking->uuid)->not->toBeNull()
        ->and($jobTracking->uuid)->toBeString();
});

it('marks job as started', function (): void {
    $jobTracking = JobTracking::factory()->pending()->create();

    $jobTracking->markAsStarted();

    expect($jobTracking->fresh())
        ->status->toBe(JobStatus::Processing)
        ->started_at->not->toBeNull();
});

it('updates progress', function (): void {
    $jobTracking = JobTracking::factory()->processing()->create();

    $jobTracking->updateProgress(75, 'Almost done...');

    expect($jobTracking->fresh())
        ->progress->toBe(75)
        ->message->toBe('Almost done...');
});

it('marks job as completed', function (): void {
    $jobTracking = JobTracking::factory()->processing()->create();

    $jobTracking->markAsCompleted('Success!');

    expect($jobTracking->fresh())
        ->status->toBe(JobStatus::Completed)
        ->progress->toBe(100)
        ->message->toBe('Success!')
        ->completed_at->not->toBeNull();
});

it('marks job as failed', function (): void {
    $jobTracking = JobTracking::factory()->processing()->create();

    $jobTracking->markAsFailed('Something went wrong');

    expect($jobTracking->fresh())
        ->status->toBe(JobStatus::Failed)
        ->message->toBe('Something went wrong')
        ->failed_at->not->toBeNull();
});

it('belongs to a user', function (): void {
    $user = User::factory()->create();
    $jobTracking = JobTracking::factory()->create(['user_id' => $user->id]);

    expect($jobTracking->user)->toBeInstanceOf(User::class)
        ->and($jobTracking->user->id)->toBe($user->id);
});
