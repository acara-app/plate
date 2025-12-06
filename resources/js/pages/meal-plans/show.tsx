import { OnboardingBanner } from '@/components/onboarding-banner';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { Skeleton } from '@/components/ui/skeleton';
import useSharedProps from '@/hooks/use-shared-props';
import AppLayout from '@/layouts/app-layout';
import { MealCard } from '@/pages/meal-plans/elements/meal-card';
import { NutritionStats } from '@/pages/meal-plans/elements/nutrition-stats';
import checkout from '@/routes/checkout';
import mealPlans from '@/routes/meal-plans';
import { type BreadcrumbItem } from '@/types';
import {
    CurrentDay,
    GenerationStatus,
    MealPlan,
    MealPlanGenerationStatus,
    Navigation,
} from '@/types/meal-plan';
import { Head, Link, usePoll } from '@inertiajs/react';
import {
    Calendar,
    ChevronLeft,
    ChevronRight,
    CrownIcon,
    Info,
    Loader2,
    Printer,
    Sparkles,
} from 'lucide-react';

interface MealPlansProps {
    mealPlan: MealPlan | null;
    currentDay: CurrentDay | null;
    navigation: Navigation | null;
    requiresSubscription?: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Meal Plans',
        href: mealPlans.index().url,
    },
];

const dayEmojis: Record<string, string> = {
    Monday: '💼',
    Tuesday: '🚀',
    Wednesday: '⚡',
    Thursday: '🌟',
    Friday: '🎉',
    Saturday: '🌈',
    Sunday: '☀️',
};

