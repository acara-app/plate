<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ContentType;
use App\Enums\FoodCategory;
use App\Models\Content;
use App\Services\SeoLinkManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class PublicFoodController
{
    public function __construct(private SeoLinkManager $seoLinkManager) {}

    public function show(Request $request, string $slug): View
    {
        $content = Content::query()
            ->where('type', ContentType::Food)
            ->where('slug', $slug)
            ->where('is_published', true)
            ->first();

        throw_unless($content, NotFoundHttpException::class, 'Food not found');

        $comparisonLinks = $this->seoLinkManager->getComparisonsFor($slug);

        $relatedFoods = $content->category
            ? Content::query()
                ->food()
                ->published()
                ->where('id', '!=', $content->id)
                ->inCategory($content->category)
                ->limit(3)
                ->get()
            : collect();

        return view('food.show', [
            'content' => $content,
            'nutrition' => $content->nutrition,
            'displayName' => $content->display_name,
            'diabeticInsight' => $content->diabetic_insight,
            'glycemicAssessment' => $content->glycemic_assessment,
            'glycemicLoad' => $content->glycemic_load,
            'comparisonLinks' => $comparisonLinks,
            'relatedFoods' => $relatedFoods,
        ]);
    }

    public function index(Request $request): View
    {
        $query = Content::query()
            ->food()
            ->published();

        /** @var string|null $search */
        $search = $request->input('search');
        /** @var string|null $assessment */
        $assessment = $request->input('assessment');
        /** @var string|null $category */
        $category = $request->input('category');

        if ($search !== null && $search !== '') {
            $query->where('title', 'ILIKE', "%{$search}%"); // @codeCoverageIgnore
        }

        if ($assessment && in_array($assessment, ['low', 'medium', 'high'], true)) {
            $query->whereRaw("body->>'glycemic_assessment' = ?", [$assessment]);
        }

        if ($category !== null && $category !== '') {
            $categoryEnum = FoodCategory::tryFrom($category);
            if ($categoryEnum) {
                $query->inCategory($categoryEnum);
            }
        }

        $foods = $query->orderBy('title')->paginate(12)->withQueryString();

        // Get available categories for filter dropdown
        $categories = Content::food()
            ->published()
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->map(fn (mixed $cat): ?FoodCategory => is_string($cat) ? FoodCategory::tryFrom($cat) : null)
            ->filter()
            ->sortBy(fn (FoodCategory $cat): int => $cat->order());

        // Group by category when no filters applied and on first page
        // Limit to 16 items per category for performance
        $foodsByCategory = null;
        $categoryCounts = null;
        $itemsPerCategory = 16;
        if (! $request->hasAny(['search', 'assessment', 'category', 'page'])) {
            $allFoods = Content::food()
                ->published()
                ->orderBy('category')
                ->orderBy('title')
                ->get();

            $grouped = $allFoods
                ->groupBy(fn (Content $food): string => $food->category !== null ? $food->category->value : 'uncategorized');

            // Store original counts before limiting
            $categoryCounts = $grouped->map(fn (\Illuminate\Support\Collection $foods): int => $foods->count());

            $foodsByCategory = $grouped
                ->map(fn (\Illuminate\Support\Collection $foods) => $foods->take($itemsPerCategory))
                ->sortKeys();
        }

        // Hardcoded popular comparisons for Spike Calculator
        $comparisons = [
            ['name1' => 'Brown Rice', 'name2' => 'White Rice'],
            ['name1' => 'Apple', 'name2' => 'Banana'],
            ['name1' => 'Almond Milk', 'name2' => 'Cow Milk'],
            ['name1' => 'Oatmeal', 'name2' => 'Cereal'],
            ['name1' => 'Sweet Potato', 'name2' => 'Regular Potato'],
        ];

        return view('food.index', [
            'foods' => $foods,
            'foodsByCategory' => $foodsByCategory,
            'categoryCounts' => $categoryCounts,
            'categories' => $categories,
            'categoryOptions' => FoodCategory::options(),
            'currentSearch' => $search,
            'currentAssessment' => $assessment,
            'currentCategory' => $category,
            'comparisons' => $comparisons,
            'canonicalUrl' => $this->getCanonicalUrl($request, $category),
        ]);
    }

    /**
     * Show foods filtered by category (clean URL for SEO).
     */
    public function category(Request $request, string $category): View
    {
        $categoryEnum = FoodCategory::tryFrom($category);
        throw_unless($categoryEnum, NotFoundHttpException::class, 'Category not found');

        $query = Content::query()
            ->food()
            ->published()
            ->inCategory($categoryEnum);

        /** @var string|null $assessment */
        $assessment = $request->input('assessment');

        if ($assessment && in_array($assessment, ['low', 'medium', 'high'], true)) {
            $query->whereRaw("body->>'glycemic_assessment' = ?", [$assessment]);
        }

        $foods = $query->orderBy('title')->paginate(12)->withQueryString();

        // Get available categories for filter dropdown
        $categories = Content::food()
            ->published()
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->map(fn (mixed $cat): ?FoodCategory => is_string($cat) ? FoodCategory::tryFrom($cat) : null)
            ->filter()
            ->sortBy(fn (FoodCategory $cat): int => $cat->order());

        return view('food.index', [
            'foods' => $foods,
            'foodsByCategory' => null,
            'categoryCounts' => null,
            'categories' => $categories,
            'categoryOptions' => FoodCategory::options(),
            'currentSearch' => null,
            'currentAssessment' => $assessment,
            'currentCategory' => $category,
            'comparisons' => [],
            'canonicalUrl' => $this->getCategoryCanonicalUrl($request, $category),
        ]);
    }

    /**
     * Generate canonical URL for main index - self-referencing based on filters.
     */
    private function getCanonicalUrl(Request $request, ?string $category): string
    {
        $params = [];

        // Include category in canonical if filtering by category
        if ($category !== null && $category !== '') {
            // Redirect logic: if using query param, canonical should point to clean URL
            return $this->getCategoryCanonicalUrl($request, $category);
        }

        $page = $request->integer('page', 1);
        if ($page > 1) {
            $params['page'] = $page;
        }

        return route('food.index', $params);
    }

    /**
     * Generate canonical URL for category pages (clean URL).
     */
    private function getCategoryCanonicalUrl(Request $request, string $category): string
    {
        $params = [];

        $page = $request->integer('page', 1);
        if ($page > 1) {
            $params['page'] = $page;
        }

        return route('food.category', array_merge(['category' => $category], $params));
    }
}
