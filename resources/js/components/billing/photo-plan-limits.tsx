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
        <div className="mt-4 flex flex-col gap-2 text-sm">
            <p className="font-medium">
                {t('checkout_subscription.limits.heading')}
            </p>
            <ul className="flex flex-col gap-1">
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
            <p className="text-xs text-muted-foreground">
                {t('checkout_subscription.limits.shared')}
            </p>
            <p className="text-xs text-muted-foreground">
                {t('checkout_subscription.limits.reset')}
            </p>
        </div>
    );
}
