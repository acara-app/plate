import { useTranslation } from 'react-i18next';

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';

interface UsageWidgetProps {
    title: string;
    currentAmount: number;
    limit: number;
    resetsIn: string;
    overLimit?: boolean;
}

export function UsageWidget({
    title,
    currentAmount,
    limit,
    resetsIn,
    overLimit = false,
}: UsageWidgetProps) {
    const { t } = useTranslation('common');

    const rawPercentage =
        limit > 0 ? Math.round((currentAmount / limit) * 100) : 0;
    const displayPercentage = Math.min(100, Math.max(0, rawPercentage));
    const isOverLimit = overLimit || (limit > 0 && currentAmount > limit);
    const progressColorClass = getProgressColorClass(
        displayPercentage,
        isOverLimit,
    );

    const usageLabel = t('billing.usage.credits_used', {
        current: currentAmount.toLocaleString(),
        limit: limit.toLocaleString(),
    });

    return (
        <Card>
            <CardHeader className="pb-2">
                <CardTitle className="text-sm">{title}</CardTitle>
                <CardDescription className="text-xs">
                    {usageLabel}
                    {isOverLimit && (
                        <span className="ml-1 font-medium text-red-600">
                            {t('billing.usage.over_limit')}
                        </span>
                    )}
                </CardDescription>
            </CardHeader>
            <CardContent className="gap-2">
                {isOverLimit ? (
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <Progress
                                value={displayPercentage}
                                className="h-2"
                                indicatorClassName={progressColorClass}
                            />
                        </TooltipTrigger>
                        <TooltipContent>
                            {t('billing.usage.over_limit_tooltip', {
                                current: currentAmount.toLocaleString(),
                                limit: limit.toLocaleString(),
                                percentage: rawPercentage,
                            })}
                        </TooltipContent>
                    </Tooltip>
                ) : (
                    <Progress
                        value={displayPercentage}
                        className="h-2"
                        indicatorClassName={progressColorClass}
                    />
                )}
                <div className="text-xs text-muted-foreground">
                    <span>
                        {displayPercentage}% &middot;{' '}
                        {t('billing.usage.resets_in', { time: resetsIn })}
                    </span>
                </div>
            </CardContent>
        </Card>
    );
}

function getProgressColorClass(
    percentage: number,
    isOverLimit: boolean,
): string {
    if (isOverLimit || percentage >= 90) {
        return 'bg-red-500';
    }
    if (percentage >= 70) {
        return 'bg-yellow-500';
    }
    return 'bg-green-500';
}
