<?php

declare(strict_types=1);

use App\Actions\FailPhotoAnalysis;
use App\Actions\RetryPhotoAnalysis;
use App\Actions\StartPhotoAnalysis;
use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Contracts\Billing\ManagesPhotoAnalyses;
use App\Contracts\Billing\ResolvesFailedPhotoAnalysis;
use App\Data\Billing\PhotoAnalysisContext;
use App\Data\Billing\PhotoEntitlement;
use App\Data\FoodAnalysisData;
use App\Enums\PhotoAnalysisFailure;
use App\Enums\PhotoAnalysisStatus;
use App\Exceptions\Billing\PhotoLimitExceeded;
use App\Jobs\ProcessPhotoAnalysis;
use App\Models\AnalysisDraft;
use App\Models\MealPhoto;
use App\Models\PhotoAnalysisRequest;
use App\Models\User;
use App\Services\Billing\NullPhotoAnalyses;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\TimeoutExceededException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('accepts an encrypted recoverable analysis once without contacting the provider', function (): void {
    Queue::fake();
    $photo = UploadedFile::fake()->image('meal.jpg');
    $image = base64_encode((string) file_get_contents($photo->getRealPath()));
    $requestId = (string) Str::uuid();
    $guestId = (string) Str::uuid();
    $context = new PhotoAnalysisContext(null, $guestId, $requestId, 'public_snap_to_track', hash('sha256', $image));

    $analysis = resolve(StartPhotoAnalysis::class)->handle($requestId, $image, 'image/jpeg', $context);
    $replayed = resolve(StartPhotoAnalysis::class)->handle($requestId, $image, 'image/jpeg', $context);

    expect($replayed->id)->toBe($analysis->id)
        ->and($analysis->status)->toBe(PhotoAnalysisStatus::Queued)
        ->and($analysis->image_base64)->toBe($image)
        ->and($analysis->ownedBy(null, $guestId))->toBeTrue()
        ->and($analysis->ownedBy(null, (string) Str::uuid()))->toBeFalse()
        ->and($analysis->context()->guestId)->toBe($guestId)
        ->and(DB::table('photo_analysis_requests')->value('image_base64'))->not->toBe($image)
        ->and(DB::table('photo_analysis_requests')->value('guest_id'))->not->toBe($guestId)
        ->and($analysis->toArray())->not->toHaveKeys(['image_base64', 'guest_id', 'draft_token']);

    Queue::assertPushed(ProcessPhotoAnalysis::class, 1);
    expect(PhotoAnalysisRequest::query()->count())->toBe(1);
});

it('completes a slow queued analysis once and replays its durable draft without the input', function (): void {
    $request = PhotoAnalysisRequest::factory()->create();
    FoodPhotoAnalyzerAgent::fake([AnalysisDraft::factory()->make()->payload]);

    app()->call([new ProcessPhotoAnalysis($request->id), 'handle']);
    app()->call([new ProcessPhotoAnalysis($request->id), 'handle']);

    $request->refresh();
    expect($request->status)->toBe(PhotoAnalysisStatus::Succeeded)
        ->and($request->image_base64)->toBeNull()
        ->and($request->draft_token)->not->toBeNull()
        ->and(AnalysisDraft::query()->count())->toBe(1)
        ->and(AnalysisDraft::query()->sole()->token_hash)->toBe(AnalysisDraft::hashToken($request->draft_token))
        ->and(DB::table('photo_analysis_requests')->value('draft_token'))->not->toBe($request->draft_token);
    FoodPhotoAnalyzerAgent::assertPromptedTimes(1);
});

it('keeps an ordinary provider failure recoverable without creating a draft', function (): void {
    $request = PhotoAnalysisRequest::factory()->create();
    FoodPhotoAnalyzerAgent::fake(fn () => throw new RuntimeException('Provider unavailable'));

    expect(fn () => app()->call([new ProcessPhotoAnalysis($request->id), 'handle']))->toThrow(RuntimeException::class, 'Provider unavailable');

    $request->refresh();
    expect($request->status)->toBe(PhotoAnalysisStatus::Failed)
        ->and($request->failure_code)->toBe(PhotoAnalysisFailure::ProviderError)
        ->and($request->image_base64)->not->toBeNull()
        ->and($request->canRetry())->toBeTrue()
        ->and(AnalysisDraft::query()->count())->toBe(0);
});

