<?php

declare(strict_types=1);

use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Contracts\Billing\ManagesPhotoAnalyses;
use App\Data\Billing\PhotoAnalysisContext;
use App\Data\Billing\PhotoEntitlement;
use App\Data\Billing\PhotoModel;
use App\Data\Billing\PhotoOffer;
use App\Data\FoodAnalysisData;
use App\Enums\AnalysisDraftSource;
use App\Enums\PhotoAnalysisStatus;
use App\Jobs\ProcessPhotoAnalysis;
use App\Models\AnalysisDraft;
use App\Models\MealPhoto;
use App\Models\PhotoAnalysisRequest;
use App\Models\ReferenceFood;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Laravel\Ai\Prompts\AgentPrompt;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use RyanChandler\LaravelCloudflareTurnstile\Contracts\ClientInterface;
use RyanChandler\LaravelCloudflareTurnstile\Facades\Turnstile;

function fakeTurnstileForSnapToTrack(bool $success = true): void
{
    if ($success) {
        Turnstile::fake();
    } else {
        Turnstile::fake()->fail();
    }
}

function verifiedTurnstileTokenForSnapToTrack(): string
{
    $token = ClientInterface::RESPONSE_DUMMY_TOKEN;

    Cache::put('snap-to-track-turnstile:'.sha1($token).':127.0.0.1', true, now()->addMinutes(10));

    return $token;
}

function analyzePhotoForSnapToTrack(array $analysis, string $fileName = 'food.jpg'): Testable
{
    fakeTurnstileForSnapToTrack();

    FoodPhotoAnalyzerAgent::fake([$analysis]);
    Queue::fake([ProcessPhotoAnalysis::class]);

    $component = Livewire::test('pages::snap-to-track')
        ->set('photo', UploadedFile::fake()->image($fileName))
        ->set('turnstileToken', verifiedTurnstileTokenForSnapToTrack())
        ->call('analyze');

    return finishQueuedSnapAnalysis($component);
}

function finishQueuedSnapAnalysis(Testable $component): Testable
{
    Queue::assertPushed(ProcessPhotoAnalysis::class, 1);
    app()->call([new ProcessPhotoAnalysis(PhotoAnalysisRequest::query()->latest('id')->firstOrFail()->id), 'handle']);

    return $component->call('refreshAnalysis');
}

function finishDirectUploadForSnapToTrack(Testable $component, UploadedFile $photo): string
{
    Storage::fake(FileUploadConfiguration::disk());

    $path = (string) FileUploadConfiguration::storeTemporaryFile($photo, FileUploadConfiguration::disk());

    $component->call('_finishUpload', 'photo', [TemporaryUploadedFile::signPath(basename($path))], false);

    return $path;
}

beforeEach(function (): void {
    RateLimiter::clear('snap-to-track:127.0.0.1');
    RateLimiter::clear('snap-to-track-upload:127.0.0.1');
    Cache::forget('snap-to-track-turnstile:'.sha1(ClientInterface::RESPONSE_DUMMY_TOKEN).':127.0.0.1');
});

it('renders the snap to track page', function (): void {
    Livewire::test('pages::snap-to-track')
        ->assertStatus(200)
        ->assertSee('Snap to Track')
        ->assertSee('Track calories & macros instantly with AI');
});

it('shows upload area when no photo is selected', function (): void {
    Livewire::test('pages::snap-to-track')
        ->assertSeeInOrder(['Tap to take photo or upload', 'JPG, PNG up to 10MB', 'Take photo', 'Choose photo']);
});

it('validates photo is required', function (): void {
    fakeTurnstileForSnapToTrack();

    Livewire::test('pages::snap-to-track')
        ->set('turnstileToken', Turnstile::dummy())
        ->call('analyze')
        ->assertHasErrors(['photo' => 'required']);
});

it('validates photo max size', function (): void {
    fakeTurnstileForSnapToTrack();

    $file = UploadedFile::fake()->image('large-image.jpg')->size(11000);

    Livewire::test('pages::snap-to-track')
        ->set('photo', $file)
        ->set('turnstileToken', Turnstile::dummy())
        ->call('analyze')
        ->assertHasErrors(['photo']);
});

