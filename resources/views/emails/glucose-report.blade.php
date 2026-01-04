<x-mail::message>
# {{ __('common.glucose_report_email.subject') }}

{{ __('common.glucose_report_email.greeting', ['days' => $daysAnalyzed]) }}

## {{ __('common.glucose_report_email.glucose_overview') }}

@if($averageGlucose)
**{{ __('common.glucose_report_email.average_glucose') }}** {{ $averageGlucose }} mg/dL
@endif

**{{ __('common.glucose_report_email.time_in_range') }}** {{ $timeInRangePercentage }}%
- {{ __('common.glucose_report_email.above_range') }} {{ $aboveRangePercentage }}%
- {{ __('common.glucose_report_email.below_range') }} {{ $belowRangePercentage }}%

**{{ __('common.glucose_report_email.total_readings') }}** {{ $totalReadings }}

@if(count($concerns) > 0)
---

## {{ __('common.glucose_report_email.areas_of_attention') }}

@foreach($concerns as $concern)
- {{ $concern }}
@endforeach
@endif

---

{{ __('common.glucose_report_email.review_meal_plan') }}

<x-mail::button :url="$mealPlanUrl">
{{ __('common.glucose_report_email.view_meal_plans') }}
</x-mail::button>

{{ __('common.glucose_report_email.stay_healthy') }}

{{ __('common.glucose_report_email.thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
