<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ContentType;
use App\Models\Content;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class FoodLinkingService
{
    /**
     * Cache of available food slugs for faster lookup.
     *
     * @var Collection<int, string>|null
     */
    private static ?Collection $availableSlugs = null;

    /**
     * Clear the cached slugs (useful for testing or after new content is added).
     */
    public static function clearCache(): void
    {
        self::$availableSlugs = null;
    }

    /**
     * Extract potential food names from a text string and return linked HTML.
     */
    public function linkFoodsInText(string $text): string
    {
        $availableSlugs = $this->getAvailableSlugs();

        if ($availableSlugs->isEmpty()) {
            return $text;
        }

        $words = $this->extractPotentialFoods($text);

        foreach ($words as $word) {
            $slug = Str::slug($word);

            if ($availableSlugs->contains($slug)) {
                $url = route('food.show', $slug);
                $link = sprintf(
                    '<a href="%s" class="text-primary hover:underline font-medium" wire:navigate>%s</a>',
                    $url,
                    e($word)
                );
                $text = (string) preg_replace('/\b'.preg_quote($word, '/').'\b/i', $link, (string) $text, 1);
            }
        }

        return $text;
    }

    /**
     * Get foods mentioned in text that have dedicated pages.
     *
     * @return Collection<int, array{name: string, slug: string, url: string}>
     */
    public function getFoodsWithPages(string $text): Collection
    {
        $availableSlugs = $this->getAvailableSlugs();

        if ($availableSlugs->isEmpty()) {
            /** @var Collection<int, array{name: string, slug: string, url: string}> */
            return collect();
        }

        $words = $this->extractPotentialFoods($text);
        /** @var Collection<int, array{name: string, slug: string, url: string}> $matchedFoods */
        $matchedFoods = collect();

        foreach ($words as $word) {
            $slug = Str::slug($word);

            if ($availableSlugs->contains($slug)) {
                $matchedFoods->push([
                    'name' => $word,
                    'slug' => $slug,
                    'url' => route('food.show', $slug),
                ]);
            }
        }

        return $matchedFoods->unique('slug')->values();
    }

    /**
     * Extract potential food names from text.
     *
     * @return array<int, string>
     */
    private function extractPotentialFoods(string $text): array
    {
        // Common food-related words to extract (simplified approach)
        // In production, this could use NLP or a more sophisticated extraction
        $words = [];

        // Split by common delimiters
        $parts = preg_split('/[\s,;:()]+/', $text);

        if ($parts === false) {
            return []; // @codeCoverageIgnore
        }

        foreach ($parts as $part) {
            $cleaned = mb_trim($part, '.,!?');

            if (mb_strlen($cleaned) >= 3) {
                $words[] = $cleaned;
            }
        }

        // Also try to extract compound food names (e.g., "brown rice", "chicken breast")
        $compoundPatterns = [
            '/\b(white|brown|wild)\s+(rice|bread|sugar)\b/i',
            '/\b(chicken|turkey|beef|pork)\s+(breast|thigh|leg)\b/i',
            '/\b(sweet|baked|mashed)\s+(potato|potatoes)\b/i',
            '/\b(olive|coconut|vegetable)\s+oil\b/i',
            '/\b(greek|plain)\s+yogurt\b/i',
        ];

        foreach ($compoundPatterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                foreach ($matches[0] as $match) {
                    $words[] = $match;
                }
            }
        }

        return array_unique($words);
    }

    /**
     * Get all available food slugs from the database.
     *
     * @return Collection<int, string>
     */
    private function getAvailableSlugs(): Collection
    {
        if (! self::$availableSlugs instanceof Collection) {
            /** @var Collection<int, string> $slugs */
            $slugs = Content::query()
                ->where('type', ContentType::Food)
                ->where('is_published', true)
                ->pluck('slug');
            self::$availableSlugs = $slugs;
        }

        return self::$availableSlugs;
    }
}