it('previews and analyzes JPG, PNG and WebP photos', function (string $fileName, string $mimeType): void {
    fakeTurnstileForSnapToTrack();
    FoodPhotoAnalyzerAgent::fake([saveMealAnalysisFixture()]);
    Queue::fake([ProcessPhotoAnalysis::class]);

    $component = Livewire::test('pages::snap-to-track')
        ->set('photo', UploadedFile::fake()->image($fileName))
        ->assertHasNoErrors('photo')
        ->assertSee('Food photo preview')
        ->set('turnstileToken', verifiedTurnstileTokenForSnapToTrack())
        ->call('analyze')
        ->assertNoRedirect();

    finishQueuedSnapAnalysis($component)->assertRedirect(route('snap-to-track.result'));

    FoodPhotoAnalyzerAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->attachments->sole()->mimeType() === $mimeType);
})->with([
    'JPG' => ['meal.jpg', 'image/jpeg'],
    'PNG' => ['meal.png', 'image/png'],
    'WebP' => ['meal.webp', 'image/webp'],
]);

it('asks for a JPG or PNG when an uploaded photo format cannot be analyzed', function (string $fileName, string $mimeType): void {
    $component = Livewire::test('pages::snap-to-track');

    $path = finishDirectUploadForSnapToTrack($component, UploadedFile::fake()->create($fileName, 120, $mimeType));

    $component
        ->assertOk()
        ->assertSet('photo', null)
        ->assertHasErrors(['photo' => 'mimes'])
        ->assertSee('We can’t read this photo format. Please use a JPG or PNG photo.')
        ->assertSee('Tap to take photo or upload');

    expect(FileUploadConfiguration::storage()->exists($path))->toBeFalse();
})->with([
    'AVIF' => ['meal.avif', 'image/avif'],
    'HEIC' => ['meal.heic', 'image/heic'],
    'HEIF' => ['meal.heif', 'image/heif'],
]);

it('shows a helpful HEIC error from the real temporary upload endpoint', function (): void {
    fakeTurnstileForSnapToTrack();
    Storage::fake(FileUploadConfiguration::disk());

    $component = Livewire::test('pages::snap-to-track')
        ->set('turnstileToken', Turnstile::dummy())
        ->call('_startUpload', 'photo', [['name' => 'meal.heic', 'size' => filesize(base_path('tests/Fixtures/meal.heic')), 'type' => 'image/heic']], false);

    $response = $this->post(URL::temporarySignedRoute('livewire.upload-file', now()->addMinutes(5)), [
        'files' => [new UploadedFile(base_path('tests/Fixtures/meal.heic'), 'meal.heic', 'image/heic', test: true)],
    ], ['Accept' => 'application/json']);

    $response->assertUnprocessable()
        ->assertInvalid(['files.0' => 'We can’t read this photo format. Please use a JPG or PNG photo.']);

    $component->call('_uploadErrored', 'photo', $response->getContent(), false)
        ->assertSet('photo', null)
        ->assertHasErrors('photo')
        ->assertSee('We can’t read this photo format. Please use a JPG or PNG photo.')
        ->assertDontSee('The photo field must be a file of type:');

    expect(FileUploadConfiguration::storage()->allFiles())->toBeEmpty();
});

it('asks for a JPG or PNG when a photo labelled as JPG is not really an image', function (): void {
    fakeTurnstileForSnapToTrack();
    FoodPhotoAnalyzerAgent::fake([saveMealAnalysisFixture()]);

    $component = Livewire::test('pages::snap-to-track');

    finishDirectUploadForSnapToTrack($component, UploadedFile::fake()->createWithContent('meal.jpg', 'not a photo'));

    $component
        ->set('turnstileToken', verifiedTurnstileTokenForSnapToTrack())
        ->call('analyze')
        ->assertHasErrors(['photo'])
        ->assertSet('photo', null)
        ->assertSee('We can’t read this photo format. Please use a JPG or PNG photo.')
        ->assertNoRedirect();

    FoodPhotoAnalyzerAgent::assertNeverPrompted();
});

it('shows analyze button after photo is uploaded', function (): void {
    $file = UploadedFile::fake()->image('food.jpg');

    Livewire::test('pages::snap-to-track')
        ->set('photo', $file)
        ->assertSee('Analyze Food')
        ->assertDontSee('Tap to take photo or upload');
});

