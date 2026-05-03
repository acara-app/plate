import { router } from '@inertiajs/react';
import { Check, Lock } from 'lucide-react';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import checkout from '@/routes/checkout';
import type {
    GatedFeature,
    PaidSubscriptionTier,
    PaywallTrigger,
    SubscriptionTier,
} from '@/types';

interface PaywallModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    trigger: PaywallTrigger;
    onUpgradeClick?: (targetTier: PaidSubscriptionTier) => void;
    onComparePlansClick?: () => void;
}

const PAID_TIERS: PaidSubscriptionTier[] = ['basic', 'plus'];

function defaultTargetTier(trigger: PaywallTrigger): PaidSubscriptionTier {
    if (trigger.kind === 'feature') {
        return trigger.requiredTier;
    }

    return trigger.currentTier === 'basic' ? 'plus' : 'basic';
}

function tiersToShow(
    currentTier: SubscriptionTier,
    targetTier: PaidSubscriptionTier,
): PaidSubscriptionTier[] {
    if (currentTier === 'basic') {
        return ['plus'];
    }

    return PAID_TIERS.includes(targetTier) ? PAID_TIERS : [targetTier];
}

export function PaywallModal({
    open,
    onOpenChange,
    trigger,
    onUpgradeClick,
    onComparePlansClick,
}: PaywallModalProps) {
    const { t } = useTranslation('common');
    const targetTier = defaultTargetTier(trigger);
    const visibleTiers = tiersToShow(trigger.currentTier, targetTier);
    const checkoutUrl = checkout.subscription().url;

    const handleUpgrade = (tier: PaidSubscriptionTier) => {
        onUpgradeClick?.(tier);
        router.visit(checkoutUrl);
    };

    const handleCompare = () => {
        onComparePlansClick?.();
        router.visit(checkoutUrl);
    };

    const heading =
        trigger.kind === 'cap'
            ? t('billing.paywall.cap_title', {
                  tier: t(`billing.tier.labels.${trigger.currentTier}`, {
                      defaultValue: trigger.currentTier,
                  }),
                  limit: t(`billing.usage.${trigger.limitType}`),
              })
            : t('billing.paywall.feature_title', {
                  feature: t(`billing.paywall.features.${trigger.feature}`),
              });

    const description =
        trigger.kind === 'cap'
            ? t('billing.paywall.cap_description', {
                  current: trigger.currentCredits.toLocaleString(),
                  total: trigger.limitCredits.toLocaleString(),
                  time: trigger.resetsIn,
              })
            : t('billing.paywall.feature_description', {
                  feature: t(
                      `billing.paywall.features.${trigger.feature}`,
                  ).toLowerCase(),
                  tier: t(`billing.paywall.plans.${trigger.requiredTier}.name`),
              });

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-xl">
                <DialogHeader>
                    <div className="flex items-center gap-2">
                        <span className="flex size-8 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <Lock className="size-4" />
                        </span>
                        <DialogTitle className="text-left">
                            {heading}
                        </DialogTitle>
                    </div>
                    <DialogDescription className="pt-1 text-left">
                        {description}
                    </DialogDescription>
                </DialogHeader>

                <div className="grid gap-3 sm:grid-cols-2">
                    {visibleTiers.map((tier) => (
                        <PlanOption
                            key={tier}
                            tier={tier}
                            primary={tier === targetTier}
                            onClick={() => handleUpgrade(tier)}
                        />
                    ))}
                </div>

                <DialogFooter className="sm:items-center sm:justify-between">
                    <Button
                        type="button"
                        variant="link"
                        size="sm"
                        onClick={handleCompare}
                        className="h-auto p-0"
                    >
                        {t('billing.paywall.compare_plans')}
                    </Button>
                    <Button
                        type="button"
                        onClick={() => handleUpgrade(targetTier)}
                        autoFocus
                    >
                        {t('billing.paywall.upgrade_to', {
                            tier: t(`billing.paywall.plans.${targetTier}.name`),
                        })}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

interface PlanOptionProps {
    tier: PaidSubscriptionTier;
    primary: boolean;
    onClick: () => void;
}

function PlanOption({ tier, primary, onClick }: PlanOptionProps) {
    const { t } = useTranslation('common');
    const name = t(`billing.paywall.plans.${tier}.name`);
    const price = t(`billing.paywall.plans.${tier}.price`);
    const pitch = t(`billing.paywall.plans.${tier}.pitch`);

    return (
        <button
            type="button"
            onClick={onClick}
            data-testid={`paywall-plan-${tier}`}
            className={
                primary
                    ? 'flex flex-col gap-2 rounded-lg border-2 border-primary bg-primary/5 p-4 text-left transition hover:bg-primary/10'
                    : 'flex flex-col gap-2 rounded-lg border border-border bg-card p-4 text-left transition hover:border-primary/50'
            }
        >
            <div className="flex items-baseline justify-between gap-2">
                <span className="text-base font-semibold">{name}</span>
                <span className="text-sm font-medium text-muted-foreground">
                    {price}
                </span>
            </div>
            <p className="flex items-start gap-1.5 text-xs text-muted-foreground">
                <Check className="mt-0.5 size-3.5 shrink-0 text-emerald-500" />
                {pitch}
            </p>
        </button>
    );
}

export type { GatedFeature, PaywallTrigger };
