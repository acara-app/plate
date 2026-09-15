import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import checkout from '@/routes/checkout';
import { Link } from '@inertiajs/react';
import { useEffect } from 'react';

export interface PhotoAllowance {
    enabled: boolean;
    limit: number | null;
    used: number;
    remaining: number | null;
    exhausted: boolean;
    resets_at: string | null;
    mode: string;
    can_upgrade: boolean;
    offer?: {
        product_id: number;
        name: string;
        formatted_price: string;
        scans: number;
    } | null;
}

export function PhotoAllowanceCard({
    allowance,
    showUpgrade = true,
}: {
    allowance?: PhotoAllowance;
    showUpgrade?: boolean;
}) {
    useEffect(() => {
        if (allowance?.enabled && allowance.can_upgrade)
            window.umami?.track('snap_to_track_offer_viewed', {
                mode: allowance.mode,
                exhausted: allowance.exhausted,
            });
    }, [
        allowance?.enabled,
        allowance?.can_upgrade,
        allowance?.mode,
        allowance?.exhausted,
    ]);
    if (!allowance?.enabled || allowance.limit === null) return null;

    const remaining = allowance.remaining ?? 0;
    const trial = allowance.mode === 'trial';
    const offer = allowance.offer;

    return (
        <Card>
            <CardHeader>
                <CardTitle>
                    {trial
                        ? remaining > 0
                            ? 'Try one photo free'
                            : 'Your free trial scan is complete'
                        : `${remaining} of ${allowance.limit} premium scans remaining`}
                </CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
                {trial && (
                    <p className="text-sm text-muted-foreground">
                        One successful scan, with no daily reset. Your result
                        stays available after the trial.
                    </p>
                )}
                {allowance.resets_at && (
                    <p className="text-sm text-muted-foreground">
                        Resets {new Date(allowance.resets_at).toLocaleString()}.
                    </p>
                )}
                {showUpgrade && allowance.can_upgrade && offer && (
                    <>
                        <p>
                            {offer.scans} premium scans per billing month. No
                            rollover or overage charges.
                        </p>
                        <Button asChild>
                            <Link
                                href={checkout.start(offer.product_id).url}
                                data-umami-event="snap_to_track_upgrade_click"
                            >
                                Continue with {offer.name} —{' '}
                                {offer.formatted_price}/month
                            </Link>
                        </Button>
                        <p className="text-xs text-muted-foreground">
                            Renews monthly. Cancel anytime from billing
                            settings.
                        </p>
                    </>
                )}
            </CardContent>
        </Card>
    );
}