it('clears the photo and reloads verification for the next upload', function (): void {
    $file = UploadedFile::fake()->image('food.jpg');

    $component = Livewire::test('pages::snap-to-track')
        ->set('photo', $file);

    $photo = $component->instance()->photo;

    expect($photo)
        ->toBeInstanceOf(TemporaryUploadedFile::class)
        ->and(file_exists($photo->getRealPath()))->toBeTrue();

    $component
        ->call('clearPhoto')
        ->assertRedirect(route('snap-to-track'))
        ->assertSet('photo', null)
        ->assertSet('error', null);

    expect(file_exists($photo->getRealPath()))->toBeFalse();
});

it('sends a guest to the result page with their analysis kept in a guest draft', function (): void {
    Storage::fake('s3');

    $component = analyzePhotoForSnapToTrack([
        'items' => [
            ['name' => 'Grilled Chicken', 'calories' => 165, 'protein' => 31, 'carbs' => 0, 'fat' => 3.6, 'portion' => '100g'],
        ],
        'total_calories' => 165,
        'total_protein' => 31,
        'total_carbs' => 0,
        'total_fat' => 3.6,
        'confidence' => 85,
    ])
        ->assertRedirect(route('snap-to-track.result'))
        ->assertSet('photo', null);

    $draft = AnalysisDraft::query()->sole();

    expect($draft->user_id)->toBeNull()
        ->and($draft->source)->toBe(AnalysisDraftSource::PublicSnapToTrack)
        ->and($draft->payload['total_calories'])->toEqual(165)
        ->and($draft->payload['confidence'])->toBe(85)
        ->and(AnalysisDraft::hashToken(session('snap_to_track.result.draft')))->toBe($draft->token_hash)
        ->and(session('snap_to_track.result.request_id'))->toBe($component->instance()->analysisRequestId)
        ->and(MealPhoto::query()->count())->toBe(0)
        ->and(Storage::disk('s3')->allFiles())->toBe([]);
});

it('keeps every detected item in the guest draft', function (): void {
    analyzePhotoForSnapToTrack([
        'items' => [
            ['name' => 'Rice', 'calories' => 130, 'protein' => 2.7, 'carbs' => 28, 'fat' => 0.3, 'portion' => '100g'],
            ['name' => 'Chicken', 'calories' => 165, 'protein' => 31, 'carbs' => 0, 'fat' => 3.6, 'portion' => '100g'],
        ],
        'total_calories' => 295,
        'total_protein' => 33.7,
        'total_carbs' => 28,
        'total_fat' => 3.9,
        'confidence' => 90,
    ])->assertRedirect(route('snap-to-track.result'));

    expect(array_column(AnalysisDraft::query()->sole()->payload['items'], 'name'))->toBe(['Rice', 'Chicken']);
});

it('sends a signed-in member straight to the claimed draft review', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $component = analyzePhotoForSnapToTrack(saveMealAnalysisFixture())->assertRedirect();

    $draft = AnalysisDraft::query()->sole();
    $token = Str::afterLast($component->effects['redirect'], '/review/');

    expect($component->effects['redirect'])->toBe(route('snap-to-track.review', ['draft' => $token]))
        ->and(AnalysisDraft::hashToken($token))->toBe($draft->token_hash)
        ->and($draft->user_id)->toBe($user->id)
        ->and($draft->claimed_at)->not->toBeNull()
        ->and(session('snap_to_track.result'))->toBeNull();
});

it('keeps a signed-in member photo with the meal they log from the public tool', function (): void {
    Storage::fake('s3');
    $user = User::factory()->create();
    $this->actingAs($user);

    $component = analyzePhotoForSnapToTrack(saveMealAnalysisFixture())->assertRedirect();

    $draft = AnalysisDraft::query()->sole();
    $photo = MealPhoto::query()->sole();

    expect($photo->user_id)->toBe($user->id)
        ->and($photo->analysis_draft_id)->toBe($draft->id);
    Storage::disk('s3')->assertExists($photo->path);

    $token = Str::afterLast($component->effects['redirect'], '/review/');
    $this->post(route('snap-to-track.review.store', ['draft' => $token]), [
        'items' => [['name' => 'Grilled Chicken', 'portion' => '100g', 'calories' => 165, 'protein' => 31, 'carbs' => 0, 'fat' => 3.6, 'provenance' => 'model']],
        'measured_at' => now()->toIso8601String(),
    ])->assertRedirect();

    $groupId = (string) $draft->refresh()->health_group_id;

    expect($photo->refresh()->health_group_id)->toBe($groupId);
    $this->get(route('health-entries.index'))->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
        ->where('entries.0.photo_url', route('health-entries.photo', ['group' => $groupId], absolute: false)));
});

