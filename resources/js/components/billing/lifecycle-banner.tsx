import { GracePeriodBanner } from '@/components/billing/grace-period-banner';
import { PaymentPendingBanner } from '@/components/billing/payment-pending-banner';
import useSharedProps from '@/hooks/use-shared-props';

interface LifecycleBannerProps {
    className?: string;
}

export function LifecycleBanner({ className }: LifecycleBannerProps) {
    const { entitlement } = useSharedProps();

    if (entitlement === null || !entitlement.premium_enforcement_active) {
        return null;
    }

    if (entitlement.payment_pending) {
        return (
            <PaymentPendingBanner
                className={className}
                recoveryUrl={entitlement.payment_recovery_url}
            />
        );
    }

    if (entitlement.on_grace_period) {
        return (
            <GracePeriodBanner
                entitlement={entitlement}
                className={className}
            />
        );
    }

    return null;
}
