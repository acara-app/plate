import { Link } from '@inertiajs/react';
import { Lock } from 'lucide-react';
import { useEffect } from 'react';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/button';
import type { PhotoAllowance } from '@/components/photo-allowance';
import checkout from '@/routes/checkout';

export interface BurstLimit {
    tier: string;
    tier_label: string;
    cap: number;
    retry_after_seconds: number;
    retry_after_minutes: number;
    resets_at: string | null;
    offer?: NonNullable<PhotoAllowance['offer']>;
}

interface BurstLimitNoticeProps {
    limit: BurstLimit;
}

export function BurstLimitNotice({ limit }: BurstLimitNoticeProps) {
    const { t } = useTranslation('common');
    const offer = limit.offer;

    useEffect(() => {
        window.umami?.track('snap_to_track_offer_viewed', {
            gate: 'burst',
            tier: limit.tier,
        });
    }, [limit.tier]);

    return (
        <div
            role="status"
            aria-live="polite"
            className="flex w-full items-start gap-3 rounded-xl border border-amber-300/70 bg-amber-50/70 p-4 sm:p-5 dark:border-amber-900/60 dark:bg-amber-950/30"
        >
            <span className="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                <Lock className="size-4" />
            </span>
            <div className="flex-1 space-y-2">
                <p className="text-sm font-medium text-foreground">
                    {t('snap_to_track.burst.heading')}
                </p>
                <p className="text-xs text-muted-foreground">
                    {t('snap_to_track.burst.body', {
                        cap: limit.cap,
                        tier: t(`billing.tier.labels.${limit.tier}`, {
                            defaultValue: limit.tier_label,
                        }),
                        minutes: limit.retry_after_minutes,
                    })}
                </p>
                {offer ? (
                    <>
                        <Button asChild size="sm">
                            <Link
                                href={checkout.start(offer.product_id).url}
                                data-umami-event="snap_to_track_upgrade_click"
                                data-umami-event-gate="burst"
                                data-umami-event-tier={limit.tier}
                            >
                                {t('snap_to_track.burst.upgrade', {
                                    name: offer.name,
                                    price: offer.formatted_price,
                                })}
                            </Link>
                        </Button>
                        <p className="text-xs text-muted-foreground">
                            {t('snap_to_track.burst.upgrade_hint', {
                                scans: offer.scans,
                            })}
                        </p>
                    </>
                ) : (
                    <Link
                        href={checkout.subscription().url}
                        data-umami-event="snap_to_track_upgrade_click"
                        data-umami-event-gate="burst"
                        data-umami-event-tier={limit.tier}
                        className="inline-block text-xs font-medium text-muted-foreground underline underline-offset-4 transition hover:text-foreground"
                    >
                        {t('snap_to_track.burst.see_plans')}
                    </Link>
                )}
            </div>
        </div>
    );
}
