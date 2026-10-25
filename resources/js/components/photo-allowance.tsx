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
                            ? `${remaining} free ${remaining === 1 ? 'scan' : 'scans'} remaining`
                            : 'Your free scans are complete'
                        : `${remaining} of ${allowance.limit} scans remaining`}
                </CardTitle>
            </CardHeader>
            <CardContent
                className={cn('space-y-3', variant === 'plain' && 'px-0')}
            >
                {trial && (
                    <p className="text-sm text-muted-foreground">
                        {allowance.limit} successful scans, with no daily reset.
                        Saved meals stay in your food log.
                    </p>
                )}
                {allowance.resets_at && (
                    <p className="text-sm text-muted-foreground">
                        Resets {new Date(allowance.resets_at).toLocaleString()}.
                    </p>
                )}
                {allowance.preview?.status === 'available' && (
                    <Button asChild className="min-h-11">
                        <a href={snapToTrack().url}>
                            Try one scan a day for three days
                        </a>
                    </Button>
                )}
                {allowance.preview?.expires_at && (
                    <p className="text-sm text-muted-foreground">
                        Preview ends{' '}
                        {new Date(
                            allowance.preview.expires_at,
                        ).toLocaleString()}
                        . No automatic subscription.
                    </p>
                )}
                {showOffer && offer && (
                    <div ref={offerRef} className="space-y-3">
                        <p>
                            Track meals from photos and save them to your food
                            log.
                        </p>
                        <Button asChild>
                            <Link
                                href={checkout.start(offer.product_id).url}
                                data-umami-event="snap_to_track_upgrade_click"
                            >
                                Continue scanning with {offer.name} —{' '}
                                {offer.formatted_price}/month
                            </Link>
                        </Button>
                        <p className="text-xs text-muted-foreground">
                            Daily and weekly AI limits apply. Renews monthly.
                            Cancel anytime from billing settings.
                        </p>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