it('recovers a charged result if the worker dies before it creates the public draft', function (): void {
    $request = PhotoAnalysisRequest::factory()->create(['status' => PhotoAnalysisStatus::Processing]);
    $result = FoodAnalysisData::from(AnalysisDraft::factory()->make()->payload);
    $resolver = Mockery::mock(ResolvesFailedPhotoAnalysis::class);
    $resolver->shouldReceive('handle')->once()->withArgs(fn (PhotoAnalysisContext $context): bool => $context->requestId === $request->request_id)->andReturn($result);
    app()->instance(ResolvesFailedPhotoAnalysis::class, $resolver);

    $job = new ProcessPhotoAnalysis($request->id);
    $job->failed(new TimeoutExceededException('Timed out'));
    $job->failed(new TimeoutExceededException('Repeated failure callback'));

    $request->refresh();
    expect($request->status)->toBe(PhotoAnalysisStatus::Succeeded)
        ->and($request->image_base64)->toBeNull()
        ->and($request->draft_token)->not->toBeNull()
        ->and(AnalysisDraft::query()->count())->toBe(1);
});

it("keeps a signed-in member's meal photo when their charged result is recovered", function (): void {
    Storage::fake('s3');
    $member = User::factory()->create();
    $photo = UploadedFile::fake()->image('meal.jpg');
    $image = base64_encode((string) file_get_contents($photo->getRealPath()));
    $request = PhotoAnalysisRequest::factory()->create([
        'status' => PhotoAnalysisStatus::Processing, 'user_id' => $member->id, 'guest_id' => null, 'guest_hash' => null,
        'image_base64' => $image, 'fingerprint' => hash('sha256', $image), 'mime_type' => 'image/jpeg',
    ]);
    $resolver = Mockery::mock(ResolvesFailedPhotoAnalysis::class);
    $resolver->shouldReceive('handle')->once()->andReturn(FoodAnalysisData::from(AnalysisDraft::factory()->make()->payload));
    app()->instance(ResolvesFailedPhotoAnalysis::class, $resolver);

    new ProcessPhotoAnalysis($request->id)->failed(new TimeoutExceededException('Timed out'));

    $mealPhoto = MealPhoto::query()->sole();
    expect($request->refresh()->status)->toBe(PhotoAnalysisStatus::Succeeded)
        ->and($request->image_base64)->toBeNull()
        ->and($mealPhoto->user_id)->toBe($member->id)
        ->and($mealPhoto->analysis_draft_id)->toBe(AnalysisDraft::query()->sole()->id);
    Storage::disk('s3')->assertExists($mealPhoto->path);
});

it('reconciles stale processing or abandoned queued work without starting it again', function (PhotoAnalysisStatus $status, PhotoAnalysisFailure $code): void {
    $request = PhotoAnalysisRequest::factory()->create(['status' => $status, 'deadline_at' => now()->subSecond()]);
    FoodPhotoAnalyzerAgent::fake()->preventStrayPrompts();

    expect($request->staleFailureCode())->toBe($code)
        ->and(resolve(FailPhotoAnalysis::class)->handle($request, $code, onlyIfStale: true))->toBeTrue()
        ->and($request->staleFailureCode())->toBeNull()
        ->and(resolve(FailPhotoAnalysis::class)->handle($request, $code, onlyIfStale: true))->toBeFalse();
    app()->call([new ProcessPhotoAnalysis($request->id), 'handle']);

    expect($request->refresh()->status)->toBe(PhotoAnalysisStatus::Failed)
        ->and($request->failure_code)->toBe($code)
        ->and($request->canRetry())->toBeTrue();
    FoodPhotoAnalyzerAgent::assertNeverPrompted();
})->with([
    'worker stopped' => [PhotoAnalysisStatus::Processing, PhotoAnalysisFailure::Timeout],
    'queue abandoned' => [PhotoAnalysisStatus::Queued, PhotoAnalysisFailure::QueueTimeout],
]);

