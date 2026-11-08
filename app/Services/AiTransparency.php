<?php

declare(strict_types=1);

namespace App\Services;

final class AiTransparency
{
    public const string LAST_REVIEWED = '2026-11-08';

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
                'a' => 'Yes. Your photo is used to estimate the nutrition in your meal and is never used to train AI models. While the scan runs, we keep an encrypted copy so a dropped connection never means uploading again. That copy is deleted as soon as your result is ready. If a scan fails, you can retry it for up to an hour, then the copy is deleted automatically. Only your browser or your account can open the result. If you are signed in, the photo is kept privately with the meal you save and deleted when you delete the meal. Our team only looks at saved photos if you turn on Help improve food recognition.',
            ],
            [
                'q' => 'How do I use Snap to Track?',
                'a' => 'Open this page on your phone or laptop, tap the upload area to take a new photo or pick one from your gallery, then tap Analyze Food. In about 5–15 seconds you get a per-item breakdown of calories, protein, carbs, and fat plus meal totals. No signup is required to try it; create a free account to save and track meals over time.',
            ],
            [
                'q' => 'Can Acara remember meals I scan?',
                'a' => 'The free public analyzer runs an instant scan without saving long-term context. With an Acara Cloud account, saved meals, meal history, stable preferences, and recurring goals can help future chats start with more useful context.',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function pipeline(): array
    {
        return [
            'Your photo goes through Acara\'s multimodal AI pipeline — a single pass that combines image understanding with nutrition reasoning to read your meal.',
            'In that one pass, the model identifies each distinct food item it can see, estimates the portion size visually, and generates calories, protein, carbohydrate, fat, and fiber for every item plus meal totals — values drawn from its training knowledge of standard nutrition references such as USDA FoodData Central, not retrieved from a database.',
            "Every analysis also returns a confidence score from 0 to 100, which is the model's own assessment of how clearly it could see and recognize the meal.",
            'The same engine powers both the free Snap to Track tool and the full app. The difference: in the full app, scans save straight to your food log, where you can edit any item or portion at any time.',
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
            'In the full app, you can correct any item or portion of a logged meal at any time. We built it that way because the estimates are starting points, not verdicts.',
        ];
    }

    /**
     * @return list<string>
     */
    public static function photoHandling(): array
    {
        return [
            'On the public Snap to Track tool, your photo is kept as an encrypted copy only while the analysis runs. It is deleted as soon as your result is ready, and if a scan fails you can retry it for up to an hour before the copy is deleted.',
            'The photo is processed by Google as an AI sub-processor to generate the analysis. It is not shared with any other organization and is never used to train AI models.',
            'When you are signed in, the photo is kept privately with the meal you save, resized and with its location data removed, and is deleted when you delete the meal or your account. Our team only looks at saved photos if you turn on Help improve food recognition.',
        ];
    }

    public static function carbBoundaryNotice(): string
    {
        return (string) __('tools.carb_boundary_notice');
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
        return 'Upload a photo of your meal and our AI vision model identifies each food item, estimates portion size, and calculates calories, protein, carbs, fat, and fiber for every item plus the full meal. The values are generated directly by the AI model — informed by standard nutrition references such as USDA FoodData Central, not looked up in a live database — and every analysis includes a confidence score.';
    }
}
