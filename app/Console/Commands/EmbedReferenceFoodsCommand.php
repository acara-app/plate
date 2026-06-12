<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ReferenceFood;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Ai\Embeddings;

#[Description('Generate and store vector embeddings for reference foods, enabling the matcher\'s semantic fallback.')]
#[Signature('nutrition:embed-references {--force : Re-embed foods that already have an embedding} {--chunk=100 : Number of foods to embed per provider call}')]
final class EmbedReferenceFoodsCommand extends Command
{
    public function handle(): int
    {
        $dimensions = (int) config('plate.food_photo_analyzer.reference_lookup.embeddings.dimensions', 1536);
        $chunk = max(1, (int) $this->option('chunk'));

        $query = ReferenceFood::query()->orderBy('id');

        if (! $this->option('force')) {
            $query->whereNull('embedding');
        }

        $total = $query->clone()->count();

        if ($total === 0) {
            $this->info('No reference foods need embedding.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($total);
        $embedded = 0;

        $query->chunkById($chunk, function (Collection $foods) use ($dimensions, $bar, &$embedded): void {
            /** @var list<string> $texts */
            $texts = $foods->pluck('description')->all();

            $vectors = Embeddings::for($texts)->dimensions($dimensions)->generate()->embeddings;

            foreach ($foods->values() as $index => $food) {
                $vector = $vectors[$index] ?? null;

                if (is_array($vector) && $vector !== []) {
                    $food->update(['embedding' => $vector]);
                    $embedded++;
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info(sprintf('Embedded %d of %d reference foods (%d dimensions).', $embedded, $total, $dimensions));

        return self::SUCCESS;
    }
}
