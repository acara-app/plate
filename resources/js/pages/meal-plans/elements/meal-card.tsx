import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
import { getMealTypeConfig } from '@/lib/meal-types';
import { cn } from '@/lib/utils';
import { Meal } from '@/types/meal-plan';
import { BookOpen, ChefHat, ChevronRight, Clock } from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { MacroBar } from './macro-bar';
import { NutritionStats } from './nutrition-stats';

interface MealCardProps {
    meal: Meal;
    className?: string;
}

export function MealCard({ meal, className }: MealCardProps) {
    const { t } = useTranslation('common');
    const config = getMealTypeConfig(meal.type);
    const MealTypeIcon = config.Icon;
    const hasDetails =
        meal.description ||
        meal.preparation_instructions ||
        (meal.ingredients && meal.ingredients.length > 0);

    return (
        <Card
            className={cn(
                'h-full overflow-hidden transition-colors hover:border-primary/40',
                className,
            )}
        >
            <CardHeader className="pb-3">
                <div className="space-y-3">
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge
                            variant="outline"
                            className={cn(
                                'min-h-7 gap-1.5 border-transparent',
                                config.color,
                            )}
                        >
                            <MealTypeIcon
                                className={cn('h-3.5 w-3.5', config.iconColor)}
                            />
                            {t(config.labelKey)}
                        </Badge>
                        {meal.preparation_time_minutes && (
                            <div className="flex min-h-7 items-center gap-1.5 rounded-md border px-2 text-xs text-muted-foreground">
                                <Clock className="h-3.5 w-3.5" />
                                {meal.preparation_time_minutes}{' '}
                                {t('meal_plans.meal_card.min')}
                            </div>
                        )}
                    </div>

                    <div className="space-y-1">
                        <CardTitle className="text-lg leading-snug">
                            {meal.name}
                        </CardTitle>
                        {meal.portion_size && (
                            <CardDescription>
                                {t('meal_plans.meal_card.portion', {
                                    size: meal.portion_size,
                                })}
                            </CardDescription>
                        )}
                    </div>
                </div>
            </CardHeader>

            <CardContent className="flex flex-1 flex-col gap-4 pb-4">
                <NutritionStats
                    calories={meal.calories}
                    protein={meal.protein_grams}
                    carbs={meal.carbs_grams}
                    fat={meal.fat_grams}
                    size="sm"
                />

                {meal.protein_grams && meal.carbs_grams && meal.fat_grams && (
                    <MacroBar macros={meal.macro_percentages} />
                )}

                {hasDetails && (
                    <Dialog>
                        <DialogTrigger asChild>
                            <Button
                                variant="outline"
                                className="mt-auto min-h-11 w-full justify-between text-sm font-medium"
                            >
                                <span className="flex items-center gap-2">
                                    <BookOpen className="h-4 w-4 text-primary" />
                                    {t('meal_plans.meal_card.view_recipe')}
                                </span>
                                <ChevronRight className="h-4 w-4" />
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="max-h-[80vh] overflow-y-auto sm:max-w-2xl">
                            <DialogHeader>
                                <DialogTitle className="flex items-center gap-2">
                                    <span
                                        className={cn(
                                            'flex h-9 w-9 items-center justify-center rounded-lg',
                                            config.color,
                                        )}
                                    >
                                        <MealTypeIcon
                                            className={cn(
                                                'h-4 w-4',
                                                config.iconColor,
                                            )}
                                        />
                                    </span>
                                    {meal.name}
                                </DialogTitle>
                                <DialogDescription>
                                    {t(config.labelKey)}
                                    {meal.preparation_time_minutes &&
                                        ` • ${meal.preparation_time_minutes} ${t('meal_plans.meal_card.min')}`}
                                </DialogDescription>
                            </DialogHeader>

                            <div className="space-y-5">
                                {meal.description && (
                                    <div>
                                        <h4 className="mb-2 flex items-center gap-2 font-semibold">
                                            <ChefHat className="h-4 w-4 text-primary" />
                                            {t(
                                                'meal_plans.meal_card.description',
                                            )}
                                        </h4>
                                        <p className="text-sm leading-relaxed text-muted-foreground">
                                            {meal.description}
                                        </p>
                                    </div>
                                )}

                                {meal.ingredients &&
                                    meal.ingredients.length > 0 && (
                                        <div>
                                            <h4 className="mb-2 font-semibold">
                                                {t(
                                                    'meal_plans.meal_card.ingredients',
                                                )}
                                            </h4>
                                            <ul className="space-y-2 text-sm text-muted-foreground">
                                                {meal.ingredients.map(
                                                    (ingredient, index) => (
                                                        <li
                                                            key={index}
                                                            className="flex gap-2"
                                                        >
                                                            <span
                                                                className="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-primary"
                                                                aria-hidden="true"
                                                            />
                                                            <span>
                                                                {
                                                                    ingredient.quantity
                                                                }{' '}
                                                                {
                                                                    ingredient.name
                                                                }
                                                                {ingredient.specificity && (
                                                                    <span className="text-xs">
                                                                        {' '}
                                                                        (
                                                                        {
                                                                            ingredient.specificity
                                                                        }
                                                                        )
                                                                    </span>
                                                                )}
                                                            </span>
                                                        </li>
                                                    ),
                                                )}
                                            </ul>
                                        </div>
                                    )}

                                {meal.preparation_instructions && (
                                    <div>
                                        <h4 className="mb-2 font-semibold">
                                            {t(
                                                'meal_plans.meal_card.preparation_instructions',
                                            )}
                                        </h4>
                                        <p className="text-sm leading-relaxed whitespace-pre-line text-muted-foreground">
                                            {meal.preparation_instructions}
                                        </p>
                                    </div>
                                )}

                                <Separator />

                                <div>
                                    <h4 className="mb-3 font-semibold">
                                        {t(
                                            'meal_plans.meal_card.nutrition_information',
                                        )}
                                    </h4>
                                    <NutritionStats
                                        calories={meal.calories}
                                        protein={meal.protein_grams}
                                        carbs={meal.carbs_grams}
                                        fat={meal.fat_grams}
                                        size="lg"
                                    />
                                    {meal.protein_grams &&
                                        meal.carbs_grams &&
                                        meal.fat_grams && (
                                            <MacroBar
                                                macros={meal.macro_percentages}
                                                showLegend
                                                className="mt-3"
                                            />
                                        )}
                                </div>
                            </div>
                        </DialogContent>
                    </Dialog>
                )}
            </CardContent>
        </Card>
    );
}
