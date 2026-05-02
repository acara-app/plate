import { Link } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/button';
import checkout from '@/routes/checkout';

interface PaymentPendingBannerProps {
    className?: string;
    recoveryUrl?: string | null;
}

export function PaymentPendingBanner({
    className,
    recoveryUrl,
}: PaymentPendingBannerProps) {
    const { t } = useTranslation('common');

    return (
        <div
            data-testid="payment-pending-banner"
            role="status"
            aria-live="polite"
            className={
                'flex w-full items-start gap-3 rounded-lg border border-yellow-300 bg-yellow-50 p-4 dark:border-yellow-900/50 dark:bg-yellow-950/40 ' +
                (className ?? '')
            }
        >
            <Loader2 className="mt-0.5 size-5 shrink-0 animate-spin text-yellow-700 dark:text-yellow-400" />
            <div className="flex-1 space-y-1">
                <p className="text-sm font-medium text-yellow-900 dark:text-yellow-100">
                    {t('billing.lifecycle.payment_pending_heading')}
                </p>
                <p className="text-xs text-yellow-800 dark:text-yellow-200">
                    {t('billing.lifecycle.payment_pending_body')}
                </p>
            </div>
            <Button
                asChild
                variant="outline"
                size="sm"
                className="border-yellow-600 text-yellow-900 hover:bg-yellow-100 dark:border-yellow-500/50 dark:text-yellow-100 dark:hover:bg-yellow-900/40"
            >
                {recoveryUrl ? (
                    <a
                        href={recoveryUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        {t('billing.lifecycle.payment_pending_action')}
                    </a>
                ) : (
                    <Link href={checkout.subscription().url}>
                        {t('billing.lifecycle.payment_pending_action')}
                    </Link>
                )}
            </Button>
        </div>
    );
}
