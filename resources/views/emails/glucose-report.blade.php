<x-mail::message>
# Your Weekly Glucose Report

Hello! Here's your glucose summary for the past {{ $daysAnalyzed }} days:

## Glucose Overview

@if($averageGlucose)
**Average Glucose:** {{ $averageGlucose }} mg/dL
@endif

**Time in Range:** {{ $timeInRangePercentage }}%
- Above range: {{ $aboveRangePercentage }}%
- Below range: {{ $belowRangePercentage }}%

**Total Readings:** {{ $totalReadings }}

@if(count($concerns) > 0)
---

## Areas of Attention

@foreach($concerns as $concern)
- {{ $concern }}
@endforeach
@endif

---

Consider reviewing your meal plan to help improve your glucose levels.

<x-mail::button :url="$mealPlanUrl">
View Meal Plans
</x-mail::button>

Stay healthy!

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
