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

## Instructions

1. Create a single, complete meal suggestion appropriate for the user's dietary needs and health conditions
2. Provide accurate nutritional estimates based on standard portion sizes
3. Consider glucose impact for users with diabetes or blood sugar concerns
4. Ensure the meal fits within any specified calorie limits
5. Use common, accessible ingredients
