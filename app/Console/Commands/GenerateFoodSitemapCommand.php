<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ContentType;
use App\Models\Content;
use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

final class GenerateFoodSitemapCommand extends Command
{
    protected $signature = 'sitemap:generate-food {--output=public/food_sitemap.xml : Output path for the sitemap}';

    protected $description = 'Generate a sitemap for all published food pages';

    public function handle(): int
    {
        $this->info('Generating food sitemap...');

        $foods = Content::query()
            ->where('type', ContentType::Food)
            ->where('is_published', true)
            ->orderBy('slug')
            ->get();

        if ($foods->isEmpty()) {
            $this->warn('No published food pages found. Sitemap not generated.');

            return self::SUCCESS;
        }

        $sitemap = Sitemap::create();

        $sitemap->add(
            Url::create(route('food.index'))
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                ->setPriority(0.8)
        );

        foreach ($foods as $food) {
            $sitemap->add(
                Url::create(route('food.show', $food->slug))
                    ->setLastModificationDate($food->updated_at)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                    ->setPriority(0.6)
            );
        }

        $outputPath = $this->option('output');
        $sitemap->writeToFile(base_path($outputPath));

        $this->info("✓ Generated sitemap with {$foods->count()} food pages at {$outputPath}");

        return self::SUCCESS;
    }
}
