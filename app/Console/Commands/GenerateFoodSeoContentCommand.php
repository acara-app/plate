<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Ai\Agents\FoodImageGeneratorAgent;
use App\Ai\Agents\FoodSeoContentAgent;
use App\Enums\ContentType;
use App\Models\Content;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

use function Laravel\Prompts\progress;

final class GenerateFoodSeoContentCommand extends Command
{
    protected $signature = 'seo:generate-food
        {foods?* : Food names to generate content for}
        {--from-file= : Path to a file containing food names (one per line)}
        {--force : Regenerate content even if it already exists}
        {--skip-image : Skip image generation}
        {--force-image : Force regenerate images only}';

    protected $description = 'Generate SEO-optimized content for food pages using AI and USDA data';

    public function __construct(
        private readonly FoodSeoContentAgent $contentAgent,
        private readonly FoodImageGeneratorAgent $imageAgent,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $foods = $this->getFoodList();

        if ($foods === []) {
            $this->error('No foods specified. Provide food names as arguments or use --from-file option.');
            $this->line('');
            $this->line('Usage examples:');
            $this->line('  php artisan seo:generate-food "Banana" "Apple" "Brown Rice"');
            $this->line('  php artisan seo:generate-food --from-file=storage/app/food-list.txt');

            return self::FAILURE;
        }

        $this->info("Generating SEO content for {$this->countFoods($foods)} food(s)...");
        $this->newLine();

        $force = (bool) $this->option('force');
        $skipImage = (bool) $this->option('skip-image');
        $forceImage = (bool) $this->option('force-image');

        $successful = 0;
        $skipped = 0;
        $failed = 0;

        $progress = progress(label: 'Generating food content', steps: count($foods));
        $progress->start();

        foreach ($foods as $foodName) {
            $foodName = mb_trim($foodName);

            if ($foodName === '') {
                $progress->advance();

                continue;
            }

            $slug = Str::slug($foodName);

            $existing = Content::query()
                ->where('type', ContentType::Food)
                ->where('slug', $slug)
                ->first();

            if ($existing && ! $force && ! $forceImage) {
                $skipped++;
                $progress->advance();

                continue;
            }

            try {
                if ($forceImage && $existing) {
                    $this->regenerateImage($existing, $foodName, $slug);
                    $successful++;
                } elseif ($existing && $force) {
                    $this->updateContent($existing, $foodName, $slug, $skipImage);
                    $successful++;
                } else {
                    $this->createContent($foodName, $slug, $skipImage);
                    $successful++;
                }
            } catch (Throwable $e) {
                $failed++;
                $this->newLine();
                $this->error("Failed to generate content for '{$foodName}': {$e->getMessage()}");
            }

            $progress->advance();
        }

        $progress->finish();

        $this->newLine();
        $this->info("✓ Completed: {$successful} generated, {$skipped} skipped, {$failed} failed");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function getFoodList(): array
    {
        $foods = $this->argument('foods');

        if (is_array($foods) && $foods !== []) {
            return $foods;
        }

        $filePath = $this->option('from-file');

        if ($filePath && file_exists($filePath)) {
            $contents = file_get_contents($filePath);

            if ($contents === false) {
                return [];
            }

            return array_filter(
                array_map(trim(...), explode("\n", $contents)),
                fn (string $line): bool => $line !== '' && ! str_starts_with($line, '#')
            );
        }

        return [];
    }

    /**
     * @param  array<int, string>  $foods
     */
    private function countFoods(array $foods): int
    {
        return count(array_filter($foods, fn (string $food): bool => mb_trim($food) !== ''));
    }

    private function createContent(string $foodName, string $slug, bool $skipImage): void
    {
        $data = $this->contentAgent->generate($foodName);

        $imagePath = null;

        if (! $skipImage && isset($data['nutrition'])) {
            $imagePath = $this->imageAgent->generate(
                $data['display_name'] ?? $foodName,
                $data['nutrition'],
                $slug
            );
        }

        Content::query()->create([
            'type' => ContentType::Food,
            'slug' => $slug,
            'title' => $data['h1_title'],
            'meta_title' => $data['meta_title'],
            'meta_description' => $data['meta_description'],
            'body' => [
                'display_name' => $data['display_name'],
                'diabetic_insight' => $data['diabetic_insight'],
                'glycemic_assessment' => $data['glycemic_assessment'],
                'nutrition' => $data['nutrition'],
            ],
            'image_path' => $imagePath,
            'is_published' => true,
        ]);
    }

    private function updateContent(Content $content, string $foodName, string $slug, bool $skipImage): void
    {
        $data = $this->contentAgent->generate($foodName);

        $imagePath = $content->image_path;

        if (! $skipImage && isset($data['nutrition'])) {
            $imagePath = $this->imageAgent->generate(
                $data['display_name'] ?? $foodName,
                $data['nutrition'],
                $slug
            );
        }

        $content->update([
            'title' => $data['h1_title'],
            'meta_title' => $data['meta_title'],
            'meta_description' => $data['meta_description'],
            'body' => [
                'display_name' => $data['display_name'],
                'diabetic_insight' => $data['diabetic_insight'],
                'glycemic_assessment' => $data['glycemic_assessment'],
                'nutrition' => $data['nutrition'],
            ],
            'image_path' => $imagePath,
        ]);
    }

    private function regenerateImage(Content $content, string $foodName, string $slug): void
    {
        $nutrition = $content->body['nutrition'] ?? null;

        if (! $nutrition) {
            $this->warn("No nutrition data found for '{$foodName}', regenerating content first...");
            $this->updateContent($content, $foodName, $slug, false);

            return;
        }

        $imagePath = $this->imageAgent->generate(
            $content->body['display_name'] ?? $foodName,
            $nutrition,
            $slug
        );

        $content->update(['image_path' => $imagePath]);
    }
}
