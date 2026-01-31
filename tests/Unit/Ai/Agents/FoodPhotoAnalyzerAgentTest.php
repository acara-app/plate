<?php

declare(strict_types=1);

use App\Ai\Agents\FoodPhotoAnalyzerAgent;
use App\Enums\ModelName;
use Illuminate\Support\Facades\Config;
use Prism\Prism\Enums\FinishReason;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\StructuredResponseFake;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\ValueObjects\Usage;

beforeEach(function (): void {
    $this->agent = new FoodPhotoAnalyzerAgent;
    Config::set('prism.providers.gemini.api_key', 'test-key');
});

it('returns gemini as provider', function (): void {
    expect($this->agent->provider())->toBe(Provider::Gemini);
});

it('returns correct model', function (): void {
    expect($this->agent->modelName())->toBe(ModelName::GEMINI_3_FLASH);
});

it('returns system prompt with food analysis instructions', function (): void {
    $systemPrompt = $this->agent->systemPrompt();

    expect($systemPrompt)
        ->toContain('nutritionist')
        ->toContain('food recognition')
        ->toContain('calories')
        ->toContain('protein')
        ->toContain('carbs')
        ->toContain('fat')
        ->toContain('portion');
});

it('returns correct max tokens', function (): void {
    expect($this->agent->maxTokens())->toBe(2000);
});

it('returns client options with timeout', function (): void {
    $options = $this->agent->clientOptions();

    expect($options)->toHaveKey('timeout')
        ->and($options['timeout'])->toBe(60);
});

it('analyzes food photo and returns analysis data', function (): void {
    $fakeResponse = StructuredResponseFake::make()
        ->withStructured([
            'items' => [
                ['name' => 'Grilled Chicken', 'calories' => 165.0, 'protein' => 31.0, 'carbs' => 0.0, 'fat' => 3.6, 'portion' => '100g'],
            ],
            'total_calories' => 165.0,
            'total_protein' => 31.0,
            'total_carbs' => 0.0,
            'total_fat' => 3.6,
            'confidence' => 85,
        ])
        ->withFinishReason(FinishReason::Stop)
        ->withUsage(new Usage(100, 200))
        ->withMeta(new Meta('test-id', 'gemini-3-flash-preview'));

    Prism::fake([$fakeResponse]);

    $imageBase64 = base64_encode('fake-image-data');
    $result = $this->agent->analyze($imageBase64, 'image/jpeg');

    expect($result->totalCalories)->toBe(165.0);
    expect($result->totalProtein)->toBe(31.0);
    expect($result->totalCarbs)->toBe(0.0);
    expect($result->totalFat)->toBe(3.6);
    expect($result->confidence)->toBe(85);
    expect($result->items)->toHaveCount(1);
    expect($result->items->first()->name)->toBe('Grilled Chicken');
});

it('analyzes food photo with multiple items', function (): void {
    $fakeResponse = StructuredResponseFake::make()
        ->withStructured([
            'items' => [
                ['name' => 'Rice', 'calories' => 130.0, 'protein' => 2.7, 'carbs' => 28.0, 'fat' => 0.3, 'portion' => '100g'],
                ['name' => 'Chicken', 'calories' => 165.0, 'protein' => 31.0, 'carbs' => 0.0, 'fat' => 3.6, 'portion' => '100g'],
            ],
            'total_calories' => 295.0,
            'total_protein' => 33.7,
            'total_carbs' => 28.0,
            'total_fat' => 3.9,
            'confidence' => 90,
        ])
        ->withFinishReason(FinishReason::Stop)
        ->withUsage(new Usage(100, 200))
        ->withMeta(new Meta('test-id', 'gemini-3-flash-preview'));

    Prism::fake([$fakeResponse]);

    $imageBase64 = base64_encode('fake-image-data');
    $result = $this->agent->analyze($imageBase64, 'image/png');

    expect($result->totalCalories)->toBe(295.0);
    expect($result->items)->toHaveCount(2);
    expect($result->items->first()->name)->toBe('Rice');
    expect($result->items->last()->name)->toBe('Chicken');
});

it('handles empty food detection', function (): void {
    $fakeResponse = StructuredResponseFake::make()
        ->withStructured([
            'items' => [],
            'total_calories' => 0,
            'total_protein' => 0,
            'total_carbs' => 0,
            'total_fat' => 0,
            'confidence' => 0,
        ])
        ->withFinishReason(FinishReason::Stop)
        ->withUsage(new Usage(100, 200))
        ->withMeta(new Meta('test-id', 'gemini-3-flash-preview'));

    Prism::fake([$fakeResponse]);

    $imageBase64 = base64_encode('fake-image-data');
    $result = $this->agent->analyze($imageBase64, 'image/jpeg');

    expect($result->totalCalories)->toBe(0.0);
    expect($result->confidence)->toBe(0);
    expect($result->items)->toHaveCount(0);
});

it('throws exception when structured data is empty', function (): void {
    $fakeResponse = StructuredResponseFake::make()
        ->withStructured([])
        ->withFinishReason(FinishReason::Stop)
        ->withUsage(new Usage(100, 200))
        ->withMeta(new Meta('test-id', 'gemini-3-flash-preview'));

    Prism::fake([$fakeResponse]);

    $imageBase64 = base64_encode('fake-image-data');

    $this->agent->analyze($imageBase64, 'image/jpeg');
})->throws(InvalidArgumentException::class, 'AI returned invalid analysis structure');
