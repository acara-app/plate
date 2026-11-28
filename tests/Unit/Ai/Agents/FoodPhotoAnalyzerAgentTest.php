<?php

declare(strict_types=1);

use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Data\Billing\PhotoModel;
use App\Listeners\LogAiProviderFailover;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Exceptions\RateLimitedException;
use Laravel\Ai\Providers\Provider;
use Spatie\LaravelData\Exceptions\CannotCreateData;

covers(FoodPhotoAnalyzerAgent::class, PhotoModel::class, LogAiProviderFailover::class);

beforeEach(function (): void {
    $this->agent = new FoodPhotoAnalyzerAgent;
});

it('keeps the prompt accuracy framing non-clinical', function (): void {
    expect($this->agent->instructions())
        ->toContain('users track per-item carbohydrate values closely')
        ->not->toContain('diabetes management')
        ->not->toContain('depend on per-item carb counts');
});

it('returns instructions with food analysis guidance', function (): void {
    $instructions = $this->agent->instructions();

    expect($instructions)
        ->toContain('nutritionist')
        ->toContain('food recognition')
        ->toContain('calories')
        ->toContain('protein')
        ->toContain('carbs')
        ->toContain('fat')
        ->toContain('portion')
        ->toContain('not a standard serving size')
        ->toContain('include the cooking oil, butter, sauce, or dressing');
});

it('omits language directive when language is not set', function (): void {
    $instructions = $this->agent->instructions();

    expect($instructions)
        ->not->toContain('Return all `name` and `portion` values in')
        ->not->toContain('language code:');
});

it('appends language directive to instructions when language is set', function (): void {
    $this->agent->withLanguage('English', 'en');

    $instructions = $this->agent->instructions();

    expect($instructions)
        ->toContain('Return all `name` and `portion` values in English')
        ->toContain('language code: `en`')
        ->toContain('Numeric fields and structured field names stay as-is')
        ->toContain('do not transliterate from English');
});

it('analyzes food photo and returns analysis data', function (): void {
    $mockResponse = [
        'items' => [
            ['name' => 'Grilled Chicken', 'calories' => 165.0, 'protein' => 31.0, 'carbs' => 0.0, 'fat' => 3.6, 'portion' => '100g'],
        ],
        'total_calories' => 165.0,
        'total_protein' => 31.0,
        'total_carbs' => 0.0,
        'total_fat' => 3.6,
        'confidence' => 85.0,
    ];

    FoodPhotoAnalyzerAgent::fake([$mockResponse]);

    $imageBase64 = base64_encode('fake-image-data');
    $result = $this->agent->analyze($imageBase64, 'image/jpeg');

    expect($result->totalCalories)->toBe(165.0)
        ->and($result->totalProtein)->toBe(31.0)
        ->and($result->totalCarbs)->toBe(0.0)
        ->and($result->totalFat)->toBe(3.6)
        ->and($result->confidence)->toBe(85)
        ->and($result->items)->toHaveCount(1)
        ->and($result->items->first()->name)->toBe('Grilled Chicken');
});

it('analyzes food photo with multiple items', function (): void {
    $mockResponse = [
        'items' => [
            ['name' => 'Rice', 'calories' => 130.0, 'protein' => 2.7, 'carbs' => 28.0, 'fat' => 0.3, 'portion' => '100g'],
            ['name' => 'Chicken', 'calories' => 165.0, 'protein' => 31.0, 'carbs' => 0.0, 'fat' => 3.6, 'portion' => '100g'],
        ],
        'total_calories' => 295.0,
        'total_protein' => 33.7,
        'total_carbs' => 28.0,
        'total_fat' => 3.9,
        'confidence' => 90.0,
    ];

    FoodPhotoAnalyzerAgent::fake([$mockResponse]);

    $imageBase64 = base64_encode('fake-image-data');
    $result = $this->agent->analyze($imageBase64, 'image/png');

    expect($result->totalCalories)->toBe(295.0)
        ->and($result->items)->toHaveCount(2)
        ->and($result->items->first()->name)->toBe('Rice')
        ->and($result->items->last()->name)->toBe('Chicken');
});

