import { Link } from '@inertiajs/react';
import { Sparkles, X } from 'lucide-react';
import { useTranslation } from 'react-i18next';

import { Progress } from '@/components/ui/progress';
import checkout from '@/routes/checkout';
import type { AiAllowance } from '@/types';

function useResetLabel(allowance: AiAllowance): string {
    const { t, i18n } = useTranslation('common');

    if (allowance.resets === 'daily') {
        return t('billing.allowance.resets_midnight');
    }

    return t('billing.allowance.resets_on', {
        date: new Date(allowance.resets_at).toLocaleDateString(i18n.language, {
            month: 'long',
            day: 'numeric',
        }),
    });
}

export function AiAllowanceBar({ allowance }: { allowance: AiAllowance }) {
    const { t } = useTranslation('common');
    const resetLabel = useResetLabel(allowance);

    return (
        <div className="space-y-3 rounded-lg border p-4">
            <div className="flex items-baseline justify-between gap-3">
                <h3 className="text-sm font-medium">
                    {t(`billing.allowance.${allowance.resets}_label`)}
                </h3>
                <span className="text-sm text-muted-foreground">
                    {t('billing.allowance.used', {
                        percentage: allowance.percentage,
                    })}
                </span>
            </div>
            <Progress
                value={allowance.percentage}
                className="h-2"
                indicatorClassName={
                    allowance.percentage >= 90 ? 'bg-red-500' : 'bg-primary'
                }
            />
            <p className="text-xs text-muted-foreground">
                {t('billing.allowance.explainer')} {resetLabel}.
            </p>
        </div>
    );
}

export function AiAllowanceNotice({
    allowance,
    onDismiss,
}: {
    allowance: AiAllowance;
    onDismiss?: () => void;
}) {
    const { t } = useTranslation('common');
    const resetLabel = useResetLabel(allowance);
    const warning = allowance.tier !== 'free' && allowance.percentage >= 80;

    if (!allowance.exhausted && !warning) {
        return null;
    }

    const heading = allowance.exhausted
        ? t(`billing.allowance.exhausted_${allowance.resets}`)
        : t('billing.allowance.warning', {
              percentage: allowance.percentage,
          });

    return (
        <div
            role="status"
            aria-live="polite"
            className="flex w-full items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-950/40"
        >
            <Sparkles className="mt-0.5 size-5 shrink-0 text-amber-600 dark:text-amber-400" />
            <div className="flex-1 space-y-1 text-sm text-amber-900 dark:text-amber-100">
                <p className="font-medium">{heading}</p>
                <p className="text-xs text-amber-800 dark:text-amber-200">
                    {resetLabel}.
                </p>
                {allowance.can_upgrade && (
                    <Link
                        href={checkout.subscription().url}
                        className="inline-block text-xs font-medium underline"
                    >
                        {t('billing.allowance.see_plans')}
                    </Link>
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
