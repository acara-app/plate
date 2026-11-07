<?php

declare(strict_types=1);

use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Ai\Tools\AnalyzePhoto;
use App\Contracts\Billing\ManagesPhotoAnalyses;
use App\Data\Billing\PhotoAnalysisContext;
use App\Data\Billing\PhotoEntitlement;
use App\Data\Billing\PhotoOffer;
use App\Data\FoodAnalysisData;
use App\Enums\SubscriptionTier;
use App\Exceptions\Billing\PhotoLimitExceeded;
use App\Exceptions\Billing\UsageLimitExceededException;
use App\Models\User;
use Carbon\CarbonImmutable;
use Laravel\Ai\Files\Base64Image;
use Laravel\Ai\Tools\Request;
use Tests\Helpers\TestJsonSchema;

use function Pest\Laravel\actingAs;

covers(AnalyzePhoto::class);

it('has the correct name', function (): void {
    $tool = new AnalyzePhoto([]);

    expect($tool->name())->toBe('analyze_photo');
});

it('returns error when no images are provided', function (): void {
    $tool = new AnalyzePhoto([]);
    $request = new Request(['query' => 'Analyze this food']);

    $result = json_decode($tool->handle($request), true);

    expect($result)->toHaveKey('error')
        ->and($result['error'])->toContain('No image');
});

it('analyzes food photo and returns structured data', function (): void {
    FoodPhotoAnalyzerAgent::fake([
        [
            'items' => [
                ['name' => 'Grilled Chicken', 'calories' => 165.0, 'protein' => 31.0, 'carbs' => 0.0, 'fat' => 3.6, 'portion' => '100g'],
            ],
            'total_calories' => 165.0,
            'total_protein' => 31.0,
            'total_carbs' => 0.0,
            'total_fat' => 3.6,
            'confidence' => 85,
        ],
    ]);

    $image = new Base64Image(base64_encode('fake-image-data'), 'image/jpeg');
    $tool = new AnalyzePhoto([$image]);
    $request = new Request(['query' => 'What is this food?']);

    $result = json_decode($tool->handle($request), true);

    expect($result)->toHaveKey('total_calories')
        ->and($result['total_calories'])->toBe(165)
        ->and($result)->toHaveKey('items')
        ->and($result['items'])->toHaveCount(1);
});

it('tells the model why a photo was not analyzed instead of failing the chat turn', function (Throwable $limit, array $reply): void {
    app()->instance(ManagesPhotoAnalyses::class, new readonly class($limit) implements ManagesPhotoAnalyses
    {
        public function __construct(private Throwable $limit) {}

        public function enabled(): bool
        {
            return true;
        }

        public function entitlement(?User $user, ?string $guestId): PhotoEntitlement
        {
            return new PhotoEntitlement;
        }

        public function analyze(PhotoAnalysisContext $context, Closure $analyze): FoodAnalysisData
        {
            throw $this->limit;
        }
    });

    $tool = new AnalyzePhoto([new Base64Image(base64_encode('fake-image-data'), 'image/jpeg')]);

    expect(json_decode($tool->handle(new Request(['query' => 'What is this?'])), true))->toBe($reply);
})->with([
    'free scans used up' => [
        fn (): PhotoLimitExceeded => new PhotoLimitExceeded(new PhotoEntitlement(enabled: true, limit: 2, used: 2, mode: 'trial', canUpgrade: true, offer: new PhotoOffer(productId: 7, name: 'Starter', formattedPrice: '$2.99'))),
        fn (): array => [
            'error' => 'Your free photo scans have been used. Choose a plan to analyze another photo.',
            'plan' => ['name' => 'Starter', 'price' => '$2.99 per month', 'url' => route('checkout.start', ['product' => 7, 'returning' => 1])],
        ],
    ],
    'no plan on offer' => [
        fn (): PhotoLimitExceeded => new PhotoLimitExceeded(new PhotoEntitlement(enabled: true, limit: 2, used: 2, mode: 'trial')),
        ['error' => 'Your free photo scans have been used. Choose a plan to analyze another photo.'],
    ],
    'AI usage limit reached' => [
        fn (): UsageLimitExceededException => new UsageLimitExceededException('rolling', SubscriptionTier::Starter, 500, 500, CarbonImmutable::parse('2026-11-08 09:00:00', 'UTC')),
        ['error' => 'Your AI usage limit is used up for now, so this photo was not analyzed. It resets at 2026-11-08T09:00:00+00:00.'],
    ],
]);

it('has a schema with query parameter', function (): void {
    $tool = new AnalyzePhoto([]);
    $schema = new TestJsonSchema();

    $result = $tool->schema($schema);

    expect($result)->toHaveKey('query');
});

it('has a non-empty description', function (): void {
    $tool = new AnalyzePhoto([]);

    expect($tool->description())
        ->toBeString()
        ->not->toBeEmpty()
        ->toContain('food photo')
        ->toContain('log_health_entry');
});

it('passes user preferred language to the agent when authenticated', function (): void {
    FoodPhotoAnalyzerAgent::fake([[
        'items' => [],
        'total_calories' => 0,
        'total_protein' => 0,
        'total_carbs' => 0,
        'total_fat' => 0,
        'confidence' => 0,
    ]]);

    $agent = new FoodPhotoAnalyzerAgent;
    app()->instance(FoodPhotoAnalyzerAgent::class, $agent);

    $user = User::factory()->create(['locale' => 'en']);
    actingAs($user);

    $image = new Base64Image(base64_encode('fake-image-data'), 'image/jpeg');
    $tool = new AnalyzePhoto([$image]);
    $tool->handle(new Request(['query' => 'analyze']));

    FoodPhotoAnalyzerAgent::assertPrompted(fn ($prompt): bool => str_contains($prompt->agent->instructions(), 'language code: `en`')
        && str_contains($prompt->agent->instructions(), 'Return all `name` and `portion` values in English'));
});

it('does not pass language when no user is authenticated', function (): void {
    FoodPhotoAnalyzerAgent::fake([[
        'items' => [],
        'total_calories' => 0,
        'total_protein' => 0,
        'total_carbs' => 0,
        'total_fat' => 0,
        'confidence' => 0,
    ]]);

    $agent = new FoodPhotoAnalyzerAgent;
    app()->instance(FoodPhotoAnalyzerAgent::class, $agent);

    $image = new Base64Image(base64_encode('fake-image-data'), 'image/jpeg');
    $tool = new AnalyzePhoto([$image]);
    $tool->handle(new Request(['query' => 'analyze']));

    expect($agent->instructions())
        ->not->toContain('Return all `name` and `portion` values in');
});
