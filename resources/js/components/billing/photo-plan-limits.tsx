import { useTranslation } from 'react-i18next';

export interface PhotoPlanLimits {
    scans_per_hour: number;
    rolling_credits: number;
    rolling_hours: number;
    weekly_credits: number;
    weekly_days: number;
}

export default function PhotoPlanLimitsDetails({
    limits,
}: {
    limits?: PhotoPlanLimits | null;
}) {
    const { t } = useTranslation('common');

    if (!limits) return null;

    return (
        <details className="mt-4 rounded-lg border p-3 text-sm">
            <summary className="cursor-pointer font-medium">
                {t('checkout_subscription.limits.heading')}
            </summary>
            <ul className="mt-3 flex flex-col gap-2">
                <li>
                    {t('checkout_subscription.limits.scans', {
                        count: limits.scans_per_hour,
                    })}
                </li>
                <li>
                    {t('checkout_subscription.limits.rolling', {
                        credits: limits.rolling_credits.toLocaleString(),
                        hours: limits.rolling_hours,
                    })}
                </li>
                <li>
                    {t('checkout_subscription.limits.weekly', {
                        credits: limits.weekly_credits.toLocaleString(),
                        days: limits.weekly_days,
                    })}
                </li>
            </ul>
            <p className="mt-3 text-xs leading-relaxed text-muted-foreground">
                {t('checkout_subscription.limits.shared')}
            </p>
            <p className="mt-3 text-xs leading-relaxed text-muted-foreground">
                {t('checkout_subscription.limits.reset')}
            </p>
            <p className="mt-2 text-xs leading-relaxed text-muted-foreground">
                {t('checkout_subscription.limits.blocked')}
            </p>
        </details>
    );
}
