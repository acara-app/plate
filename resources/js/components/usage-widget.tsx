import { useTranslation } from 'react-i18next';

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';

interface UsageWidgetProps {
    title: string;
    currentAmount: number;
    limit: number;
    resetsIn: string;
}

export function UsageWidget({
    title,
    currentAmount,
    limit,
    resetsIn,
}: UsageWidgetProps) {
    const { t } = useTranslation('common');

    const percentage =
        limit > 0 ? Math.round((currentAmount / limit) * 100) : 0;

    const progressColorClass = getProgressColorClass(percentage);

    return (
        <Card>
            <CardHeader className="pb-2">
                <CardTitle className="text-sm">{title}</CardTitle>
                <CardDescription className="text-xs">
                    {t('billing.usage.credits_used', {
                        current: currentAmount.toLocaleString(),
                        limit: limit.toLocaleString(),
                    })}
                </CardDescription>
            </CardHeader>
            <CardContent className="gap-2">
                <Progress
                    value={percentage}
                    className="h-2"
                    indicatorClassName={progressColorClass}
                />
                <div className="text-xs text-muted-foreground">
                    <span>
                        {percentage}% &middot;{' '}
                        {t('billing.usage.resets_in', { time: resetsIn })}
                    </span>
                </div>
            </CardContent>
        </Card>
    );
}

function getProgressColorClass(percentage: number): string {
    if (percentage >= 90) {
        return 'bg-red-500';
    }
    if (percentage >= 70) {
        return 'bg-yellow-500';
    }
    return 'bg-green-500';
}
