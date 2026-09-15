import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import checkout from '@/routes/checkout';
import { Link } from '@inertiajs/react';
import { useEffect } from 'react';

export interface PhotoAllowance {
    enabled: boolean;
    limit: number | null;
    used: number;
    resetsAt: string | null;
    mode: string;
    canUpgrade: boolean;
    offer?: {
        productId: number;
        name: string;
        formattedPrice: string;
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
        if (allowance?.enabled && allowance.canUpgrade)
            window.umami?.track('snap_to_track_offer_viewed', {
                mode: allowance.mode,
                exhausted:
                    allowance.limit !== null &&
                    allowance.used >= allowance.limit,
            });
    }, [
        allowance?.enabled,
        allowance?.canUpgrade,
        allowance?.mode,
        allowance?.used,
        allowance?.limit,
    ]);
    if (!allowance?.enabled || allowance.limit === null) return null;

    const remaining = Math.max(0, allowance.limit - allowance.used);
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
                {allowance.resetsAt && (
                    <p className="text-sm text-muted-foreground">
                        Resets {new Date(allowance.resetsAt).toLocaleString()}.
                    </p>
                )}
                {showUpgrade && allowance.canUpgrade && offer && (
                    <>
                        <p>
                            {offer.scans} premium scans per billing month. No
                            rollover or overage charges.
                        </p>
                        <Button asChild>
                            <Link
                                href={checkout.start(offer.productId).url}
                                data-umami-event="snap_to_track_upgrade_click"
                            >
                                Continue with {offer.name} —{' '}
                                {offer.formattedPrice}/month
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
