<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ContentType;
use App\Models\Content;
use Illuminate\Console\Command;

/**
 * One-time command to seed SEO metadata for internal linking.
 *
 * @codeCoverageIgnoreStart
 */
final class SeedSeoMetadataCommand extends Command
{
    /**
     * SEO link mappings to seed.
     * Format: source_slug => [target_slug => anchor_text]
     *
     * @var array<string, array<string, string>>
     */
    private const array LINK_MAPPINGS = [
        // Grains → Farro
        'rice-brown-long-grain-unenriched-raw' => [
            'farro-pearled-dry-raw' => 'Looking for alternatives? Pearled Farro has a similar texture but different micronutrient profile.',
        ],
        'flour-quinoa' => [
            'farro-pearled-dry-raw' => 'Compare with Farro for a heartier grain option.',
        ],
        'rice-white-long-grain-unenriched-raw' => [
            'farro-pearled-dry-raw' => 'For a lower glycemic alternative, check out the GI profile of Pearled Farro.',
        ],
        'flour-oat-whole-grain' => [
            'farro-pearled-dry-raw' => 'Another whole grain to consider is Farro — see its glycemic index.',
        ],
        'wild-rice-dry-raw' => [
            'farro-pearled-dry-raw' => 'Prefer ancient grains? See how Pearled Farro compares.',
        ],
        'bulgur-dry-raw' => [
            'farro-pearled-dry-raw' => 'Looking for similar whole grains? Check the Farro glycemic index.',
        ],

        // Eggs → Egg Yolk
        'egg-whole-raw-frozen-pasteurized' => [
            'egg-yolk-raw-frozen-pasteurized' => 'Curious about just the yolk? See the Egg Yolk nutrition and glycemic profile.',
        ],
        'egg-white-raw-frozen-pasteurized' => [
            'egg-yolk-raw-frozen-pasteurized' => 'For a complete picture, also check the Egg Yolk glycemic index.',
        ],
        'eggs-grade-a-large-egg-white' => [
            'egg-yolk-raw-frozen-pasteurized' => 'Want to know about the other half? See the Egg Yolk nutrition facts.',
        ],

        // Dairy → American Cheese
        'cheese-cheddar' => [
            'cheese-pasteurized-process-american-vitamin-d-fortified' => 'How does American Cheese compare? See its diabetic safety profile.',
        ],
        'milk-whole-325-milkfat-with-added-vitamin-d' => [
            'cheese-pasteurized-process-american-vitamin-d-fortified' => 'For cheese lovers: check if American Cheese is safe for diabetics.',
        ],
        'yogurt-plain-nonfat' => [
            'cheese-pasteurized-process-american-vitamin-d-fortified' => 'Another dairy option to explore: American Cheese glycemic index.',
        ],
        'cheese-mozzarella-low-moisture-part-skim' => [
            'cheese-pasteurized-process-american-vitamin-d-fortified' => 'Comparing cheeses? See the American Cheese GI profile.',
        ],
        'cheese-swiss' => [
            'cheese-pasteurized-process-american-vitamin-d-fortified' => 'How does American Cheese stack up? Check its diabetic safety.',
        ],

        // Fruits → Red Delicious Apple
        'bananas-ripe-and-slightly-ripe-raw' => [
            'apples-red-delicious-with-skin-raw' => 'Prefer something with a lower GI? Check Red Delicious Apple for diabetics.',
        ],
        'grapes-red-seedless-raw' => [
            'apples-red-delicious-with-skin-raw' => 'For a crunchier option, see the glycemic index of Red Delicious Apples.',
        ],
        'grapes-green-seedless-raw' => [
            'apples-red-delicious-with-skin-raw' => 'Compare with Red Apple — a popular low-GI fruit choice.',
        ],
        'apples-fuji-with-skin-raw' => [
            'apples-red-delicious-with-skin-raw' => 'Want another apple variety? See the Red Delicious Apple GI score.',
        ],
        'apples-gala-with-skin-raw' => [
            'apples-red-delicious-with-skin-raw' => 'Comparing apple varieties? Check the Red Delicious glycemic index.',
        ],
    ];

    protected $signature = 'seo:seed-metadata {--dry-run : Show what would be updated without making changes}';

    protected $description = 'Seed SEO metadata for striking distance food pages (one-time use)';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $this->info($isDryRun ? '🔍 Dry run mode - no changes will be made' : '🚀 Seeding SEO metadata...');
        $this->newLine();

        $updated = 0;
        $notFound = 0;

        foreach (self::LINK_MAPPINGS as $sourceSlug => $targets) {
            $content = Content::query()
                ->where('type', ContentType::Food)
                ->where('slug', $sourceSlug)
                ->first();

            if (! $content) {
                $this->warn("  ⚠️  Not found: {$sourceSlug}");
                $notFound++;

                continue;
            }

            $manualLinks = [];
            foreach ($targets as $targetSlug => $anchor) {
                $manualLinks[] = [
                    'slug' => $targetSlug,
                    'anchor' => $anchor,
                ];
            }

            $seoMetadata = $content->seo_metadata ?? [];
            $seoMetadata['manual_links'] = $manualLinks;

            if (! $isDryRun) {
                $content->update(['seo_metadata' => $seoMetadata]);
            }

            $this->line("  ✅ {$sourceSlug}");
            foreach (array_keys($targets) as $targetSlug) {
                $this->line("      → {$targetSlug}");
            }

            $updated++;
        }

        $this->newLine();
        $this->info('📊 Summary:');
        $this->line("   Updated: {$updated}");
        $this->line("   Not found: {$notFound}");

        if ($isDryRun) {
            $this->newLine();
            $this->warn('💡 Run without --dry-run to apply changes');
        }

        return self::SUCCESS;
    }
}
/**
 * @codeCoverageIgnoreEnd
 */
