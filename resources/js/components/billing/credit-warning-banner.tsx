import { AlertTriangle, X } from 'lucide-react';
import { useTranslation } from 'react-i18next';

import { Button } from '@/components/ui/button';
import type { CreditWarning } from '@/types';

interface CreditWarningBannerProps {
    warning: CreditWarning;
    onDismiss?: () => void;
    onUpgradeClick?: () => void;
}

export function CreditWarningBanner({
    warning,
    onDismiss,
    onUpgradeClick,
}: CreditWarningBannerProps) {
    const { t } = useTranslation('common');

    const limitLabel = t(`billing.usage.${warning.limit_type}`);

    return (
        <div
            role="status"
            aria-live="polite"
            className="flex w-full items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-950/40"
        >
            <AlertTriangle className="mt-0.5 size-5 shrink-0 text-amber-600 dark:text-amber-400" />
            <div className="flex-1 space-y-2">
                <p className="text-sm font-medium text-amber-900 dark:text-amber-100">
                    {t('billing.warning.heading', {
                        percentage: warning.percentage,
                        limit: limitLabel,
                    })}
                </p>
                <p className="text-xs text-amber-800 dark:text-amber-200">
                    {t('billing.warning.body', {
                        current: warning.current_credits.toLocaleString(),
                        total: warning.limit_credits.toLocaleString(),
                        time: warning.resets_in,
                    })}
                </p>
                {onUpgradeClick && warning.tier !== 'plus' && (
                    <Button
                        type="button"
                        variant="link"
                        size="sm"
                        onClick={onUpgradeClick}
                        className="h-auto p-0 text-amber-900 underline dark:text-amber-100"
                    >
                        {t('billing.warning.see_upgrade_options')}
                    </Button>
                )}
            </div>
            {onDismiss && (
                <button
                    type="button"
                    onClick={onDismiss}
                    aria-label={t('close')}
                    className="text-amber-700 hover:text-amber-900 dark:text-amber-300 dark:hover:text-amber-100"
                >
                    <X className="size-4" />
                </button>
            )}
        </div>
    );
}