it('displays error when analysis fails', function (): void {
    fakeTurnstileForSnapToTrack();

    FoodPhotoAnalyzerAgent::fake(function (): void {
        throw new Exception('AI analysis failed');
    });
    Queue::fake([ProcessPhotoAnalysis::class]);

    $file = UploadedFile::fake()->image('food.jpg');

    $component = Livewire::test('pages::snap-to-track')
        ->set('photo', $file);

    $photo = $component->instance()->photo;

    expect($photo)
        ->toBeInstanceOf(TemporaryUploadedFile::class)
        ->and(file_exists($photo->getRealPath()))->toBeTrue();

    $component
        ->set('turnstileToken', verifiedTurnstileTokenForSnapToTrack())
        ->call('analyze')
        ->assertSet('loading', true);

    Queue::assertPushed(ProcessPhotoAnalysis::class, 1);
    expect(fn () => app()->call([new ProcessPhotoAnalysis(PhotoAnalysisRequest::query()->sole()->id), 'handle']))
        ->toThrow(Exception::class, 'AI analysis failed');

    $component->call('refreshAnalysis')
        ->assertSet('canRetryAnalysis', true)
        ->assertSet('photo', null)
        ->assertNoRedirect();

    expect(AnalysisDraft::query()->count())->toBe(0);

    expect(file_exists($photo->getRealPath()))->toBeFalse();
});

it('shows tips for best results when no photo is selected', function (): void {
    Livewire::test('pages::snap-to-track')
        ->assertSee('Tips for best results')
        ->assertSee('Take photo in good lighting')
        ->assertSee('Make sure all food is visible');
});

it('shows disclaimer about AI estimates', function (): void {
    Livewire::test('pages::snap-to-track')
        ->assertSee('Disclaimer')
        ->assertSee('These are AI estimates');
});

it('shows faq section', function (): void {
    Livewire::test('pages::snap-to-track')
        ->assertSee('Frequently Asked Questions')
        ->assertSee('How does the AI food photo analyzer work?')
        ->assertSee('How accurate are calorie estimates from food photos?')
        ->assertSee('Is my food photo kept private?');
});

it('renders the seo definition block', function (): void {
    Livewire::test('pages::snap-to-track')
        ->assertSee('What is Snap to Track?')
        ->assertSee('is a free AI food photo analyzer that estimates calories, protein, carbs, and fat');
});

it('renders authority and freshness signals', function (): void {
    Livewire::test('pages::snap-to-track')
        ->assertSee('AI vision analysis')
        ->assertSee('USDA-aligned nutrition references')
        ->assertSee('Last updated:');
});

it('keeps the visitor on the tool when no food is detected', function (): void {
    analyzePhotoForSnapToTrack([
        'items' => [],
        'total_calories' => 0,
        'total_protein' => 0,
        'total_carbs' => 0,
        'total_fat' => 0,
        'confidence' => 0,
    ], 'empty.jpg')
        ->assertNoRedirect()
        ->assertSet('error', 'No food items were detected. Try a clearer photo with better lighting.')
        ->assertSee('No food items were detected');

    expect(AnalysisDraft::query()->count())->toBe(0)
        ->and(session('snap_to_track.result'))->toBeNull();
});

it('validates turnstile token is required in testing environment', function (): void {
    fakeTurnstileForSnapToTrack();

    $file = UploadedFile::fake()->image('food.jpg');

    Livewire::test('pages::snap-to-track')
        ->set('photo', $file)
        ->call('analyze')
        ->assertHasErrors(['turnstileToken' => 'required']);
});

