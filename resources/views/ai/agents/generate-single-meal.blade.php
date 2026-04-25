# Meal Generation Task

Generate a personalized single meal suggestion for a user with the following profile:

{{ $profileContext }}

## Meal Requirements

- **Meal Type**: {{ $mealType }}
@if($cuisine)
- **Cuisine Style**: {{ $cuisine }}
@endif
@if($maxCalories)
- **Maximum Calories**: {{ $maxCalories }}
@endif
@if($specificRequest)
- **Specific Request**: {{ $specificRequest }}
@endif

@include('ai.prompts.partials.language', [
    'language' => $language,
    'languageCode' => $languageCode,
    'contentNoun' => 'meal content',
    'scopes' => [
        '`name` — meal name',
        '`description` — meal description',
        '`cuisine` — cuisine label',
        '`ingredients[]` — ingredient/food names',
        '`instructions[]` — cooking steps',
        '`dietary_tags[]` — descriptive tags',
        '`glycemic_index_estimate` — GI description',
        '`glucose_impact_notes` — glucose impact notes',
    ],
])

## Instructions

1. Create a single, complete meal suggestion appropriate for the user's dietary needs and health conditions
2. Provide accurate nutritional estimates based on standard portion sizes
3. Consider glucose impact for users with diabetes or blood sugar concerns
4. Ensure the meal fits within any specified calorie limits
5. Use common, accessible ingredients
