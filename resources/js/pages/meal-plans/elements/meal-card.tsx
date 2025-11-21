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
import { TrustBadge } from '@/components/ui/trust-badge';
import { cn } from '@/lib/utils';
import { Meal, MealType } from '@/types/meal-plan';
import { Clock } from 'lucide-react';
import { MacroBar } from './macro-bar';
import { NutritionStats } from './nutrition-stats';

interface MealCardProps {
    meal: Meal;
    className?: string;
}

const mealTypeConfig: Record<
    MealType,
    { emoji: string; color: string; label: string }
> = {
    breakfast: {
        emoji: '🌅',
        color: 'bg-orange-100 text-orange-700 dark:bg-orange-950 dark:text-orange-300',
        label: 'Breakfast',
    },
    lunch: {
        emoji: '☀️',
        color: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-950 dark:text-yellow-300',
        label: 'Lunch',
    },
    dinner: {
        emoji: '🌙',
        color: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300',
        label: 'Dinner',
    },
    snack: {
        emoji: '🍎',
        color: 'bg-green-100 text-green-700 dark:bg-green-950 dark:text-green-300',
        label: 'Snack',
    },
};

export function MealCard({ meal, className }: MealCardProps) {
    const config = mealTypeConfig[meal.type];
    const hasDetails =
        meal.description || meal.preparation_instructions || meal.ingredients;

    return (
        <Card className={cn('overflow-hidden', className)}>
            <CardHeader className="pb-3">
                <div className="flex items-start justify-between gap-2">
                    <div className="flex-1 space-y-1">
                        <div className="flex items-center gap-2">
                            <Badge variant="outline" className={config.color}>
                                {config.emoji} {config.label}
                            </Badge>
                            {meal.preparation_time_minutes && (
                                <div className="flex items-center gap-1 text-xs text-muted-foreground">
                                    <Clock className="h-3 w-3" />
                                    {meal.preparation_time_minutes} min
                                </div>
                            )}
                        </div>
                        <CardTitle className="text-lg">{meal.name}</CardTitle>
                        {meal.portion_size && (
                            <CardDescription>
                                Portion: {meal.portion_size}
                            </CardDescription>
                        )}
                    </div>
                    <TrustBadge verification={meal.verification_metadata} />
                </div>
            </CardHeader>

            <CardContent className="space-y-3 pb-4">
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
                                variant="ghost"
                                className="w-full justify-start px-0 text-sm font-normal text-muted-foreground hover:text-foreground"
                            >
                                View recipe details →
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="max-h-[80vh] overflow-y-auto">
                            <DialogHeader>
                                <DialogTitle className="flex items-center gap-2">
                                    {config.emoji} {meal.name}
                                </DialogTitle>
                                <DialogDescription>
                                    {config.label}
                                    {meal.preparation_time_minutes &&
                                        ` • ${meal.preparation_time_minutes} minutes`}
                                </DialogDescription>
                            </DialogHeader>

                            <div className="space-y-4">
                                {meal.description && (
                                    <div>
                                        <h4 className="mb-2 font-semibold">
                                            Description
                                        </h4>
                                        <p className="text-sm text-muted-foreground">
                                            {meal.description}
                                        </p>
                                    </div>
                                )}

                                {meal.ingredients && (
                                    <div>
                                        <h4 className="mb-2 font-semibold">
                                            Ingredients
                                        </h4>
                                        <p className="text-sm whitespace-pre-line text-muted-foreground">
                                            {meal.ingredients}
                                        </p>
                                    </div>
                                )}

                                {meal.preparation_instructions && (
                                    <div>
                                        <h4 className="mb-2 font-semibold">
                                            Preparation Instructions
                                        </h4>
                                        <p className="text-sm whitespace-pre-line text-muted-foreground">
                                            {meal.preparation_instructions}
                                        </p>
                                    </div>
                                )}

                                <Separator />

                                <div>
                                    <h4 className="mb-3 font-semibold">
                                        Nutrition Information
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
