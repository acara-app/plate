<?php

declare(strict_types=1);

use App\Models\ReferenceFood;
use App\Services\Nutrition\ReferenceFoodMatcher;
use Laravel\Ai\Embeddings;

function enableEmbeddingFallback(): void
{
    config()->set('plate.food_photo_analyzer.reference_lookup.embeddings.enabled', true);
}

function seedReferenceFood(string $description, array $attributes = []): ReferenceFood
{
    return ReferenceFood::factory()->create([
        'description' => $description,
        'match_name' => ReferenceFood::normalizeName($description),
        ...$attributes,
    ]);
}

beforeEach(function (): void {
    $this->matcher = new ReferenceFoodMatcher;
});

it('returns an exact normalized match at full score', function (): void {
    $food = seedReferenceFood('Hummus, commercial');

    $match = $this->matcher->match('Hummus, commercial');

    expect($match)->not->toBeNull()
        ->and($match->food->is($food))->toBeTrue()
        ->and($match->method)->toBe('exact')
        ->and($match->score)->toBe(1.0);
});

it('matches on token overlap above the floor', function (): void {
    $food = seedReferenceFood('Hummus, commercial');
    seedReferenceFood('Oil, olive, extra virgin');

    $match = $this->matcher->match('hummus');

    expect($match)->not->toBeNull()
        ->and($match->food->is($food))->toBeTrue()
        ->and($match->method)->toBe('tokens')
        ->and($match->score)->toBe(0.5);
});

it('prefers the higher-overlap candidate and drops those below the floor', function (): void {
    $grilled = seedReferenceFood('Chicken breast, grilled');
    seedReferenceFood('Chicken thigh, roasted');

    $match = $this->matcher->match('chicken breast');

    expect($match->food->is($grilled))->toBeTrue()
        ->and($match->method)->toBe('tokens')
        ->and(round($match->score, 3))->toBe(0.667);
});

it('returns null when the best overlap is below the floor', function (): void {
    seedReferenceFood('Chicken breast, grilled');

    expect($this->matcher->match('chicken curry'))->toBeNull();
});

it('returns null when nothing shares a token', function (): void {
    seedReferenceFood('Hummus, commercial');

    expect($this->matcher->match('banana'))->toBeNull();
});

it('never matches a nutritionally incomplete food, even on an exact name', function (): void {
    seedReferenceFood('Plain water', ['carbs_per_100g' => null]);

    expect($this->matcher->match('Plain water'))->toBeNull();
});

it('returns null for an empty query', function (): void {
    seedReferenceFood('Hummus, commercial');

    expect($this->matcher->match('   '))->toBeNull();
});

it('falls back to embedding similarity when no token matches', function (): void {
    enableEmbeddingFallback();

    $cola = seedReferenceFood('Cola, carbonated', ['embedding' => [1.0, 0.0, 0.0]]);
    seedReferenceFood('Lemonade, prepared', ['embedding' => [0.0, 1.0, 0.0]]);

    Embeddings::fake([[[0.9, 0.1, 0.0]]]);

    $match = $this->matcher->match('fizzy drink');

    expect($match)->not->toBeNull()
        ->and($match->food->is($cola))->toBeTrue()
        ->and($match->method)->toBe('embedding')
        ->and($match->score)->toBeGreaterThan(0.8);
});

it('returns null when the closest embedding is below the threshold', function (): void {
    enableEmbeddingFallback();

    seedReferenceFood('Cola, carbonated', ['embedding' => [1.0, 0.0, 0.0]]);

    Embeddings::fake([[[0.0, 0.0, 1.0]]]);

    expect($this->matcher->match('fizzy drink'))->toBeNull();
});

it('does not call the embeddings provider when the fallback is disabled', function (): void {
    Embeddings::fake()->preventStrayEmbeddings();

    seedReferenceFood('Cola, carbonated', ['embedding' => [1.0, 0.0, 0.0]]);

    expect($this->matcher->match('fizzy drink'))->toBeNull();
});

it('degrades to no match when the embeddings provider fails', function (): void {
    enableEmbeddingFallback();

    seedReferenceFood('Cola, carbonated', ['embedding' => [1.0, 0.0, 0.0]]);

    Embeddings::fake(function (): never {
        throw new RuntimeException('provider down');
    });

    expect($this->matcher->match('fizzy drink'))->toBeNull();
});

it('prefers an exact token match over the embedding fallback', function (): void {
    enableEmbeddingFallback();

    $hummus = seedReferenceFood('Hummus, commercial', ['embedding' => [0.0, 1.0, 0.0]]);

    Embeddings::fake()->preventStrayEmbeddings();

    $match = $this->matcher->match('Hummus, commercial');

    expect($match->food->is($hummus))->toBeTrue()
        ->and($match->method)->toBe('exact');
});
