<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ContentType;
use App\Models\Content;
use Illuminate\Database\Eloquent\Collection;

/**
 * @codeCoverageIgnore
 */
final class SeoLinkManager
{
    /**
     * @var array<string, array<string, string>>
     */
    private const array MANUAL_MAPPINGS = [
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

        'egg-whole-raw-frozen-pasteurized' => [
            'egg-yolk-raw-frozen-pasteurized' => 'Curious about just the yolk? See the Egg Yolk nutrition and glycemic profile.',
        ],
        'egg-white-raw-frozen-pasteurized' => [
            'egg-yolk-raw-frozen-pasteurized' => 'For a complete picture, also check the Egg Yolk glycemic index.',
        ],
        'eggs-grade-a-large-egg-white' => [
            'egg-yolk-raw-frozen-pasteurized' => 'Want to know about the other half? See the Egg Yolk nutrition facts.',
        ],

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
     * @var array<string>
     */
    private const array STATIC_AUTHORITY_FOODS = [
        'rice-brown-long-grain-unenriched-raw',
        'apples-red-delicious-with-skin-raw',
    ];

    /**
     * @var array<string>
     */
    private const array STRIKING_DISTANCE_FOODS = [
        'farro-pearled-dry-raw',
        'egg-yolk-raw-frozen-pasteurized',
        'cheese-pasteurized-process-american-vitamin-d-fortified',
    ];

    /**
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

        if (isset(self::MANUAL_MAPPINGS[$slug])) {
            $links = collect(self::MANUAL_MAPPINGS[$slug])
                ->map(fn (string $anchor, string $targetSlug): array => ['slug' => $targetSlug, 'anchor' => $anchor])
                ->values()
                ->all();

            return $this->resolveLinks($links);
        }

        return [];
    }

    /**
     * @return Collection<int, Content>
     */
    public function getFeaturedFoods(): Collection
    {
        $weekIndex = now()->weekOfYear % count(self::STRIKING_DISTANCE_FOODS);
        $rotatingSlots = [
            self::STRIKING_DISTANCE_FOODS[$weekIndex],
            self::STRIKING_DISTANCE_FOODS[($weekIndex + 1) % count(self::STRIKING_DISTANCE_FOODS)],
        ];

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
