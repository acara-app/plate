<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ContentType;
use App\Models\Content;

/**
 * @codeCoverageIgnore
 */
final class SeoLinkManager
{
    /**
     * Manual link mappings for SEO cross-linking.
     * These are fallbacks when database seo_metadata is not populated.
     * Format: source_slug => [target_slug => anchor_text]
     *
     * Strategy: 70% natural, 20% exact-match, 10% navigational
     *
     * @var array<string, array<string, string>>
     */
    private const array MANUAL_MAPPINGS = [
        // Grains linking to Farro (striking distance target)
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

        // Eggs linking to Egg Yolk (striking distance target)
        'egg-whole-raw-frozen-pasteurized' => [
            'egg-yolk-raw-frozen-pasteurized' => 'Curious about just the yolk? See the Egg Yolk nutrition and glycemic profile.',
        ],
        'egg-white-raw-frozen-pasteurized' => [
            'egg-yolk-raw-frozen-pasteurized' => 'For a complete picture, also check the Egg Yolk glycemic index.',
        ],
        'eggs-grade-a-large-egg-white' => [
            'egg-yolk-raw-frozen-pasteurized' => 'Want to know about the other half? See the Egg Yolk nutrition facts.',
        ],

        // Dairy linking to American Cheese (striking distance target)
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

        // Fruits linking to Red Delicious Apple (striking distance target)
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

    /**
     * Static "all-star" foods that always appear in slots 1-2.
     * These are high-traffic anchor foods that establish authority.
     *
     * @var array<string>
     */
    private const array STATIC_AUTHORITY_FOODS = [
        'rice-brown-long-grain-unenriched-raw',  // Brown Rice - anchor food
        'apples-red-delicious-with-skin-raw',     // Apple - everyone searches this
    ];

    /**
     * "Striking Distance" foods that rotate through slots 3-4.
     * These are Page 2 keywords we're pushing to Page 1.
     *
     * @var array<string>
     */
    private const array STRIKING_DISTANCE_FOODS = [
        'farro-pearled-dry-raw',                                    // Rank 6 → targeting top 3
        'egg-yolk-raw-frozen-pasteurized',                          // Rank 11 → targeting top 5
        'cheese-pasteurized-process-american-vitamin-d-fortified',  // Rank 7.5 → targeting top 3
    ];

    /**
     * Get comparison links for a given food slug.
     * First checks database seo_metadata, then falls back to manual mappings.
     *
     * @return array<int, array{slug: string, anchor: string, content: Content|null}>
     */
    public function getComparisonsFor(string $slug): array
    {
        $content = Content::query()
            ->where('type', ContentType::Food)
            ->where('slug', $slug)
            ->where('is_published', true)
            ->first();

        if ($content && ! empty($content->manual_links)) {
            return $this->resolveLinks($content->manual_links);
        }

        // Fall back to manual mappings
        if (isset(self::MANUAL_MAPPINGS[$slug])) {
            $links = [];
            foreach (self::MANUAL_MAPPINGS[$slug] as $targetSlug => $anchor) {
                $links[] = ['slug' => $targetSlug, 'anchor' => $anchor];
            }

            return $this->resolveLinks($links);
        }

        return [];
    }

    /**
     * Get featured foods for homepage with 2+2 rotation strategy.
     * Slots 1-2: Static authority foods (always shown).
     * Slots 3-4: Rotating striking distance foods (weekly rotation).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Content>
     */
    public function getFeaturedFoods(): \Illuminate\Database\Eloquent\Collection
    {
        // Slots 3-4: Rotate through striking distance foods weekly
        $weekIndex = now()->weekOfYear % count(self::STRIKING_DISTANCE_FOODS);
        $rotatingSlots = [
            self::STRIKING_DISTANCE_FOODS[$weekIndex],
            self::STRIKING_DISTANCE_FOODS[($weekIndex + 1) % count(self::STRIKING_DISTANCE_FOODS)],
        ];

        // Combine: 2 static + 2 rotating
        $allSlugs = array_merge(self::STATIC_AUTHORITY_FOODS, $rotatingSlots);

        return Content::query()
            ->food()
            ->published()
            ->whereIn('slug', $allSlugs)
            ->get()
            ->sortBy(fn (Content $food): int => array_search($food->slug, $allSlugs, true) ?: 0)
            ->values();
    }

    /**
     * Get the striking distance food slugs for footer links.
     *
     * @return array<string, array{slug: string, anchor: string}>
     */
    public function getPopularSearchLinks(): array
    {
        return [
            'cheese' => [
                'slug' => 'cheese-pasteurized-process-american-vitamin-d-fortified',
                'anchor' => 'American Cheese Glycemic Index',
            ],
            'farro' => [
                'slug' => 'farro-pearled-dry-raw',
                'anchor' => 'Pearled Farro GI Score',
            ],
            'egg' => [
                'slug' => 'egg-yolk-raw-frozen-pasteurized',
                'anchor' => 'Egg Yolk Nutrition Facts',
            ],
            'apple' => [
                'slug' => 'apples-red-delicious-with-skin-raw',
                'anchor' => 'Red Apple Diabetes Safety',
            ],
        ];
    }

    /**
     * Resolve link slugs to Content models.
     *
     * @param  array<int, array{slug: string, anchor: string}>  $links
     * @return array<int, array{slug: string, anchor: string, content: Content|null}>
     */
    private function resolveLinks(array $links): array
    {
        $slugs = array_column($links, 'slug');

        $contents = Content::query()
            ->food()
            ->published()
            ->whereIn('slug', $slugs)
            ->get()
            ->keyBy('slug');

        return array_map(function (array $link) use ($contents): array {
            $link['content'] = $contents->get($link['slug']);

            return $link;
        }, $links);
    }
}
