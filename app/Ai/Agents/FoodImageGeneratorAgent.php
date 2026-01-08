<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use Illuminate\Support\Facades\Storage;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;

final class FoodImageGeneratorAgent
{
    /**
     * Generate an infographic-style food image with nutritional annotations.
     *
     * @param  array{calories: float, protein: float, carbs: float, fat: float, fiber: float, sugar: float}  $nutrition
     */
    public function generate(string $foodName, array $nutrition, string $slug): ?string
    {
        $prompt = $this->buildPrompt($foodName, $nutrition);

        $response = Prism::image()
            ->using(Provider::Gemini, 'gemini-2.5-flash-image')
            ->withPrompt($prompt)
            ->generate();

        if (! $response->hasImages()) {
            return null;
        }

        $image = $response->firstImage();

        if (! $image->hasBase64()) {
            return null;
        }

        $relativePath = "food-images/{$slug}.png";

        Storage::disk('s3_public')->put($relativePath, base64_decode((string) $image->base64));

        return $relativePath;
    }

    /**
     * @param  array{calories: float, protein: float, carbs: float, fat: float, fiber: float, sugar: float}  $nutrition
     */
    private function buildPrompt(string $foodName, array $nutrition): string
    {
        $calories = number_format($nutrition['calories'], 0);
        $protein = number_format($nutrition['protein'], 1);
        $carbs = number_format($nutrition['carbs'], 1);
        $fat = number_format($nutrition['fat'], 1);
        $fiber = number_format($nutrition['fiber'], 1);
        $sugar = number_format($nutrition['sugar'], 1);

        return <<<PROMPT
Create a clean, modern food infographic image for "{$foodName}".

Visual style:
– Top-down or 45-degree angle view of fresh {$foodName} on a clean white or light gray background
– Photorealistic rendering with soft, even studio lighting
– Minimalist composition with plenty of whitespace
– The food should look fresh, appetizing, and professionally photographed

Nutritional information to display (per 100g):
– Calories: {$calories} kcal
– Protein: {$protein}g
– Carbs: {$carbs}g
– Fat: {$fat}g
– Fiber: {$fiber}g
– Sugar: {$sugar}g

Annotation design guidelines:
– Clean sans-serif font (like Helvetica or Arial), medium weight
– Text placed inside minimal rectangular frames or boxes with subtle rounded corners
– Thin, precise connector lines pointing directly from each annotation to the food
– High readability, no text overlap, no decorative excess
– Structured vertical layout on the right side, like a modern recipe card or nutrition label
– Use a consistent color scheme: dark gray (#333) text, light accent color (#F5F5F5) for frames
– Small icons next to each nutritional value (optional)

The overall aesthetic should be:
– Professional medical/health publication quality
– Clean, trustworthy, and educational
– Similar to infographics found in nutrition guides or health apps
PROMPT;
    }
}
