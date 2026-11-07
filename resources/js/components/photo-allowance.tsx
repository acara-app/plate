import PhotoPlanLimitsDetails, {
    type PhotoPlanLimits,
} from '@/components/billing/photo-plan-limits';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import checkout from '@/routes/checkout';
import { snapToTrack } from '@/routes';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { useEffect, useRef } from 'react';

const seenOffers = new Set<string>();

export interface PhotoAllowance {
    enabled: boolean;
    limit: number | null;
    used: number;
    remaining: number | null;
    exhausted: boolean;
    resets_at: string | null;
    mode: string;
    can_upgrade: boolean;
    variant?: string | null;
    preview?: {
        status: 'available' | 'active' | 'expired';
        starts_at: string | null;
        expires_at: string | null;
        next_scan_at: string | null;
    } | null;
    offer?: {
        product_id: number;
        name: string;
        formatted_price: string;
        limits?: PhotoPlanLimits | null;
    } | null;
}

export function PhotoAllowanceCard({
    allowance,
    showUpgrade = true,
    variant = 'card',
}: {
    allowance?: PhotoAllowance;
    showUpgrade?: boolean;
    variant?: 'card' | 'plain';
}) {
    const { t } = useTranslation('common');
    const offerRef = useRef<HTMLDivElement>(null);
    const showOffer = !!(
        showUpgrade &&
        allowance?.can_upgrade &&
        allowance.offer &&
        (allowance.exhausted || allowance.preview)
    );
    useEffect(() => {
        if (!showOffer || !offerRef.current || !allowance) {
            return;
        }
        const key = JSON.stringify([
            allowance.variant,
            allowance.mode,
            allowance.used,
            allowance.resets_at,
        ]);
        const observer = new IntersectionObserver(
            ([entry]) => {
                if (entry.isIntersecting && !seenOffers.has(key)) {
                    seenOffers.add(key);
                    window.umami?.track('snap_to_track_offer_viewed', {
                        source: 'app',
                        mode: allowance.mode,
                        variant: allowance.variant ?? 'baseline',
                    });
                    observer.disconnect();
                }
            },
            { threshold: 0.5 },
        );
        observer.observe(offerRef.current);
        return () => observer.disconnect();
    }, [showOffer, allowance]);
    if (!allowance?.enabled || allowance.limit === null) {
        return null;
    }

    const remaining = allowance.remaining ?? 0;
    const trial = allowance.mode === 'trial';
    const offer = allowance.offer;

    return (
        <Card
            className={cn(
                variant === 'plain' &&
                    'gap-2 rounded-none border-0 border-b bg-transparent py-0 pb-5 shadow-none',
            )}
        >
            <CardHeader className={cn(variant === 'plain' && 'px-0')}>
                <CardTitle
                    className={cn(variant === 'plain' && 'text-sm leading-6')}
                >
                    {trial
                        ? remaining > 0
                            ? t('photo_plans.free.remaining', {
                                  count: remaining,
                              })
                            : t('photo_plans.free.used')
                        : t('photo_plans.offer.remaining', {
                              remaining,
                              total: allowance.limit,
                          })}
                </CardTitle>
            </CardHeader>
            <CardContent
                className={cn('space-y-3', variant === 'plain' && 'px-0')}
            >
                {trial && (
                    <p className="text-sm text-muted-foreground">
                        {t('photo_plans.free.total', {
                            count: allowance.limit,
                        })}
                    </p>
                )}
                {allowance.resets_at && (
                    <p className="text-sm text-muted-foreground">
                        {t('photo_plans.offer.resets', {
                            time: new Date(
                                allowance.resets_at,
                            ).toLocaleString(),
                        })}
                    </p>
                )}
                {allowance.preview?.status === 'available' && (
                    <Button asChild className="min-h-11">
                        <a href={snapToTrack().url}>
                            {t('photo_plans.preview.try')}
                        </a>
                    </Button>
                )}
                {allowance.preview?.expires_at && (
                    <p className="text-sm text-muted-foreground">
                        {t('photo_plans.preview.ends', {
                            time: new Date(
                                allowance.preview.expires_at,
                            ).toLocaleString(),
                        })}
                    </p>
                )}
                {showOffer && offer && (
                    <div ref={offerRef} className="space-y-3">
                        <p>
                            {t('photo_plans.offer.body', { name: offer.name })}
                        </p>
                        <PhotoPlanLimitsDetails limits={offer.limits} />
                        <Button
                            asChild
                            className="h-auto min-h-11 text-center whitespace-normal"
                        >
                            <Link
                                href={checkout.start(offer.product_id).url}
                                data-umami-event="snap_to_track_upgrade_click"
                            >
                                {t('photo_plans.offer.cta', {
                                    name: offer.name,
                                    price: offer.formatted_price,
                                })}
                            </Link>
                        </Button>
                        <p className="text-xs text-muted-foreground">
                            {t('photo_plans.offer.terms')}
                        </p>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
