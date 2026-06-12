<?php

declare(strict_types=1);

namespace App\Services;

final class AiTransparency
{
    public const string LAST_REVIEWED = '2026-06-12';

    /**
     * @return list<array{q: string, a: string}>
     */
    public static function snapToTrackFaqs(): array
    {
        return [
            [
                'q' => 'How does the AI food photo analyzer work?',
                'a' => self::photoAnalyzerFaqAnswer(),
            ],
            [
                'q' => 'How accurate are calorie estimates from food photos?',
                'a' => 'Treat every number as an estimate, not a measurement. Accuracy depends on photo clarity, lighting, and how visible each ingredient is — and portion size is the hardest part: published evaluations of AI photo analysis find errors are driven mostly by portion estimation, with underestimation that grows as portions get larger. Mixed dishes, sauces, and cooking oils are harder than visible whole foods. The confidence score is the model\'s own judgment of how clearly it could see the meal, not a measured error range. We publish a full breakdown on our AI accuracy page.',
            ],
            [
                'q' => 'What types of food can the AI recognize?',
                'a' => 'The analyzer recognizes most common foods: fruits, vegetables, grains, meats, fish, dairy, packaged snacks, drinks, and prepared dishes from many cuisines. It works best when each item is clearly visible from above with good lighting. Hidden ingredients (oils, sauces, dressings, broths) are harder to detect, so single-ingredient and well-lit plate shots produce the most reliable results.',
            ],
            [
                'q' => 'Is my food photo kept private?',
                'a' => 'Yes. Your photo is used only to generate the nutrition analysis. Livewire stores it as a temporary upload while the scan runs, then we delete that temporary file as soon as the result or error is returned. We do not retain images, share them with third parties, or use them to train AI models. Authenticated users can opt to log meals with photos to their personal history; on this public tool, no image is saved.',
            ],
            [
                'q' => 'How do I use Snap to Track?',
                'a' => 'Open this page on your phone or laptop, tap the upload area to take a new photo or pick one from your gallery, then tap Analyze Food. In about 5–15 seconds you get a per-item breakdown of calories, protein, carbs, and fat plus meal totals. No signup is required to try it; create a free account to save and track meals over time.',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function pipeline(): array
    {
        if (self::usesReferenceLookup()) {
            return [
                'Your photo goes through Acara\'s multimodal AI pipeline — a single pass that combines image understanding with nutrition reasoning to read your meal.',
                'In that one pass, the model identifies each distinct food item it can see, estimates its portion size and weight in grams, and proposes calories, protein, carbohydrate, and fat for every item.',
                'Where the model confidently recognizes a food, we then replace its estimated nutrients with values computed from a reference database — USDA FoodData Central — scaled to the estimated weight. Those items are flagged as reference-derived; items we cannot confidently match keep the model\'s own estimate and are flagged as estimates, so a single meal can mix both.',
                "Every analysis also returns a confidence score from 0 to 100, which is the model's own assessment of how clearly it could see and recognize the meal.",
                'The same engine powers both the free Snap to Track tool and the full app. The difference: in the full app, you review and can edit every item and portion before anything is saved to your history.',
            ];
        }

        return [
            'Your photo goes through Acara\'s multimodal AI pipeline — a single pass that combines image understanding with nutrition reasoning to read your meal.',
            'In that one pass, the model identifies each distinct food item it can see, estimates the portion size visually, and generates calories, protein, carbohydrate, and fat for every item plus meal totals — values drawn from its training knowledge of standard nutrition references such as USDA FoodData Central, not retrieved from a database.',
            "Every analysis also returns a confidence score from 0 to 100, which is the model's own assessment of how clearly it could see and recognize the meal.",
            'The same engine powers both the free Snap to Track tool and the full app. The difference: in the full app, you review and can edit every item and portion before anything is saved to your history.',
        ];
    }

    public static function usesReferenceLookup(): bool
    {
        return (bool) config('plate.food_photo_analyzer.reference_lookup.enabled', false);
    }

    /**
     * @return list<string>
     */
    public static function provenance(): array
    {
        return [
            'When the database lookup is active, every item is tagged with where its numbers came from. "Reference-derived" means we matched the food to a USDA FoodData Central entry and computed its nutrients from that entry, scaled to the estimated weight — repeatable and citable. "Estimate" means the model\'s own value, used when no confident database match exists, such as composite dishes or regional foods.',
            'The split is deliberate: it concentrates the remaining error where only the photo can answer — identifying the food and judging its weight — rather than in recalling nutrient tables. It does not make portion size any easier to judge, which stays the dominant source of error.',
        ];
    }

    /**
     * @return list<string>
     */
    public static function accuracy(): array
    {
        return [
            'We have not yet completed our own validation study against weighed meals, so we will not quote you a precise error figure — any number we gave you today would be invented. What we can do is point at the peer-reviewed research.',
            'Published evaluations of general-purpose multimodal AI models on meal photos report energy estimation errors of roughly 30–40% for the best-performing models, with systematic underestimation that grows as portions get larger; several models tested fared considerably worse, and we have no validated figure for our own pipeline yet. Portion size — judging grams from a flat photo with no scale reference — is consistently the dominant source of error.',
            'We are building an internal benchmark of photographed, weighed meals so we can publish measured numbers for our own pipeline, including how actual error relates to the confidence score. Until those numbers exist, treat the literature as the realistic baseline.',
        ];
    }

    /**
     * @return list<array{title: string, detail: string}>
     */
    public static function limitations(): array
    {
        return [
            [
                'title' => 'Portion size is the biggest source of error',
                'detail' => 'A single 2D photo carries no scale reference, so grams are an educated guess. Research consistently shows underestimation that grows with portion size — large meals get undercounted the most.',
            ],
            [
                'title' => 'Hidden ingredients are invisible',
                'detail' => "Cooking oils, butter, dressings, and added sugars don't show up in a photo, so they are typically undercounted. A salad with two tablespoons of olive oil looks identical to one with none.",
            ],
            [
                'title' => 'Mixed and layered dishes are harder',
                'detail' => "Curries, stews, casseroles, and anything occluded or blended reduce the model's ability to identify and separate individual ingredients.",
            ],
            [
                'title' => 'Cuisine coverage is uneven',
                'detail' => "Foods that are well documented in the model's training data fare better. Regional and home-style dishes may be misidentified or mapped to the nearest familiar equivalent.",
            ],
            [
                'title' => 'Image conditions matter',
                'detail' => 'Lighting, angle, and distance materially change results. A well-lit photo taken from directly above is the best case; dim, angled, or partial shots degrade accuracy.',
            ],
            [
                'title' => 'Results vary run to run',
                'detail' => 'AI model outputs are not deterministic. Analyzing the same photo twice can produce different estimates.',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function confidence(): array
    {
        return [
            'The confidence score is the model\'s self-assessment of how clearly it could see and recognize your meal — good lighting and visible, distinct items push it up; clutter, occlusion, and dim photos push it down.',
            'It is not a calibrated error range. A score of 85 does not mean the values are within 15% of the truth, and it does not mean an 85% chance of being right. We have not yet measured how actual error relates to this score — that mapping is one of the main goals of our internal benchmark.',
            'No confidence level makes these values appropriate for medication decisions. Use the score the way it is intended: as a prompt for healthy skepticism, not as a guarantee.',
        ];
    }

    /**
     * @return list<string>
     */
    public static function intendedUse(): array
    {
        return [
            'Acara Plate is a wellness and awareness aid. It is not a medical device, and it does not diagnose, treat, or manage any medical condition.',
            'Photo-estimated values are explicitly not validated for insulin dosing or clinical carb counting. If a number feeds a medication decision, it needs to come from a scale, a label, or your care team — not a photo.',
            'In the full app, every analysis is presented for your review — you can correct items and portions before anything is logged. We built it that way because the estimates are starting points, not verdicts.',
        ];
    }

    /**
     * @return list<string>
     */
    public static function photoHandling(): array
    {
        return [
            'On the public Snap to Track tool, your photo is stored only as a temporary upload while the analysis runs and is deleted as soon as the result or error is returned.',
            'The photo is processed by Google as an AI sub-processor to generate the analysis. It is not shared with anyone else and is never used to train AI models.',
            'Signed-in users can choose to save meals with photos to their personal history; on the public tool, no image is ever saved.',
        ];
    }

    public static function carbBoundaryNotice(): string
    {
        return 'Estimates, not measurements — never dose insulin or other medication from these numbers.';
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    public static function literature(): array
    {
        return [
            [
                'label' => 'Performance Evaluation of 3 Large Language Models for Nutritional Content Estimation from Food Images (2025)',
                'url' => 'https://www.ncbi.nlm.nih.gov/pmc/articles/PMC12513282/',
            ],
            [
                'label' => 'An Evaluation of ChatGPT for Nutrient Content Estimation from Meal Photographs — Nutrients 17(4):607 (2025)',
                'url' => 'https://www.mdpi.com/2072-6643/17/4/607',
            ],
            [
                'label' => 'Comprehensive Evaluation of Large Multimodal Models for Nutrition Analysis (2025)',
                'url' => 'https://arxiv.org/html/2507.07048',
            ],
        ];
    }

    private static function photoAnalyzerFaqAnswer(): string
    {
        if (self::usesReferenceLookup()) {
            return "Upload a photo of your meal and our AI vision model identifies each food item, estimates its portion size and weight, and produces calories, protein, carbs, and fat for every item. For foods it can confidently match, those values are computed from USDA FoodData Central reference data and flagged as reference-derived; the rest stay as the model's own estimate. Every analysis includes a confidence score.";
        }

        return 'Upload a photo of your meal and our AI vision model identifies each food item, estimates portion size, and calculates calories, protein, carbs, and fat for every item plus the full meal. The values are generated directly by the AI model — informed by standard nutrition references such as USDA FoodData Central, not looked up in a live database — and every analysis includes a confidence score.';
    }
}