it('validates turnstile token before generating a temporary upload URL', function (): void {
    fakeTurnstileForSnapToTrack(success: false);

    Livewire::test('pages::snap-to-track')
        ->set('turnstileToken', Turnstile::dummy())
        ->call('_startUpload', 'photo', [['name' => 'food.jpg', 'size' => 1024, 'type' => 'image/jpeg']], false)
        ->assertHasErrors(['turnstileToken']);
});

it('blocks temporary upload URL generation once the upload limit is exhausted', function (): void {
    fakeTurnstileForSnapToTrack();

    RateLimiter::hit('snap-to-track-upload:127.0.0.1', 3600);
    RateLimiter::hit('snap-to-track-upload:127.0.0.1', 3600);
    RateLimiter::hit('snap-to-track-upload:127.0.0.1', 3600);
    RateLimiter::hit('snap-to-track-upload:127.0.0.1', 3600);
    RateLimiter::hit('snap-to-track-upload:127.0.0.1', 3600);

    Livewire::test('pages::snap-to-track')
        ->set('turnstileToken', Turnstile::dummy())
        ->call('_startUpload', 'photo', [['name' => 'food.jpg', 'size' => 1024, 'type' => 'image/jpeg']], false)
        ->assertHasErrors(['photo']);
});

it('configures Livewire temporary uploads for photo-only hourly throttling', function (): void {
    expect(config('livewire.temporary_file_upload.rules'))
        ->toBe(['required', 'mimes:jpg,jpeg,png,webp', 'max:10240'])
        ->and(config('livewire.temporary_file_upload.preview_mimes'))
        ->toContain('jpg', 'jpeg', 'png', 'webp')
        ->and(config('livewire.temporary_file_upload.middleware'))
        ->toBe('throttle:5,60');
});

it('blocks the analyze call once the per-IP rate limit is exhausted', function (): void {
    fakeTurnstileForSnapToTrack();

    RateLimiter::hit('snap-to-track:127.0.0.1', 3600);
    RateLimiter::hit('snap-to-track:127.0.0.1', 3600);
    RateLimiter::hit('snap-to-track:127.0.0.1', 3600);
    RateLimiter::hit('snap-to-track:127.0.0.1', 3600);
    RateLimiter::hit('snap-to-track:127.0.0.1', 3600);

    FoodPhotoAnalyzerAgent::fake(function (): void {
        throw new Exception('Agent should not have been called when rate limited');
    });

    $file = UploadedFile::fake()->image('food.jpg');

    Livewire::test('pages::snap-to-track')
        ->set('photo', $file)
        ->set('turnstileToken', Turnstile::dummy())
        ->call('analyze')
        ->assertSet('error', 'Too many attempts in a short time. Please try again later; this is separate from your scan allowance.')
        ->assertNoRedirect()
        ->assertSee('on a roll')
        ->assertDontSee('Too many requests. Please try again later.');
});

function exhaustSnapToTrackAnalysisLimit(): void
{
    foreach (range(1, 5) as $i) {
        RateLimiter::hit('snap-to-track:127.0.0.1', 3600);
    }
}

it('shows the guest limit recovery card with signup actions', function (): void {
    exhaustSnapToTrackAnalysisLimit();

    Livewire::test('pages::snap-to-track')
        ->assertSee('on a roll')
        ->assertSee('Keep analyzing — free')
        ->assertSee('Log in');
});

it('sends a limited guest into the app module through registration', function (): void {
    exhaustSnapToTrackAnalysisLimit();

    Livewire::test('pages::snap-to-track')
        ->call('continueInApp', 'register')
        ->assertRedirect(route('register'));

    expect(session('url.intended'))->toBe(route('snap-to-track.index', absolute: false))
        ->and(session('snap_to_track.auth_path'))->toBe('register');
});

it('shows the app shortcut instead of signup actions to a limited member', function (): void {
    $this->actingAs(User::factory()->create());

    exhaustSnapToTrackAnalysisLimit();

    Livewire::test('pages::snap-to-track')
        ->assertSee('Open Snap to Track')
        ->assertSee('pick up right where you left off')
        ->assertDontSee('Keep analyzing — free');
});

