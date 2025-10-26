## User Profile

- **Age**: {{ $context['age'] ?? 'Not specified' }} years
- **Sex**: {{ $context['sex'] ? ucfirst($context['sex']) : 'Not specified' }}
- **Height**: {{ $context['height'] ?? 'Not specified' }} cm
- **Weight**: {{ $context['weight'] ?? 'Not specified' }} kg
@if($context['bmi'])
- **BMI**: {{ $context['bmi'] }}
@endif
@if($context['bmr'])
- **BMR (Basal Metabolic Rate)**: {{ $context['bmr'] }} calories/day
@endif
@if($context['tdee'])
- **TDEE (Total Daily Energy Expenditure)**: {{ $context['tdee'] }} calories/day
@endif

## Goals

@if($context['goal'])
- **Primary Goal**: {{ $context['goal'] }}
@endif
@if($context['targetWeight'])
- **Target Weight**: {{ $context['targetWeight'] }} kg
@endif
@if($context['additionalGoals'])
- **Additional Goals**: {{ $context['additionalGoals'] }}
@endif
@if($context['dailyCalorieTarget'])
- **Daily Calorie Target**: {{ $context['dailyCalorieTarget'] }} calories
@endif

## Macronutrient Targets

Based on the user's goals, aim for the following macronutrient distribution:
- **Protein**: {{ $context['macronutrientRatios']['protein'] }}%
- **Carbohydrates**: {{ $context['macronutrientRatios']['carbs'] }}%
- **Fat**: {{ $context['macronutrientRatios']['fat'] }}%

## Lifestyle

@if($context['lifestyle'])
- **Activity Level**: {{ $context['lifestyle']['activityLevel'] }}
- **Lifestyle Type**: {{ $context['lifestyle']['name'] }}
@if($context['lifestyle']['sleepHours'])
- **Sleep Hours**: {{ $context['lifestyle']['sleepHours'] }}
@endif
@if($context['lifestyle']['occupation'])
- **Occupation**: {{ $context['lifestyle']['occupation'] }}
@endif
@if($context['lifestyle']['description'])
- **Description**: {{ $context['lifestyle']['description'] }}
@endif
@else
- No lifestyle information provided
@endif

## Dietary Preferences

@if(count($context['dietaryPreferences']) > 0)
@foreach($context['dietaryPreferences'] as $preference)
- **{{ $preference['name'] }}** ({{ $preference['type'] }})
@if($preference['description'])
  - {{ $preference['description'] }}
@endif
@endforeach
@else
- No specific dietary preferences
@endif

## Health Conditions

@if(count($context['healthConditions']) > 0)
@foreach($context['healthConditions'] as $condition)
### {{ $condition['name'] }}
@if($condition['description'])
- **Description**: {{ $condition['description'] }}
@endif
@if($condition['nutritionalImpact'])
- **Nutritional Impact**: {{ $condition['nutritionalImpact'] }}
@endif
@if($condition['recommendedNutrients'] && count($condition['recommendedNutrients']) > 0)
- **Recommended Nutrients**: {{ implode(', ', $condition['recommendedNutrients']) }}
@endif
@if($condition['nutrientsToLimit'] && count($condition['nutrientsToLimit']) > 0)
- **Nutrients to Limit**: {{ implode(', ', $condition['nutrientsToLimit']) }}
@endif
@if($condition['notes'])
- **User Notes**: {{ $condition['notes'] }}
@endif

@endforeach
@else
- No health conditions reported
@endif

## Task

Create a comprehensive and personalized 7-day meal plan that:

1. **Meets caloric targets**: Each day should be close to {{ $context['dailyCalorieTarget'] ?? $context['tdee'] ?? 'the calculated' }} calories
2. **Respects dietary preferences**: Only include foods that align with the user's dietary restrictions and preferences
3. **Addresses health conditions**: Consider nutritional impacts, recommended nutrients, and nutrients to limit
4. **Fits lifestyle**: Consider activity level and daily routine
5. **Achieves goals**: Support the user's primary goal of {{ $context['goal'] ?? 'maintaining health' }}
6. **Provides variety**: Include diverse meals throughout the week
7. **Is practical**: Use common ingredients and reasonable preparation times

For each day, provide:
- **Breakfast** (with estimated calories and macros)
- **Lunch** (with estimated calories and macros)
- **Dinner** (with estimated calories and macros)
- **Snacks** (1-2 snacks with estimated calories and macros)
- **Daily total** (total calories and macro breakdown)

Include brief preparation instructions and portion sizes for each meal.

## Output Format

- Set `type` to `weekly`, `duration_days` to `7`, and align `target_daily_calories` with the goal-adjusted target above (fall back to TDEE if the target is missing).
- Keep `macronutrient_ratios` aligned with the percentages provided in the context.
- Populate the `meals` array with every eating occasion for all 7 days. Use `day_number` values from 1 through 7, `sort_order` values that reflect the chronological order (Breakfast `1`, morning snack `2`, Lunch `3`, afternoon snack `4`, Dinner `5`).
- Set the `type` field on each meal to one of `breakfast`, `lunch`, `dinner`, or `snack`. Create separate snack entries when multiple snacks are required for the day.
- Provide concise meal names, vivid descriptions, newline-separated ingredient lists (`Ingredient – quantity`), practical preparation instructions, precise portion sizes, estimated calories, and macro grams for each meal.
- Supply realistic `preparation_time_minutes` values (integer minutes) for every meal.

Return only the structured data that follows these instructions.