it('handles empty food detection', function (): void {
    $mockResponse = [
        'items' => [],
        'total_calories' => 0,
        'total_protein' => 0,
        'total_carbs' => 0,
        'total_fat' => 0,
        'confidence' => 0.0,
    ];

    FoodPhotoAnalyzerAgent::fake([$mockResponse]);

    $imageBase64 = base64_encode('fake-image-data');
    $result = $this->agent->analyze($imageBase64, 'image/jpeg');

    expect($result->totalCalories)->toBe(0.0)
        ->and($result->confidence)->toBe(0)
        ->and($result->items)->toHaveCount(0);
});

it('retries once when the structured output is malformed', function (): void {
    FoodPhotoAnalyzerAgent::fake([[], ['items' => [], 'total_calories' => 0, 'total_protein' => 0, 'total_carbs' => 0, 'total_fat' => 0, 'confidence' => 80]]);

    expect($this->agent->analyze('aW1hZ2U=', 'image/jpeg')->confidence)->toBe(80);
});

it('retries a malformed answer on the provider that gave it, not the whole failover chain', function (): void {
    FoodPhotoAnalyzerAgent::fake(fn (string $prompt, $attachments, Provider $provider): array => $provider->name() === 'gemini'
        ? throw RateLimitedException::forProvider('gemini', 429)
        : []);

    expect(function (): void {
        (new FoodPhotoAnalyzerAgent)->analyze('aW1hZ2U=', 'image/jpeg');
    })->toThrow(CannotCreateData::class);

    FoodPhotoAnalyzerAgent::assertPromptedTimes(3);
});

it('stamps the analyzer version on analysis results', function (): void {
    FoodPhotoAnalyzerAgent::fake([
        [
            'items' => [],
            'total_calories' => 0,
            'total_protein' => 0,
            'total_carbs' => 0,
            'total_fat' => 0,
            'confidence' => 0.0,
        ],
    ]);

    $result = $this->agent->analyze(base64_encode('fake-image-data'), 'image/jpeg');

    expect($result->analyzerVersion)->toBe(FoodPhotoAnalyzerAgent::version());
});

it('uses the selected photo model without changing the shared default', function (): void {
    FoodPhotoAnalyzerAgent::fake([[
        'items' => [], 'total_calories' => 0, 'total_protein' => 0,
        'total_carbs' => 0, 'total_fat' => 0, 'confidence' => 0,
    ]]);
    $agent = new FoodPhotoAnalyzerAgent;
    $profile = new PhotoModel('openai', 'test-premium-model', 2048);
    $result = $agent->usingModel($profile)->analyze('aW1hZ2U=', 'image/jpeg');
    expect($result->analyzerVersion)->toContain('test-premium-model')
        ->and($agent->maxTokens())->toBe(35000);
    FoodPhotoAnalyzerAgent::assertPrompted(fn ($prompt): bool => $prompt->model === 'test-premium-model');
});

it('fails over to the next model when the primary provider is unavailable', function (): void {
    Log::spy();
    $spendCap = new RequestException(new Response(new Psr7Response(429, [], json_encode([
        'error' => ['message' => 'Your billing account has exceeded its monthly spending cap.'],
    ]))));
    FoodPhotoAnalyzerAgent::fake(fn (string $prompt, $attachments, Provider $provider): array => $provider->name() === 'gemini'
        ? throw RateLimitedException::forProvider('gemini', 429, $spendCap)
        : ['items' => [], 'total_calories' => 0, 'total_protein' => 0, 'total_carbs' => 0, 'total_fat' => 0, 'confidence' => 0]);

    $result = (new FoodPhotoAnalyzerAgent)->analyze('aW1hZ2U=', 'image/jpeg');

    expect($result->analyzerVersion)->toBe(FoodPhotoAnalyzerAgent::version('gpt-6.1-terra'));
    Log::shouldHaveReceived('error')->once()->with('ai_provider_failed_over', Mockery::on(fn (array $context): bool => $context['provider'] === 'gemini'
        && $context['reason'] === 'Your billing account has exceeded its monthly spending cap.'));
});
