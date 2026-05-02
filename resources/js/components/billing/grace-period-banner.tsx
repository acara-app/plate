import { Link } from '@inertiajs/react';
import { CalendarClock } from 'lucide-react';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/button';
import checkout from '@/routes/checkout';
import type { Entitlement } from '@/types';

interface GracePeriodBannerProps {
    entitlement: Entitlement;
    className?: string;
}

export function GracePeriodBanner({
    entitlement,
    className,
}: GracePeriodBannerProps) {
    const { t } = useTranslation('common');

    if (
        !entitlement.on_grace_period ||
        entitlement.grace_period_ends_at === null
    ) {
        return null;
    }

    const endsAt = new Date(entitlement.grace_period_ends_at);
    const formattedDate = endsAt.toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });

    return (
        <div
            data-testid="grace-period-banner"
            role="status"
            aria-live="polite"
            className={
                'flex w-full items-start gap-3 rounded-lg border border-orange-300 bg-orange-50 p-4 dark:border-orange-900/50 dark:bg-orange-950/40 ' +
                (className ?? '')
            }
        >
            <CalendarClock className="mt-0.5 size-5 shrink-0 text-orange-700 dark:text-orange-400" />
            <div className="flex-1 space-y-1">
                <p className="text-sm font-medium text-orange-900 dark:text-orange-100">
                    {t('billing.lifecycle.grace_period_heading', {
                        date: formattedDate,
                    })}
                </p>
                <p className="text-xs text-orange-800 dark:text-orange-200">
                    {t('billing.lifecycle.grace_period_body', {
                        tier: entitlement.tier_label,
                    })}
                </p>
            </div>
            <Button
                asChild
                variant="outline"
                size="sm"
                className="border-orange-600 text-orange-900 hover:bg-orange-100 dark:border-orange-500/50 dark:text-orange-100 dark:hover:bg-orange-900/40"
            >
                <Link href={checkout.subscription().url}>
                    {t('billing.lifecycle.grace_period_action')}
                </Link>
            </Button>
        </div>
    );
}