export default function MealPlans({
    mealPlan,
    currentDay,
    navigation,
    requiresSubscription = false,
}: MealPlansProps) {
    const { currentUser } = useSharedProps();

    usePoll(
        2000,
        { only: ['currentDay'] },
        {
            autoStart:
                currentDay?.needs_generation &&
                currentDay?.status === GenerationStatus.Generating,
        },
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Meal Plans" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6">
                {!currentUser?.is_onboarded ? (
                    <OnboardingBanner />
                ) : requiresSubscription ? (
                    <>
                        <div className="space-y-2">
                            <h1 className="flex items-center gap-2 text-3xl font-bold tracking-tight">
                                <Calendar className="h-8 w-8 text-primary" />
                                Your Meal Plans
                            </h1>
                            <p className="text-muted-foreground">
                                View and manage your personalized nutrition
                                plans
                            </p>
                        </div>

                        <Alert className="border-purple-300 bg-purple-50 dark:border-purple-700 dark:bg-purple-950/50">
                            <CrownIcon className="h-5 w-5 text-purple-600 dark:text-purple-400" />
                            <AlertTitle className="text-lg text-purple-900 dark:text-purple-100">
                                Unlock Personalized Meal Plans
                            </AlertTitle>
                            <AlertDescription className="space-y-3 text-purple-800 dark:text-purple-200">
                                <p>
                                    Subscribe to unlock AI-powered meal plans
                                    tailored to your dietary needs, health
                                    goals, and lifestyle. Get weekly meal plans
                                    with detailed recipes, nutrition
                                    information, and shopping lists.
                                </p>
                                <Button asChild size="sm">
                                    <Link href={checkout.subscription().url}>
                                        <CrownIcon className="mr-2 h-4 w-4" />
                                        Upgrade Now
                                    </Link>
                                </Button>
                            </AlertDescription>
                        </Alert>
                    </>
                ) : !mealPlan ? (
                    <>
                        <div className="space-y-2">
                            <h1 className="flex items-center gap-2 text-3xl font-bold tracking-tight">
                                <Calendar className="h-8 w-8 text-primary" />
                                Your Meal Plans
                            </h1>
                            <p className="text-muted-foreground">
                                View and manage your personalized nutrition
                                plans
                            </p>
                        </div>

                        <Alert>
                            <Info className="h-4 w-4" />
                            <AlertDescription>
                                You don't have any meal plans yet. Complete your
                                profile and preferences to generate your first
                                personalized meal plan!
                            </AlertDescription>
                        </Alert>
                    </>
                ) : (
                    mealPlan &&
                    currentDay &&
                    navigation && (
                        <>
                            {/* Header with Navigation */}
                            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div className="space-y-2">
                                    <div className="flex items-center gap-2">
                                        <Badge
                                            variant="default"
                                            className="capitalize"
                                        >
                                            📅 {mealPlan.type}
                                        </Badge>
                                        <Badge variant="outline">
                                            {mealPlan.duration_days} days
                                        </Badge>
                                    </div>
                                    <h1 className="text-3xl font-bold tracking-tight">
                                        {mealPlan.name || 'Meal Plan'}
                                    </h1>
                                    {mealPlan.description && (
                                        <p className="text-muted-foreground">
                                            {mealPlan.description}
                                        </p>
                                    )}
                                </div>

                                {/* Actions */}
                                <div className="flex items-center gap-2">
                                    <Button variant="outline" size="sm" asChild>
                                        <a
                                            href={
                                                mealPlans.print(mealPlan.id).url
                                            }
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            <Printer className="mr-2 h-4 w-4" />
                                            Print
                                        </a>
                                    </Button>
                                </div>
                            </div>

                            {/* Day Navigation */}
                            <div className="flex items-center justify-center gap-2">
                                <Button variant="outline" size="icon" asChild>
                                    <Link
                                        href={
                                            mealPlans.index({
                                                query: {
                                                    day: navigation.previous_day,
                                                },
                                            }).url
                                        }
                                        preserveScroll
                                    >
                                        <ChevronLeft className="h-4 w-4" />
                                    </Link>
                                </Button>

                                <div className="min-w-[120px] text-center">
                                    <div className="text-xs text-muted-foreground">
                                        Day {currentDay.day_number} of{' '}
                                        {navigation.total_days}
                                    </div>
                                </div>

                                <Button variant="outline" size="icon" asChild>
                                    <Link
                                        href={
                                            mealPlans.index({
                                                query: {
                                                    day: navigation.next_day,
                                                },
                                            }).url
                                        }
                                        preserveScroll
                                    >
                                        <ChevronRight className="h-4 w-4" />
                                    </Link>
                                </Button>
                            </div>

                            <Separator />

                            {/* Current Day Header */}
                            <div className="space-y-4">
                                <div className="flex items-center justify-between">
                                    <h2 className="flex items-center gap-2 text-2xl font-semibold">
                                        {dayEmojis[currentDay.day_name] || '📅'}{' '}
                                        {currentDay.day_name}
                                    </h2>

                                    {mealPlan.target_daily_calories && (
                                        <CalorieComparison
                                            actual={
                                                currentDay.daily_stats
                                                    .total_calories
                                            }
                                            target={
                                                mealPlan.target_daily_calories
                                            }
                                        />
                                    )}
                                </div>

                                {/* Daily Nutrition Stats */}
                                <NutritionStats
                                    calories={
                                        currentDay.daily_stats.total_calories
                                    }
                                    protein={currentDay.daily_stats.protein}
                                    carbs={currentDay.daily_stats.carbs}
                                    fat={currentDay.daily_stats.fat}
                                    size="lg"
                                />
                            </div>

                            {/* Preparation Notes */}
                            {mealPlan.metadata?.preparation_notes && (
                                <Alert>
                                    <Info className="h-4 w-4" />
                                    <AlertDescription>
                                        <strong className="font-semibold">
                                            Preparation Tips:
                                        </strong>{' '}
                                        {mealPlan.metadata.preparation_notes}
                                    </AlertDescription>
                                </Alert>
                            )}

                            {/* Meals for Current Day */}
                            <div className="space-y-3">
                                <h3 className="flex items-center gap-2 text-lg font-semibold">
                                    <Sparkles className="h-5 w-5 text-primary" />
                                    Today's Meals
                                </h3>

                                {currentDay.needs_generation ? (
                                    <GeneratingMealsState
                                        status={currentDay.status}
                                        dayNumber={currentDay.day_number}
                                    />
                                ) : currentDay.meals.length === 0 ? (
                                    <Alert>
                                        <Info className="h-4 w-4" />
                                        <AlertDescription>
                                            No meals planned for this day.
                                        </AlertDescription>
                                    </Alert>
                                ) : (
                                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                        {currentDay.meals.map((meal) => (
                                            <MealCard
                                                key={meal.id}
                                                meal={meal}
                                            />
                                        ))}
                                    </div>
                                )}
                            </div>

                            {/* Plan Info Footer */}
                            <div className="mt-8 rounded-lg bg-muted/30 p-4 text-sm text-muted-foreground">
                                <p>
                                    Created on{' '}
                                    {new Date(
                                        mealPlan.created_at,
                                    ).toLocaleDateString('en-US', {
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric',
                                    })}
                                </p>
                            </div>
                        </>
                    )
                )}
            </div>
        </AppLayout>
    );
}