it('flags reference-derived items in the guest draft when the lookup is enabled', function (): void {
    config()->set('plate.food_photo_analyzer.reference_lookup.enabled', true);

    ReferenceFood::factory()->create([
        'description' => 'Hummus, commercial',
        'match_name' => ReferenceFood::normalizeName('Hummus, commercial'),
        'calories_per_100g' => 166,
        'protein_per_100g' => 7.9,
        'carbs_per_100g' => 14.3,
        'fat_per_100g' => 9.6,
    ]);

    analyzePhotoForSnapToTrack([
        'items' => [
            ['name' => 'Hummus', 'calories' => 200, 'protein' => 5, 'carbs' => 10, 'fat' => 15, 'portion' => '100g', 'grams' => 100, 'match_name' => 'hummus commercial'],
        ],
        'total_calories' => 200,
        'total_protein' => 5,
        'total_carbs' => 10,
        'total_fat' => 15,
        'confidence' => 80,
    ])->assertRedirect(route('snap-to-track.result'));

    expect(AnalysisDraft::query()->sole()->payload['items'][0]['provenance'])->toBe('reference');
});

it('shows how it works section', function (): void {
    Livewire::test('pages::snap-to-track')
        ->assertSee('How it works')
        ->assertSee('Snap a photo of your meal')
        ->assertSee('AI identifies each food item')
        ->assertSee('Get instant macro breakdown');
});

it('shows main app promo section', function (): void {
    Livewire::test('pages::snap-to-track')
        ->assertSee('Need more than just tracking?')
        ->assertSee('Get Started');
});

it('shows explore more tools section', function (): void {
    Livewire::test('pages::snap-to-track')
        ->assertSee('Explore More Tools')
        ->assertSee('View All Tools');
});

function saveMealAnalysisFixture(): array
{
    return [
        'items' => [
            ['name' => 'Grilled Chicken', 'calories' => 165, 'protein' => 31, 'carbs' => 0, 'fat' => 3.6, 'portion' => '100g'],
        ],
        'total_calories' => 165,
        'total_protein' => 31,
        'total_carbs' => 0,
        'total_fat' => 3.6,
        'confidence' => 85,
    ];
}

it('keeps retired upsells off the tool page', function (): void {
    Livewire::test('pages::snap-to-track')
        ->assertDontSee('Sign up for sharper analysis')
        ->assertDontSee('Altani');
});

function stubSnapPhotoAllowance(int $used): void
{
    app()->instance(ManagesPhotoAnalyses::class, new readonly class($used) implements ManagesPhotoAnalyses
    {
        public function __construct(private int $used) {}

        public function enabled(): bool
        {
            return true;
        }

        public function entitlement(?User $user, ?string $guestId): PhotoEntitlement
        {
            return new PhotoEntitlement(
                enabled: true, limit: 2, used: $this->used, mode: 'trial', canUpgrade: true,
                offer: new PhotoOffer(productId: 7, name: 'Starter', formattedPrice: '$2.99'),
            );
        }

        public function analyze(PhotoAnalysisContext $context, Closure $analyze): FoodAnalysisData
        {
            return $analyze(PhotoModel::standard());
        }
    });
}

it('withholds the paid offer until a scan has proven the value', function (): void {
    stubSnapPhotoAllowance(used: 0);

    Livewire::test('pages::snap-to-track')
        ->assertDontSee('Starter — $2.99/month')
        ->assertSee('2 free scans');

    analyzePhotoForSnapToTrack([
        'items' => [['name' => 'Toast', 'calories' => 200, 'protein' => 6, 'carbs' => 30, 'fat' => 5, 'portion' => '1 slice']],
        'total_calories' => 200, 'total_protein' => 6, 'total_carbs' => 30, 'total_fat' => 5, 'confidence' => 85,
    ])->assertRedirect(route('snap-to-track.result'));

    stubSnapPhotoAllowance(used: 1);

    Livewire::test('pages::snap-to-track-result')
        ->assertSee('1 free scan left')
        ->assertSee('Keep tracking with Starter — $2.99/month')
        ->assertSee('Not now — save this meal free');

    stubSnapPhotoAllowance(used: 2);

    Livewire::test('pages::snap-to-track')
        ->assertSee('Scan limit reached')
        ->assertSee('Your free scans are complete.')
        ->assertSee('Continue scanning with Starter — $2.99/month')
        ->assertDontSee('Tap to take photo or upload');
});

