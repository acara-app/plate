import { cn } from '@/lib/utils';
import { useTranslation } from 'react-i18next';

interface NutritionStatsProps {
    calories: number;
    protein: number | null;
    carbs: number | null;
    fat: number | null;
    className?: string;
    size?: 'sm' | 'md' | 'lg';
}

export function NutritionStats({
    calories,
    protein,
    carbs,
    fat,
    className,
    size = 'md',
}: NutritionStatsProps) {
    const { t } = useTranslation('common');
    const sizeClasses = {
        sm: 'text-xs',
        md: 'text-sm',
        lg: 'text-base',
    };

    const valueSizeClasses = {
        sm: 'text-sm',
        md: 'text-base',
        lg: 'text-lg',
    };

    return (
        <div
            className={cn(
                'grid grid-cols-4 gap-2 rounded-lg bg-muted/50 p-3',
                className,
            )}
        >
            <div className="flex flex-col items-center">
                <span
                    className={cn(
                        'font-semibold text-foreground',
                        valueSizeClasses[size],
                    )}
                >
                    {Math.round(calories)}
                </span>
                <span
                    className={cn('text-muted-foreground', sizeClasses[size])}
                >
                    🔥 {t('meal_plans.nutrition.calories')}
                </span>
            </div>

            <div className="flex flex-col items-center">
                <span
                    className={cn(
                        'font-semibold text-blue-600 dark:text-blue-400',
                        valueSizeClasses[size],
                    )}
                >
                    {protein ? Math.round(protein) : '-'}g
                </span>
                <span
                    className={cn('text-muted-foreground', sizeClasses[size])}
                >
                    💪 {t('meal_plans.nutrition.protein')}
                </span>
            </div>

            <div className="flex flex-col items-center">
                <span
                    className={cn(
                        'font-semibold text-green-600 dark:text-green-400',
                        valueSizeClasses[size],
                    )}
                >
                    {carbs ? Math.round(carbs) : '-'}g
                </span>
                <span
                    className={cn('text-muted-foreground', sizeClasses[size])}
                >
                    🌾 {t('meal_plans.nutrition.carbs')}
                </span>
            </div>

            <div className="flex flex-col items-center">
                <span
                    className={cn(
                        'font-semibold text-amber-600 dark:text-amber-400',
                        valueSizeClasses[size],
                    )}
                >
                    {fat ? Math.round(fat) : '-'}g
                </span>
                <span
                    className={cn('text-muted-foreground', sizeClasses[size])}
                >
                    🥑 {t('meal_plans.nutrition.fat')}
                </span>
            </div>
        </div>
    );
}
