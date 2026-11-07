@props(['limits' => null])

@if ($limits)
    <details class="mt-4 rounded-lg border border-current/20 p-3 text-sm">
        <summary class="cursor-pointer font-medium">{{ __('common.checkout_subscription.limits.heading') }}</summary>
        <ul class="mt-3 flex flex-col gap-2">
            <li>{!! e(strtr(__('common.checkout_subscription.limits.scans'), ['@{{count}}' => (string) $limits->scansPerHour])) !!}</li>
            <li>{!! e(strtr(__('common.checkout_subscription.limits.rolling'), ['@{{credits}}' => number_format($limits->rollingCredits), '@{{hours}}' => (string) $limits->rollingHours])) !!}</li>
            <li>{!! e(strtr(__('common.checkout_subscription.limits.weekly'), ['@{{credits}}' => number_format($limits->weeklyCredits), '@{{days}}' => (string) $limits->weeklyDays])) !!}</li>
        </ul>
        <p class="mt-3 text-xs leading-relaxed">{{ __('common.checkout_subscription.limits.shared') }}</p>
        <p class="mt-2 text-xs leading-relaxed">{{ __('common.checkout_subscription.limits.reset') }}</p>
        <p class="mt-2 text-xs leading-relaxed">{{ __('common.checkout_subscription.limits.blocked') }}</p>
    </details>
@endif