it('retries a failed photo once with a new immutable request id after a lost retry response', function (): void {
    Queue::fake();
    $previous = PhotoAnalysisRequest::factory()->create(['status' => PhotoAnalysisStatus::Failed, 'expires_at' => now()->addMinutes(12)]);
    $originalId = $previous->request_id;

    $retry = resolve(RetryPhotoAnalysis::class)->handle($previous, null, $previous->guest_id);
    $again = resolve(RetryPhotoAnalysis::class)->handle($previous, null, $previous->guest_id);

    expect($retry->request_id)->not->toBe($originalId)
        ->and($again->id)->toBe($retry->id)
        ->and($previous->refresh()->request_id)->toBe($originalId)
        ->and($previous->retry_request_id)->toBe($retry->request_id)
        ->and($previous->image_base64)->toBeNull()
        ->and($retry->expires_at->equalTo($previous->expires_at))->toBeTrue();
    Queue::assertPushed(ProcessPhotoAnalysis::class, 1);
});

it('does not expose a user-bound request through its former guest identity', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $request = PhotoAnalysisRequest::factory()->create(['user_id' => $owner->id]);

    expect($request->ownedBy($owner, null))->toBeTrue()
        ->and($request->ownedBy($other, $request->guest_id))->toBeFalse()
        ->and($request->ownedBy(null, $request->guest_id))->toBeFalse()
        ->and(PhotoAnalysisRequest::query()->metadata()->first()->getAttributes())->not->toHaveKey('image_base64');
});

it('rejects replaying another guest request without creating work', function (): void {
    Queue::fake();
    $request = PhotoAnalysisRequest::factory()->create();
    $context = new PhotoAnalysisContext(null, (string) Str::uuid(), $request->request_id, $request->source, $request->fingerprint);

    try {
        resolve(StartPhotoAnalysis::class)->handle($request->request_id, $request->image_base64, $request->mime_type, $context);
        test()->fail('The request owner must be checked.');
    } catch (HttpException $httpException) {
        expect($httpException->getStatusCode())->toBe(404);
    }

    expect(PhotoAnalysisRequest::query()->count())->toBe(1);
    Queue::assertNothingPushed();
});

it('expires a stalled input and refuses retrying it past its recovery window', function (): void {
    Queue::fake();
    $request = PhotoAnalysisRequest::factory()->create(['deadline_at' => now()->subHour(), 'expires_at' => now()->subSecond()]);

    expect(resolve(FailPhotoAnalysis::class)->handle($request, PhotoAnalysisFailure::Expired, onlyIfStale: true))->toBeTrue()
        ->and($request->image_base64)->toBeNull()
        ->and($request->canRetry())->toBeFalse()
        ->and(new PhotoAnalysisRequest()->prunable()->whereKey($request->id)->exists())->toBeTrue();

    try {
        resolve(RetryPhotoAnalysis::class)->handle($request, null, $request->guest_id);
        test()->fail('Expired input must not be retried.');
    } catch (HttpException $httpException) {
        expect($httpException->getStatusCode())->toBe(410);
    }

    Queue::assertNothingPushed();
});

it('does not run a second provider call for a redelivered processing job', function (): void {
    $request = PhotoAnalysisRequest::factory()->create(['status' => PhotoAnalysisStatus::Processing, 'deadline_at' => now()->addMinute()]);
    FoodPhotoAnalyzerAgent::fake()->preventStrayPrompts();

    app()->call([new ProcessPhotoAnalysis($request->id), 'handle']);

    expect($request->refresh()->status)->toBe(PhotoAnalysisStatus::Processing);
    FoodPhotoAnalyzerAgent::assertNeverPrompted();
});

it('keeps a completed result when a later job failure callback arrives', function (): void {
    $request = PhotoAnalysisRequest::factory()->create();
    FoodPhotoAnalyzerAgent::fake([AnalysisDraft::factory()->make()->payload]);
    $job = new ProcessPhotoAnalysis($request->id);
    app()->call($job->handle(...));
    $token = $request->refresh()->draft_token;

    $job->failed(new RuntimeException('Failure after successful persistence'));

    expect($request->refresh()->status)->toBe(PhotoAnalysisStatus::Succeeded)
        ->and($request->draft_token)->toBe($token)
        ->and(AnalysisDraft::query()->count())->toBe(1);
});