interface CalorieComparisonProps {
    actual: number;
    target: number;
}

function CalorieComparison({ actual, target }: CalorieComparisonProps) {
    const diff = actual - target;
    const percentage = ((diff / target) * 100).toFixed(0);
    const isWithinRange = Math.abs(diff) <= 50;

    return (
        <div className="text-right">
            <div
                className={
                    isWithinRange
                        ? 'text-lg font-semibold text-green-600 dark:text-green-400'
                        : 'text-lg font-semibold text-muted-foreground'
                }
            >
                {diff > 0 ? '+' : ''}
                {Math.round(diff)} cal
            </div>
            <div className="text-xs text-muted-foreground">
                {diff > 0 ? '+' : ''}
                {percentage}% vs target
            </div>
        </div>
    );
}

interface GeneratingMealsStateProps {
    status: MealPlanGenerationStatus;
    dayNumber: number;
}

function GeneratingMealsState({
    status,
    dayNumber,
}: GeneratingMealsStateProps) {
    if (status === GenerationStatus.Failed) {
        return (
            <Alert variant="destructive">
                <Info className="h-4 w-4" />
                <AlertTitle>Generation Failed</AlertTitle>
                <AlertDescription className="space-y-3">
                    <p>
                        We couldn't generate meals for this day. This might be a
                        temporary issue.
                    </p>
                    <Button variant="outline" size="sm" asChild>
                        <Link
                            href={
                                mealPlans.index({ query: { day: dayNumber } })
                                    .url
                            }
                        >
                            Try Again
                        </Link>
                    </Button>
                </AlertDescription>
            </Alert>
        );
    }

    return (
        <div className="space-y-4">
            <Alert className="border-primary/30 bg-primary/5">
                <Loader2 className="h-4 w-4 animate-spin text-primary" />
                <AlertTitle className="text-primary">
                    Generating Your Meals
                </AlertTitle>
                <AlertDescription className="text-muted-foreground">
                    Our AI is crafting personalized meals for this day based on
                    your preferences and nutritional goals. This usually takes
                    30-60 seconds.
                </AlertDescription>
            </Alert>

            {/* Skeleton cards */}
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                {[1, 2, 3, 4].map((i) => (
                    <MealCardSkeleton key={i} />
                ))}
            </div>
        </div>
    );
}

function MealCardSkeleton() {
    return (
        <div className="rounded-lg border bg-card p-4 shadow-sm">
            <div className="space-y-3">
                <div className="flex items-center justify-between">
                    <Skeleton className="h-5 w-20" />
                    <Skeleton className="h-4 w-16" />
                </div>
                <Skeleton className="h-6 w-3/4" />
                <Skeleton className="h-4 w-full" />
                <Skeleton className="h-4 w-2/3" />
                <div className="flex gap-2 pt-2">
                    <Skeleton className="h-8 w-16" />
                    <Skeleton className="h-8 w-16" />
                    <Skeleton className="h-8 w-16" />
                </div>
            </div>
        </div>
    );
}
