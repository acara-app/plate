<?php

declare(strict_types=1);

use App\Models\ReferenceFood;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Prompts\EmbeddingsPrompt;

function fakeDimensionedEmbeddings(): void
{
    Embeddings::fake(fn (EmbeddingsPrompt $prompt) => array_map(
        fn () => Embeddings::fakeEmbedding($prompt->dimensions),
        $prompt->inputs,
    ));
}

it('embeds reference foods that lack an embedding', function (): void {
    fakeDimensionedEmbeddings();

    ReferenceFood::factory()->count(3)->create(['embedding' => null]);

    $this->artisan('nutrition:embed-references')->assertSuccessful();

    expect(ReferenceFood::query()->whereNotNull('embedding')->count())->toBe(3)
        ->and(ReferenceFood::query()->first()->embedding)->toBeArray()->toHaveCount(1536);
});

it('skips foods that already have an embedding unless forced', function (): void {
    fakeDimensionedEmbeddings();

    ReferenceFood::factory()->create(['embedding' => [0.1, 0.2]]);
    ReferenceFood::factory()->create(['embedding' => null]);

    $this->artisan('nutrition:embed-references')->assertSuccessful();

    Embeddings::assertGenerated(fn (EmbeddingsPrompt $prompt) => count($prompt->inputs) === 1);
});

it('re-embeds everything when forced', function (): void {
    fakeDimensionedEmbeddings();

    ReferenceFood::factory()->count(2)->create(['embedding' => [0.1, 0.2]]);

    $this->artisan('nutrition:embed-references', ['--force' => true])->assertSuccessful();

    Embeddings::assertGenerated(fn (EmbeddingsPrompt $prompt) => count($prompt->inputs) === 2);
});

it('reports when nothing needs embedding', function (): void {
    Embeddings::fake();

    ReferenceFood::factory()->create(['embedding' => [0.1, 0.2]]);

    $this->artisan('nutrition:embed-references')
        ->expectsOutputToContain('No reference foods need embedding')
        ->assertSuccessful();

    Embeddings::assertNothingGenerated();
});
