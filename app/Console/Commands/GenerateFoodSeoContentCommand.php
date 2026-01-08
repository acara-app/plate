<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Ai\Agents\FoodImageGeneratorAgent;
use App\Ai\Agents\FoodSeoContentAgent;
use App\Enums\ContentType;
use App\Enums\FoodCategory;
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
        {--force-image : Force regenerate images only}
        {--batch-size=20 : Number of foods to process per batch (default: 20)}
        {--offset=0 : Start processing from this index (for resuming)}
        {--limit= : Maximum number of foods to process (for testing)}
        {--dry-run : Show what would be processed without making changes}';

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
            $this->line('');
            $this->line('Batch processing options:');
            $this->line('  --batch-size=20     Process 20 foods at a time (default)');
            $this->line('  --offset=100        Start from the 100th item (resume capability)');
            $this->line('  --limit=50          Only process 50 foods total');
            $this->line('  --dry-run           Preview what would be processed');

            return self::FAILURE;
        }

        // Apply offset and limit for strategic processing
        $offset = (int) $this->option('offset');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $batchSize = (int) $this->option('batch-size');
        $dryRun = (bool) $this->option('dry-run');

        // Slice the foods array based on offset and limit
        $totalAvailable = count($foods);
        $foods = array_slice($foods, $offset, $limit);
        $totalToProcess = count($foods);

        $this->displayHeader($totalAvailable, $totalToProcess, $offset, $batchSize, $dryRun);

        if ($dryRun) {
            return $this->performDryRun($foods, $offset);
        }

        return $this->processInBatches($foods, $batchSize, $offset);
    }

    private function displayHeader(int $totalAvailable, int $totalToProcess, int $offset, int $batchSize, bool $dryRun): void
    {
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║              Food SEO Content Generator                    ║');
        $this->info('╠════════════════════════════════════════════════════════════╣');
        $this->info(sprintf('║  Total foods available: %-33d ║', $totalAvailable));
        $this->info(sprintf('║  Foods to process:      %-33d ║', $totalToProcess));
        $this->info(sprintf('║  Starting from offset:  %-33d ║', $offset));
        $this->info(sprintf('║  Batch size:            %-33d ║', $batchSize));
        $this->info(sprintf('║  Estimated batches:     %-33d ║', (int) ceil($totalToProcess / $batchSize)));
        $this->info('╚════════════════════════════════════════════════════════════╝');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }

        $this->newLine();
    }

    /**
     * @param  array<int, string>  $foods
     */
    private function performDryRun(array $foods, int $offset): int
    {
        $this->info('Foods that would be processed:');
        $this->newLine();

        $table = [];
        foreach ($foods as $index => $foodName) {
            $foodName = mb_trim($foodName);
            if ($foodName === '') {
                continue;
            }

            $slug = Str::slug($foodName);
            $exists = Content::query()
                ->where('type', ContentType::Food)
                ->where('slug', $slug)
                ->exists();

            $table[] = [
                'Index' => $offset + $index,
                'Food Name' => Str::limit($foodName, 40),
                'Slug' => Str::limit($slug, 30),
                'Status' => $exists ? '⏭️ Exists' : '✨ New',
            ];
        }

        $this->table(['Index', 'Food Name', 'Slug', 'Status'], $table);

        $newCount = count(array_filter($table, fn ($row) => $row['Status'] === '✨ New'));
        $existingCount = count($table) - $newCount;

        $this->newLine();
        $this->info("Summary: {$newCount} new foods to create, {$existingCount} already exist");

        return self::SUCCESS;
    }

    /**
     * @param  array<int, string>  $foods
     */
    private function processInBatches(array $foods, int $batchSize, int $startOffset): int
    {
        $force = (bool) $this->option('force');
        $skipImage = (bool) $this->option('skip-image');
        $forceImage = (bool) $this->option('force-image');

        $totalSuccessful = 0;
        $totalSkipped = 0;
        $totalFailed = 0;

        $batches = array_chunk($foods, $batchSize, true);
        $batchNumber = 0;
        $totalBatches = count($batches);

        foreach ($batches as $batchIndex => $batch) {
            $batchNumber++;
            $startIndex = $startOffset + ($batchNumber - 1) * $batchSize;

            $this->info("━━━ Batch {$batchNumber}/{$totalBatches} (items {$startIndex}-" . ($startIndex + count($batch) - 1) . ') ━━━');

            $progress = progress(label: "Processing batch {$batchNumber}", steps: count($batch));
            $progress->start();

            $batchSuccessful = 0;
            $batchSkipped = 0;
            $batchFailed = 0;

            foreach ($batch as $foodName) {
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
                    $batchSkipped++;
                    $progress->advance();

                    continue;
                }

                try {
                    if ($forceImage && $existing) {
                        $this->regenerateImage($existing, $foodName, $slug);
                        $batchSuccessful++;
                    } elseif ($existing && $force) {
                        $this->updateContent($existing, $foodName, $slug, $skipImage);
                        $batchSuccessful++;
                    } else {
                        $this->createContent($foodName, $slug, $skipImage);
                        $batchSuccessful++;
                    }
                } catch (Throwable $e) {
                    $batchFailed++;
                    $this->newLine();
                    $this->error("Failed '{$foodName}': " . $e->getMessage());
                    \Illuminate\Support\Facades\Log::error("Food SEO generation failed for '{$foodName}'", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }

                $progress->advance();
            }

            $progress->finish();

            // Batch summary
            $this->line("   ✓ {$batchSuccessful} generated | ⏭️ {$batchSkipped} skipped | ✗ {$batchFailed} failed");
            $this->newLine();

            $totalSuccessful += $batchSuccessful;
            $totalSkipped += $batchSkipped;
            $totalFailed += $batchFailed;

            // Memory cleanup between batches
            gc_collect_cycles();

            // Add small delay between batches to avoid rate limiting
            if ($batchNumber < $totalBatches) {
                usleep(500000); // 0.5 second pause
            }
        }

        $this->displaySummary($totalSuccessful, $totalSkipped, $totalFailed, $startOffset, count($foods));

        return $totalFailed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function displaySummary(int $successful, int $skipped, int $failed, int $offset, int $processed): void
    {
        $this->newLine();
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║                    Processing Complete                      ║');
        $this->info('╠════════════════════════════════════════════════════════════╣');
        $this->info(sprintf('║  ✓ Generated:  %-42d ║', $successful));
        $this->info(sprintf('║  ⏭️  Skipped:   %-42d ║', $skipped));
        $this->info(sprintf('║  ✗ Failed:     %-42d ║', $failed));
        $this->info('╠════════════════════════════════════════════════════════════╣');

        $nextOffset = $offset + $processed;
        $this->info(sprintf('║  To continue: --offset=%-34d ║', $nextOffset));
        $this->info('╚════════════════════════════════════════════════════════════╝');
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
            'category' => FoodCategory::tryFrom($data['category'] ?? ''),
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
            'category' => FoodCategory::tryFrom($data['category'] ?? ''),
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