it('refuses a synchronous queue instead of holding the interactive request open', function (): void {
    config()->set('queue.default', 'sync');
    $request = PhotoAnalysisRequest::factory()->make();
    FoodPhotoAnalyzerAgent::fake()->preventStrayPrompts();

    try {
        resolve(StartPhotoAnalysis::class)->handle($request->request_id, $request->image_base64, $request->mime_type, $request->context());
        test()->fail('A durable background queue is required.');
    } catch (HttpException $httpException) {
        expect($httpException->getStatusCode())->toBe(503);
    }

    expect(PhotoAnalysisRequest::query()->count())->toBe(0);
    FoodPhotoAnalyzerAgent::assertNeverPrompted();
});

it('keeps a failed queue submission recoverable when dispatch fails after commit', function (): void {
    config()->set('queue.default', 'database');
    $queue = Mockery::mock(Illuminate\Contracts\Queue\Queue::class);
    $queue->shouldReceive('push')->once()->andReturnUsing(function (): string {
        DB::afterCommit(fn () => throw new RuntimeException('Queue unavailable'));

        return 'pending-dispatch';
    });
    Queue::shouldReceive('connection')->with(null)->andReturn($queue);
    $request = PhotoAnalysisRequest::factory()->make();

    expect(fn () => resolve(StartPhotoAnalysis::class)->handle($request->request_id, $request->image_base64, $request->mime_type, $request->context()))
        ->toThrow(RuntimeException::class, 'Queue unavailable');

    $stored = PhotoAnalysisRequest::query()->sole();
    expect($stored->status)->toBe(PhotoAnalysisStatus::Failed)
        ->and($stored->failure_code)->toBe(PhotoAnalysisFailure::DispatchFailed)
        ->and($stored->canRetry())->toBeTrue()
        ->and($stored->image_base64)->not->toBeNull();
});

it('keeps the retry child recoverable when queue dispatch fails after its outer commit', function (): void {
    config()->set('queue.default', 'database');
    $queue = Mockery::mock(Illuminate\Contracts\Queue\Queue::class);
    $queue->shouldReceive('push')->once()->andReturnUsing(function (): string {
        DB::afterCommit(fn () => throw new RuntimeException('Retry queue unavailable'));

        return 'pending-retry';
    });
    Queue::shouldReceive('connection')->with(null)->andReturn($queue);
    $previous = PhotoAnalysisRequest::factory()->create(['status' => PhotoAnalysisStatus::Failed]);

    expect(fn () => resolve(RetryPhotoAnalysis::class)->handle($previous, null, $previous->guest_id))
        ->toThrow(RuntimeException::class, 'Retry queue unavailable');

    $retry = PhotoAnalysisRequest::query()->where('request_id', $previous->refresh()->retry_request_id)->sole();
    expect($retry->status)->toBe(PhotoAnalysisStatus::Failed)
        ->and($retry->failure_code)->toBe(PhotoAnalysisFailure::DispatchFailed)
        ->and($retry->image_base64)->not->toBeNull();
});

it('does not call the provider when the host analysis fails before the manager starts', function (): void {
    $request = PhotoAnalysisRequest::factory()->create();
    FoodPhotoAnalyzerAgent::fake()->preventStrayPrompts();
    app()->instance(ManagesPhotoAnalyses::class, new readonly class($request) implements ManagesPhotoAnalyses
    {
        public function __construct(private PhotoAnalysisRequest $request) {}

        public function enabled(): bool
        {
            return false;
        }

        public function entitlement(?User $user, ?string $guestId): PhotoEntitlement
        {
            return new PhotoEntitlement;
        }

        public function analyze(PhotoAnalysisContext $context, Closure $analyze): FoodAnalysisData
        {
            resolve(FailPhotoAnalysis::class)->handle($this->request, PhotoAnalysisFailure::Timeout);

            return new NullPhotoAnalyses()->analyze($context, $analyze);
        }
    });

    app()->call([new ProcessPhotoAnalysis($request->id), 'handle']);
    expect($request->refresh()->status)->toBe(PhotoAnalysisStatus::Failed)
        ->and(AnalysisDraft::query()->count())->toBe(0);
    FoodPhotoAnalyzerAgent::assertNeverPrompted();
});

