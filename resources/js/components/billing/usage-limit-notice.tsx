import { router } from '@inertiajs/react';
import { Lock, X } from 'lucide-react';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/button';
import checkout from '@/routes/checkout';
import type { PaidSubscriptionTier, PaywallCapTrigger } from '@/types';

interface UsageLimitNoticeProps {
    trigger: PaywallCapTrigger;
    onDismiss?: () => void;
    onUpgradeClick?: (target: PaidSubscriptionTier) => void;
}

function defaultTargetTier(
    currentTier: PaywallCapTrigger['currentTier'],
): PaidSubscriptionTier | null {
    if (currentTier === 'plus') {
        return null;
    }

    return currentTier === 'basic' ? 'plus' : 'basic';
}

export function UsageLimitNotice({
    trigger,
    onDismiss,
    onUpgradeClick,
}: UsageLimitNoticeProps) {
    const { t } = useTranslation('common');
    const targetTier = defaultTargetTier(trigger.currentTier);

    const heading = t('billing.paywall.cap_title', {
        tier: t(`billing.tier.labels.${trigger.currentTier}`, {
            defaultValue: trigger.currentTier,
        }),
        limit: t(`billing.usage.${trigger.limitType}`),
    });

    const body = t('billing.paywall.cap_description', {
        current: trigger.currentCredits.toLocaleString(),
        total: trigger.limitCredits.toLocaleString(),
        time: trigger.resetsIn,
    });

    const handleUpgrade = (tier: PaidSubscriptionTier) => {
        onUpgradeClick?.(tier);
        router.visit(checkout.subscription().url);
    };

    return (
        <div
            role="status"
            aria-live="polite"
            className="flex w-full items-start gap-3 rounded-xl border border-amber-300/70 bg-amber-50/70 p-4 dark:border-amber-900/60 dark:bg-amber-950/30 sm:p-5"
        >
            <span className="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                <Lock className="size-4" />
            </span>
            <div className="flex-1 space-y-2">
                <p className="text-sm font-medium text-foreground">{heading}</p>
                <p className="text-xs text-muted-foreground">{body}</p>
                {targetTier && (
                    <Button
                        type="button"
                        size="sm"
                        onClick={() => handleUpgrade(targetTier)}
                    >
                        {t('billing.paywall.upgrade_to', {
                            tier: t(
                                `billing.paywall.plans.${targetTier}.name`,
                            ),
                        })}
                    </Button>
                )}
            </div>
            {onDismiss && (
                <button
                    type="button"
                    onClick={onDismiss}
                    aria-label={t('close')}
                    className="text-muted-foreground transition hover:text-foreground"
                >
                    <X className="size-4" />
                </button>
            )}
        </div>
    );
}
