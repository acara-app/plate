import { show as showGroceryList } from '@/actions/App/Http/Controllers/GroceryListController';
import { OnboardingBanner } from '@/components/onboarding-banner';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { Skeleton } from '@/components/ui/skeleton';
import useSharedProps from '@/hooks/use-shared-props';
import AppLayout from '@/layouts/app-layout';
import { MealCard } from '@/pages/meal-plans/elements/meal-card';
import { NutritionStats } from '@/pages/meal-plans/elements/nutrition-stats';
import mealPlans from '@/routes/meal-plans';
import { type BreadcrumbItem } from '@/types';
import {
    CurrentDay,
    GenerationStatus,
    MealPlan,
    MealPlanGenerationStatus,
    Navigation,
} from '@/types/meal-plan';
import { Head, Link, useForm, usePoll } from '@inertiajs/react';
import {
    Calendar,
    ChevronLeft,
    ChevronRight,
    Info,
    Loader2,
    Printer,
    RefreshCw,
    ShoppingCart,
    Sparkles,
} from 'lucide-react';
import { useTranslation } from 'react-i18next';

interface MealPlansProps {
    mealPlan: MealPlan | null;
    currentDay: CurrentDay | null;
    navigation: Navigation | null;
}

const getBreadcrumbs = (t: (key: string) => string): BreadcrumbItem[] => [
    {
        title: t('meal_plans.title'),
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
}: MealPlansProps) {
    const { currentUser } = useSharedProps();
    const { t } = useTranslation('common');

    const { start: startPolling } = usePoll(
        2000,
        { only: ['currentDay'] },
        {
            autoStart:
                currentDay?.needs_generation &&
                currentDay?.status === GenerationStatus.Generating,
        },
    );

    return (
        <AppLayout breadcrumbs={getBreadcrumbs(t)}>
            <Head title={t('meal_plans.title')} />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6">
                {!currentUser?.is_onboarded ? (
                    <OnboardingBanner />
                ) : !mealPlan ? (
                    <>
                        <div className="space-y-2">
                            <h1 className="flex items-center gap-2 text-3xl font-bold tracking-tight">
                                <Calendar className="h-8 w-8 text-primary" />
                                {t('meal_plans.your_meal_plans')}
                            </h1>
                            <p className="text-muted-foreground">
                                {t('meal_plans.description')}
                            </p>
                        </div>

                        <Alert>
                            <Info className="h-4 w-4" />
                            <AlertDescription>
                                {t('meal_plans.no_plans')}
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
                                            {mealPlan.duration_days}{' '}
                                            {t('meal_plans.days')}
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
                                        <Link
                                            href={
                                                showGroceryList(mealPlan.id).url
                                            }
                                        >
                                            <ShoppingCart className="mr-2 h-4 w-4" />
                                            {t('meal_plans.grocery_list')}
                                        </Link>
                                    </Button>
                                    <Button variant="outline" size="sm" asChild>
                                        <a
                                            href={
                                                mealPlans.print(mealPlan.id).url
                                            }
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            <Printer className="mr-2 h-4 w-4" />
                                            {t('meal_plans.print')}
                                        </a>
                                    </Button>
                                </div>
                            </div>

                            {/* Day Navigation */}
                            <DayPagination
                                currentDay={currentDay.day_number}
                                totalDays={navigation.total_days}
                            />

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
                                    {t('meal_plans.todays_meals')}
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
                                            {t('meal_plans.no_meals')}
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
                            <PlanInfoFooter
                                mealPlan={mealPlan}
                                currentDay={currentDay}
                                onRegenerateStart={startPolling}
                            />
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
    const { t } = useTranslation('common');

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
                {Math.round(diff)} {t('meal_plans.cal')}
            </div>
            <div className="text-xs text-muted-foreground">
                {diff > 0 ? '+' : ''}
                {percentage}% {t('meal_plans.vs_target')}
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
    const { t } = useTranslation('common');
    if (status === GenerationStatus.Failed) {
        return (
            <Alert variant="destructive">
                <Info className="h-4 w-4" />
                <AlertTitle>
                    {t('meal_plans.generation.failed_title')}
                </AlertTitle>
                <AlertDescription className="space-y-3">
                    <p>{t('meal_plans.generation.failed_description')}</p>
                    <Button variant="outline" size="sm" asChild>
                        <Link
                            href={
                                mealPlans.index({ query: { day: dayNumber } })
                                    .url
                            }
                        >
                            {t('meal_plans.generation.try_again')}
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
                    {t('meal_plans.generation.generating_title')}
                </AlertTitle>
                <AlertDescription className="text-muted-foreground">
                    {t('meal_plans.generation.generating_description')}
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

interface DayPaginationProps {
    currentDay: number;
    totalDays: number;
}

function DayPagination({ currentDay, totalDays }: DayPaginationProps) {
    const days = Array.from({ length: totalDays }, (_, i) => i + 1);
    const { t } = useTranslation('common');

    return (
        <nav className="flex items-center justify-between border-t border-border px-4 sm:px-0">
            {/* Previous */}
            <div className="-mt-px flex w-0 flex-1">
                <Link
                    href={
                        mealPlans.index({
                            query: { day: Math.max(1, currentDay - 1) },
                        }).url
                    }
                    preserveScroll
                    className={`inline-flex items-center border-t-2 border-transparent pt-4 pr-1 text-sm font-medium text-muted-foreground hover:border-border hover:text-foreground ${
                        currentDay === 1 ? 'pointer-events-none opacity-50' : ''
                    }`}
                >
                    <ChevronLeft className="mr-2 h-4 w-4" />
                    {t('meal_plans.previous')}
                </Link>
            </div>

            {/* Day numbers - hidden on mobile */}
            <div className="hidden md:-mt-px md:flex">
                {days.map((day) => (
                    <Link
                        key={day}
                        href={mealPlans.index({ query: { day } }).url}
                        preserveScroll
                        aria-current={day === currentDay ? 'page' : undefined}
                        className={`inline-flex items-center border-t-2 px-4 pt-4 text-sm font-medium ${
                            day === currentDay
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground'
                        }`}
                    >
                        {day}
                    </Link>
                ))}
            </div>

            {/* Mobile: show current day indicator */}
            <div className="-mt-px flex items-center pt-4 md:hidden">
                <span className="text-sm text-muted-foreground">
                    {t('meal_plans.day_of', {
                        current: currentDay,
                        total: totalDays,
                    })}
                </span>
            </div>

            {/* Next */}
            <div className="-mt-px flex w-0 flex-1 justify-end">
                <Link
                    href={
                        mealPlans.index({
                            query: { day: Math.min(totalDays, currentDay + 1) },
                        }).url
                    }
                    preserveScroll
                    className={`inline-flex items-center border-t-2 border-transparent pt-4 pl-1 text-sm font-medium text-muted-foreground hover:border-border hover:text-foreground ${
                        currentDay === totalDays
                            ? 'pointer-events-none opacity-50'
                            : ''
                    }`}
                >
                    {t('meal_plans.next')}
                    <ChevronRight className="ml-2 h-4 w-4" />
                </Link>
            </div>
        </nav>
    );
}

interface PlanInfoFooterProps {
    mealPlan: MealPlan;
    currentDay: CurrentDay;
    onRegenerateStart: () => void;
}

function PlanInfoFooter({
    mealPlan,
    currentDay,
    onRegenerateStart,
}: PlanInfoFooterProps) {
    const { t } = useTranslation('common');
    const regenerateForm = useForm({
        day: currentDay.day_number,
    });

    const isRegenerating = currentDay.status === GenerationStatus.Generating;
    const canRegenerate =
        currentDay.status === GenerationStatus.Completed ||
        currentDay.status === GenerationStatus.Failed;

    const handleRegenerate = () => {
        regenerateForm.post(mealPlans.regenerateDay(mealPlan.id).url, {
            onSuccess: () => {
                onRegenerateStart();
            },
        });
    };

    return (
        <div className="mt-8 flex flex-col items-start justify-between gap-4 rounded-lg bg-muted/30 p-4 text-sm text-muted-foreground sm:flex-row sm:items-center">
            <p>
                {t('meal_plans.created_on')}{' '}
                {new Date(mealPlan.created_at).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                })}
            </p>

            {canRegenerate && (
                <AlertDialog>
                    <AlertDialogTrigger asChild>
                        <Button
                            variant="outline"
                            size="sm"
                            disabled={
                                isRegenerating || regenerateForm.processing
                            }
                        >
                            {regenerateForm.processing || isRegenerating ? (
                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                            ) : (
                                <RefreshCw className="mr-2 h-4 w-4" />
                            )}
                            {t('meal_plans.regenerate_day')}
                        </Button>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>
                                {t('meal_plans.regenerate_title')}
                            </AlertDialogTitle>
                            <AlertDialogDescription
                                dangerouslySetInnerHTML={{
                                    __html: t(
                                        'meal_plans.regenerate_description',
                                        { dayName: currentDay.day_name },
                                    ),
                                }}
                            />
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>
                                {t('meal_plans.cancel')}
                            </AlertDialogCancel>
                            <AlertDialogAction onClick={handleRegenerate}>
                                {t('meal_plans.regenerate')}
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            )}
        </div>
    );
}