it('refuses retrying a failed photo without enough recovery time for a bounded attempt', function (): void {
    $this->freezeTime();
    Queue::fake();
    $previous = PhotoAnalysisRequest::factory()->create(['status' => PhotoAnalysisStatus::Failed, 'expires_at' => now()->addSeconds(30)]);

    expect($previous->canRetry())->toBeFalse();
    try {
        resolve(RetryPhotoAnalysis::class)->handle($previous, null, $previous->guest_id);
        test()->fail('A retry must have time to finish within the recovery window.');
    } catch (HttpException $httpException) {
        expect($httpException->getStatusCode())->toBe(410);
    }

    $this->assertDatabaseCount('photo_analysis_requests', 1);
    Queue::assertNothingPushed();
});

it('finishes an admitted analysis when its remaining window falls below the admission budget while processing', function (): void {
    $this->freezeTime();
    $request = PhotoAnalysisRequest::factory()->create(['expires_at' => now()->addSeconds(130)]);
    $payload = AnalysisDraft::factory()->make()->payload;
    FoodPhotoAnalyzerAgent::fake(function () use ($request, $payload): array {
        test()->travel(30)->seconds();

        expect($request->fresh()->staleFailureCode())->toBeNull();

        return $payload;
    });

    app()->call([new ProcessPhotoAnalysis($request->id), 'handle']);

    expect($request->refresh()->status)->toBe(PhotoAnalysisStatus::Succeeded)
        ->and($request->draft_token)->not->toBeNull()
        ->and(AnalysisDraft::query()->count())->toBe(1);
    FoodPhotoAnalyzerAgent::assertPromptedTimes(1);
});

it('does not start nearly expired queued work after waiting for a worker', function (): void {
    $this->freezeTime();
    $request = PhotoAnalysisRequest::factory()->create(['expires_at' => now()->addSeconds(30)]);
    FoodPhotoAnalyzerAgent::fake()->preventStrayPrompts();

    app()->call([new ProcessPhotoAnalysis($request->id), 'handle']);

    expect($request->refresh()->status)->toBe(PhotoAnalysisStatus::Failed)
        ->and($request->failure_code)->toBe(PhotoAnalysisFailure::Expired)
        ->and($request->canRetry())->toBeFalse()
        ->and($request->failureMessage())->toContain('Choose a photo to start a new scan.')
        ->and(AnalysisDraft::query()->count())->toBe(0);
    FoodPhotoAnalyzerAgent::assertNeverPrompted();
});

it('settles a scan that runs out of allowance without failing the job', function (): void {
    $request = PhotoAnalysisRequest::factory()->create();
    FoodPhotoAnalyzerAgent::fake()->preventStrayPrompts();
    app()->instance(ManagesPhotoAnalyses::class, new readonly class implements ManagesPhotoAnalyses
    {
        public function enabled(): bool
        {
            return true;
        }

        public function entitlement(?User $user, ?string $guestId): PhotoEntitlement
        {
            return new PhotoEntitlement(enabled: true, limit: 2, used: 2, mode: 'trial');
        }

        public function analyze(PhotoAnalysisContext $context, Closure $analyze): FoodAnalysisData
        {
            throw new PhotoLimitExceeded($this->entitlement($context->user, $context->guestId));
        }
    });

    app()->call([new ProcessPhotoAnalysis($request->id), 'handle']);

    expect($request->refresh()->status)->toBe(PhotoAnalysisStatus::Failed)
        ->and($request->failure_code)->toBe(PhotoAnalysisFailure::LimitReached)
        ->and($request->failureMessage())->toBe('Your scan allowance is used up. Choose a plan to continue.')
        ->and(AnalysisDraft::query()->count())->toBe(0);
    FoodPhotoAnalyzerAgent::assertNeverPrompted();
});

it('leaves an analysis running when it stops being stale before the poll can settle it', function (): void {
    $request = PhotoAnalysisRequest::factory()->create(['status' => PhotoAnalysisStatus::Processing, 'deadline_at' => now()->subSecond()]);
    PhotoAnalysisRequest::query()->whereKey($request->id)->update(['deadline_at' => now()->addMinute()]);

    expect($request->staleFailureCode())->toBe(PhotoAnalysisFailure::Timeout)
        ->and(resolve(FailPhotoAnalysis::class)->handle($request, PhotoAnalysisFailure::Timeout, onlyIfStale: true))->toBeFalse()
        ->and($request->fresh()?->status)->toBe(PhotoAnalysisStatus::Processing);
});