it('recovers an accepted scan after losing the response even when the last allowance is reserved', function (): void {
    Queue::fake([ProcessPhotoAnalysis::class]);
    FoodPhotoAnalyzerAgent::fake([saveMealAnalysisFixture()]);
    fakeTurnstileForSnapToTrack();
    stubSnapPhotoAllowance(used: 1);

    $submitted = Livewire::test('pages::snap-to-track')
        ->set('photo', UploadedFile::fake()->image('meal.jpg'))
        ->set('turnstileToken', verifiedTurnstileTokenForSnapToTrack())
        ->call('analyze')
        ->assertSet('loading', true)
        ->assertNoRedirect();
    FoodPhotoAnalyzerAgent::assertNeverPrompted();

    $requestId = $submitted->instance()->analysisRequestId;
    stubSnapPhotoAllowance(used: 2);
    session()->forget('snap_to_track.result');

    Livewire::test('pages::snap-to-track')
        ->assertSet('analysisRequestId', $requestId)
        ->assertSet('loading', true)
        ->assertSee('Your photo is being analyzed')
        ->assertDontSee('Your free scans are complete.')
        ->call('analyze');

    app()->call([new ProcessPhotoAnalysis(PhotoAnalysisRequest::query()->sole()->id), 'handle']);

    Livewire::test('pages::snap-to-track')
        ->assertSet('hasRecoverableResult', true)
        ->assertNoRedirect()
        ->call('refreshAnalysis')
        ->assertRedirect(route('snap-to-track.result'));

    Queue::assertPushed(ProcessPhotoAnalysis::class, 1);
    expect(PhotoAnalysisRequest::query()->count())->toBe(1)
        ->and(AnalysisDraft::query()->count())->toBe(1)
        ->and(session('snap_to_track.result.request_id'))->toBe($requestId);
});

it('does not expose an account scan to another account sharing its guest identity', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $guestId = (string) Str::uuid();
    session()->put('snap_to_track.analysis_guest_id', $guestId);
    PhotoAnalysisRequest::factory()->create([
        'user_id' => $owner->id,
        'guest_id' => $guestId,
        'guest_hash' => hash('sha256', $guestId),
    ]);

    $this->actingAs($other);
    Livewire::test('pages::snap-to-track')
        ->assertSet('analysisRequestId', null)
        ->assertSet('loading', false)
        ->assertSet('hasRecoverableResult', false)
        ->assertDontSee('Your photo is being analyzed');
});

it('does not offer an already saved draft as a recoverable analysis', function (): void {
    analyzePhotoForSnapToTrack(saveMealAnalysisFixture())->assertRedirect();
    AnalysisDraft::query()->sole()->update(['consumed_at' => now()]);

    Livewire::test('pages::snap-to-track')
        ->assertSet('hasRecoverableResult', false)
        ->assertDontSee('Your previous analysis is ready.')
        ->assertSee('Tap to take photo or upload');
});

it('resumes one retry after losing its response without requiring another upload', function (): void {
    Queue::fake([ProcessPhotoAnalysis::class]);
    $guestId = (string) Str::uuid();
    session()->put('snap_to_track.analysis_guest_id', $guestId);
    $failed = PhotoAnalysisRequest::factory()->create([
        'guest_id' => $guestId,
        'guest_hash' => hash('sha256', $guestId),
        'status' => PhotoAnalysisStatus::Failed,
        'failure_code' => 'provider_error',
    ]);

    $first = Livewire::test('pages::snap-to-track')->assertSet('canRetryAnalysis', true);
    $lostResponse = Livewire::test('pages::snap-to-track');
    $first->call('retryAnalysis')->assertSet('loading', true);
    exhaustSnapToTrackAnalysisLimit();

    $lostResponse->call('retryAnalysis')
        ->assertSet('loading', true)
        ->assertSet('analysisRequestId', $first->instance()->analysisRequestId)
        ->assertSet('photo', null);

    Queue::assertPushed(ProcessPhotoAnalysis::class, 1);
    expect(PhotoAnalysisRequest::query()->count())->toBe(2)
        ->and($failed->refresh()->retry_request_id)->toBe($first->instance()->analysisRequestId);
});
