import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';
import { DailyStats, Meal } from '@/types/meal-plan';
import { useTranslation } from 'react-i18next';
import { MealCard } from './meal-card';
import { NutritionStats } from './nutrition-stats';

interface DayMealCardProps {
    dayNumber: number;
    dayName: string;
    meals: Meal[];
    dailyStats: DailyStats;
    targetCalories?: number | null;
    className?: string;
}

const dayEmojis: Record<string, string> = {
    Monday: '💼',
    Tuesday: '🚀',
    Wednesday: '⚡',
    Thursday: '🌟',
    Friday: '🎉',
    Saturday: '🌈',
    Sunday: '☀️',
};

export function DayMealCard({
    dayNumber,
    dayName,
    meals,
    dailyStats,
    targetCalories,
    className,
}: DayMealCardProps) {
    const { t } = useTranslation('common');
    const dayEmoji = dayEmojis[dayName] || '📅';
    const caloriesDiff = targetCalories
        ? dailyStats.total_calories - targetCalories
        : null;

    // Sort meals by meal type order (breakfast, lunch, dinner, snack)
    const mealTypeOrder: Record<string, number> = {
        breakfast: 0,
        lunch: 1,
        dinner: 2,
        snack: 3,
    };

    const sortedMeals = [...meals].sort(
        (a, b) => mealTypeOrder[a.type] - mealTypeOrder[b.type],
    );

    return (
        <Card className={cn('overflow-hidden', className)}>
            <CardHeader className="bg-muted/50 pb-4">
                <div className="flex items-center justify-between">
                    <div>
                        <CardTitle className="flex items-center gap-2 text-xl">
                            {dayEmoji} {dayName}
                        </CardTitle>
                        <CardDescription>
                            {t('meal_plans.day_card.day', {
                                number: dayNumber,
                            })}
                        </CardDescription>
                    </div>

                    {targetCalories && caloriesDiff !== null && (
                        <div className="text-right">
                            <div
                                className={cn(
                                    'text-sm font-medium',
                                    Math.abs(caloriesDiff) <= 50
                                        ? 'text-green-600 dark:text-green-400'
                                        : 'text-muted-foreground',
                                )}
                            >
                                {caloriesDiff > 0 ? '+' : ''}
                                {Math.round(caloriesDiff)} cal
                            </div>
                            <div className="text-xs text-muted-foreground">
                                {t('meal_plans.vs_target')}
                            </div>
                        </div>
                    )}
                </div>

                <Separator className="mt-4" />

                <NutritionStats
                    calories={dailyStats.total_calories}
                    protein={dailyStats.protein}
                    carbs={dailyStats.carbs}
                    fat={dailyStats.fat}
                    className="mt-3"
                />
            </CardHeader>

            <CardContent className="p-4">
                <div className="space-y-3">
                    {sortedMeals.map((meal) => (
                        <MealCard key={meal.id} meal={meal} />
                    ))}

                    {meals.length === 0 && (
                        <div className="py-8 text-center text-sm text-muted-foreground">
                            {t('meal_plans.day_card.no_meals')}
                        </div>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}
