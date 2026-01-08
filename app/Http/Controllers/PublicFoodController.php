<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ContentType;
use App\Enums\FoodCategory;
use App\Models\Content;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PublicFoodController
{
    public function show(Request $request, string $slug): View
    {
        $content = Content::query()
            ->where('type', ContentType::Food)
            ->where('slug', $slug)
            ->where('is_published', true)
            ->first();

        throw_unless($content, NotFoundHttpException::class, 'Food not found');

        return view('food.show', [
            'content' => $content,
            'nutrition' => $content->nutrition,
            'displayName' => $content->display_name,
            'diabeticInsight' => $content->diabetic_insight,
            'glycemicAssessment' => $content->glycemic_assessment,
            'glycemicLoad' => $content->glycemic_load,
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
        $itemsPerCategory = 16;
        if (! $request->hasAny(['search', 'assessment', 'category', 'page'])) {
            $allFoods = Content::food()
                ->published()
                ->orderBy('category')
                ->orderBy('title')
                ->get();

            $foodsByCategory = $allFoods
                ->groupBy(fn (Content $food): string => $food->category !== null ? $food->category->value : 'uncategorized')
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
            'categories' => $categories,
            'categoryOptions' => FoodCategory::options(),
            'currentSearch' => $search,
            'currentAssessment' => $assessment,
            'currentCategory' => $category,
            'comparisons' => $comparisons,
            'canonicalUrl' => $this->getCanonicalUrl($request),
        ]);
    }

    /**
     * Generate canonical URL - strips filter params, keeps only page if > 1.
     */
    private function getCanonicalUrl(Request $request): string
    {
        $page = $request->integer('page', 1);

        if ($page > 1) {
            return route('food.index', ['page' => $page]);
        }

        return route('food.index');
    }
}
