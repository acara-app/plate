import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import checkout from '@/routes/checkout';
import { Link } from '@inertiajs/react';

export interface PhotoAllowance {
    enabled: boolean;
    limit: number | null;
    used: number;
    resetsAt: string | null;
    mode: string;
    canUpgrade: boolean;
}

export function PhotoAllowanceCard({
    allowance,
}: {
    allowance?: PhotoAllowance;
}) {
    if (!allowance?.enabled || allowance.limit === null) return null;

    return (
        <Card>
            <CardHeader>
                <CardTitle>
                    {Math.max(0, allowance.limit - allowance.used)} of{' '}
                    {allowance.limit}{' '}
                    {allowance.mode === 'premium'
                        ? 'premium scans remaining'
                        : 'free scans remaining today'}
                </CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
                {allowance.resetsAt && (
                    <p className="text-sm text-muted-foreground">
                        Resets {new Date(allowance.resetsAt).toLocaleString()}.
                    </p>
                )}
                {allowance.canUpgrade && (
                    <Button asChild>
                        <Link
                            href={checkout.subscription().url}
                            data-umami-event="snap_to_track_upgrade_click"
                        >
                            100 premium scans per month — $9
                        </Link>
                    </Button>
                )}
            </CardContent>
        </Card>
    );
}
