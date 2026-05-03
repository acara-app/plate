import { Link } from '@inertiajs/react';
import { Sparkles } from 'lucide-react';
import { useTranslation } from 'react-i18next';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import checkout from '@/routes/checkout';
import type { GatedFeature, PaidSubscriptionTier } from '@/types';

interface UpsellCardProps {
    feature: GatedFeature;
    requiredTier: PaidSubscriptionTier;
    className?: string;
    onUpgradeClick?: () => void;
}

export function UpsellCard({
    feature,
    requiredTier,
    className,
    onUpgradeClick,
}: UpsellCardProps) {
    const { t } = useTranslation('common');

    const tierName = t(`billing.paywall.plans.${requiredTier}.name`);
    const tierPrice = t(`billing.paywall.plans.${requiredTier}.price`);
    const featureName = t(`billing.paywall.features.${feature}`);
    const tierPitch = t(`billing.paywall.plans.${requiredTier}.pitch`);

    return (
        <div
            data-testid={`upsell-card-${feature}`}
            className={
                'flex flex-col gap-4 rounded-xl border border-primary/30 bg-primary/5 p-6 sm:flex-row sm:items-start sm:gap-6 ' +
                (className ?? '')
            }
        >
            <span className="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                <Sparkles className="size-5" />
            </span>
            <div className="flex-1 space-y-3">
                <div className="space-y-1">
                    <Badge variant="secondary" className="uppercase">
                        {t('billing.upsell.card_eyebrow', { tier: tierName })}
                    </Badge>
                    <h3 className="text-lg font-semibold tracking-tight">
                        {t('billing.upsell.card_title', {
                            feature: featureName,
                        })}
                    </h3>
                    <p className="text-sm text-muted-foreground">
                        {t('billing.upsell.card_description', {
                            tier: tierName,
                            price: tierPrice,
                            feature: featureName.toLowerCase(),
                        })}
                    </p>
                </div>
                <p className="text-xs text-muted-foreground">{tierPitch}</p>
                <Button asChild onClick={onUpgradeClick}>
                    <Link href={checkout.subscription().url}>
                        {t('billing.upsell.upgrade_button', {
                            tier: tierName,
                        })}
                    </Link>
                </Button>
            </div>
        </div>
    );
}
