import { Camera, Check } from 'lucide-react';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/button';

export interface PhotoPlan {
    id: number;
    name: string;
    description: string;
    features: string[];
    formatted_price: string;
}

interface PhotoPlanBandProps {
    plan: PhotoPlan;
    isGuest: boolean;
    isSubscribing: boolean;
    isCurrent: boolean;
    onSubscribe: () => void;
}

export function PhotoPlanBand({
    plan,
    isGuest,
    isSubscribing,
    isCurrent,
    onSubscribe,
}: PhotoPlanBandProps) {
    const { t } = useTranslation('common');

    return (
        <div className="overflow-hidden rounded-lg border border-emerald-300/70 bg-emerald-50/60 shadow-sm dark:border-emerald-900/60 dark:bg-emerald-950/25">
            <div className="flex flex-col gap-5 p-6 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex flex-1 items-start gap-4">
                    <span className="flex size-10 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                        <Camera className="size-5" />
                    </span>
                    <div className="space-y-2">
                        <div className="flex flex-wrap items-baseline gap-x-2">
                            <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {plan.name}
                            </h2>
                            <span className="text-sm text-muted-foreground">
                                {t('checkout_subscription.photo_plan.price', {
                                    price: plan.formatted_price,
                                })}
                            </span>
                        </div>
                        <p className="text-sm text-gray-700 dark:text-gray-300">
                            {plan.description}
                        </p>
                        <ul className="flex flex-wrap gap-x-4 gap-y-1">
                            {plan.features.map((feature) => (
                                <li
                                    key={feature}
                                    className="flex items-center gap-1.5 text-xs text-muted-foreground"
                                >
                                    <Check className="size-3.5 shrink-0 text-emerald-600 dark:text-emerald-400" />
                                    {feature}
                                </li>
                            ))}
                        </ul>
                        <p className="text-xs text-muted-foreground">
                            {t('checkout_subscription.photo_plan.separate')}
                        </p>
                    </div>
                </div>
                <div className="shrink-0 sm:text-right">
                    {isCurrent ? (
                        <span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1.5 text-sm font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">
                            <Check className="size-4" />
                            {t('checkout_subscription.photo_plan.current')}
                        </span>
                    ) : (
                        <>
                            <Button
                                type="button"
                                onClick={onSubscribe}
                                disabled={isSubscribing}
                                data-umami-event="snap_to_track_upgrade_click"
                                data-umami-event-gate="pricing"
                            >
                                {isGuest
                                    ? t(
                                          'checkout_subscription.photo_plan.guest_cta',
                                      )
                                    : t('checkout_subscription.photo_plan.cta')}
                            </Button>
                            <p className="mt-2 text-xs text-muted-foreground">
                                {t('checkout_subscription.photo_plan.renewal')}
                            </p>
                        </>
                    )}
                </div>
            </div>
        </div>
    );
}
