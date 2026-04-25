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
import { Label } from '@/components/ui/label';
import { Progress } from '@/components/ui/progress';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { Textarea } from '@/components/ui/textarea';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import useSharedProps from '@/hooks/use-shared-props';
import AppLayout from '@/layouts/app-layout';
import { cn, generateUUID } from '@/lib/utils';
import { MealCard } from '@/pages/meal-plans/elements/meal-card';
import { NutritionStats } from '@/pages/meal-plans/elements/nutrition-stats';
import chat from '@/routes/chat';
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
    CalendarDays,
    CheckCircle2,
    ChefHat,
    ChevronLeft,
    ChevronRight,
    Info,
    Loader2,
    MessageSquare,
    Printer,
    RefreshCw,
    ShoppingCart,
    Sparkles,
    Target,
    Utensils,
} from 'lucide-react';
import { useTranslation } from 'react-i18next';

interface MealPlansProps {
    mealPlan: MealPlan | null;
    currentDay: CurrentDay | null;
    navigation: Navigation | null;
    userDietType: string;
    dietTypes: Record<string, string>;
}

const getBreadcrumbs = (t: (key: string) => string): BreadcrumbItem[] => [
    {
        title: t('meal_plans.title'),
        href: mealPlans.index().url,
    },
];

export default function MealPlans({
    mealPlan,
    currentDay,
    navigation,
    userDietType,
    dietTypes,
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

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-hidden p-4 md:p-6">
                {!currentUser?.is_onboarded ? (
                    <OnboardingBanner />
                ) : !mealPlan ? (
                    <EmptyMealPlanState
                        userDietType={userDietType}
                        dietTypes={dietTypes}
                    />
                ) : (
                    mealPlan &&
                    currentDay &&
                    navigation && (
                        <>
                            <PlanSummary
                                mealPlan={mealPlan}
                                dietTypes={dietTypes}
                            />

                            <DayPagination
                                currentDay={currentDay.day_number}
                                navigation={navigation}
                            />

                            <section className="space-y-4 rounded-xl border bg-card p-4 shadow-sm md:p-5">
                                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                    <div className="space-y-2">
                                        <Badge
                                            variant="secondary"
                                            className="w-fit gap-1.5 bg-primary/10 text-primary hover:bg-primary/10 dark:bg-primary/20"
                                        >
                                            <CalendarDays className="h-3.5 w-3.5" />
                                            {t('meal_plans.day_of', {
                                                current: currentDay.day_number,
                                                total: navigation.total_days,
                                            })}
                                        </Badge>
                                        <h2 className="flex items-center gap-2 text-2xl font-semibold">
                                            <ChefHat className="h-6 w-6 text-primary" />
                                            {currentDay.day_name}
                                        </h2>
                                    </div>

                                    <div className="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto] lg:min-w-96">
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
                                        <RegenerateDayButton
                                            mealPlan={mealPlan}
                                            currentDay={currentDay}
                                            onRegenerateStart={startPolling}
                                        />
                                    </div>
                                </div>

                                <NutritionStats
                                    calories={
                                        currentDay.daily_stats.total_calories
                                    }
                                    protein={currentDay.daily_stats.protein}
                                    carbs={currentDay.daily_stats.carbs}
                                    fat={currentDay.daily_stats.fat}
                                    size="lg"
                                />
                            </section>

                            {mealPlan.metadata?.preparation_notes && (
                                <Alert className="border-cyan-500/30 bg-cyan-500/5">
                                    <Info className="h-4 w-4 text-cyan-700 dark:text-cyan-300" />
                                    <AlertDescription>
                                        <strong className="font-semibold text-foreground">
                                            {t('meal_plans.preparation_tips')}
                                        </strong>{' '}
                                        {mealPlan.metadata.preparation_notes}
                                    </AlertDescription>
                                </Alert>
                            )}

                            <section className="space-y-3">
                                <div className="flex items-center justify-between gap-3">
                                    <h3 className="flex items-center gap-2 text-lg font-semibold">
                                        <Utensils className="h-5 w-5 text-primary" />
                                        {t('meal_plans.todays_meals')}
                                    </h3>
                                    {!currentDay.needs_generation &&
                                        currentDay.meals.length > 0 && (
                                            <Badge
                                                variant="outline"
                                                className="font-normal"
                                            >
                                                {currentDay.meals.length}{' '}
                                                {t('meal_plans.meals')}
                                            </Badge>
                                        )}
                                </div>

                                {currentDay.needs_generation ? (
                                    <GeneratingMealsState
                                        status={currentDay.status}
                                        dayNumber={currentDay.day_number}
                                        mealPlanId={mealPlan.id}
                                        onRetry={startPolling}
                                    />
                                ) : currentDay.meals.length === 0 ? (
                                    <Alert>
                                        <Info className="h-4 w-4" />
                                        <AlertDescription>
                                            {t('meal_plans.no_meals')}
                                        </AlertDescription>
                                    </Alert>
                                ) : (
                                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                        {currentDay.meals.map((meal) => (
                                            <MealCard
                                                key={meal.id}
                                                meal={meal}
                                            />
                                        ))}
                                    </div>
                                )}
                            </section>

                            <div className="rounded-lg border bg-muted/20 px-4 py-3 text-sm text-muted-foreground">
                                <p>
                                    {t('meal_plans.created_on')}{' '}
                                    <time dateTime={mealPlan.created_at}>
                                        {new Date(
                                            mealPlan.created_at,
                                        ).toLocaleDateString(undefined, {
                                            year: 'numeric',
                                            month: 'long',
                                            day: 'numeric',
                                        })}
                                    </time>
                                </p>
                            </div>
                        </>
                    )
                )}
            </div>
        </AppLayout>
    );
}

interface EmptyMealPlanStateProps {
    userDietType: string;
    dietTypes: Record<string, string>;
}

function EmptyMealPlanState({
    userDietType,
    dietTypes,
}: EmptyMealPlanStateProps) {
    const { t } = useTranslation('common');

    return (
        <>
            <div className="space-y-2">
                <h1 className="flex items-center gap-2 text-3xl font-bold tracking-tight">
                    <CalendarDays className="h-8 w-8 text-primary" />
                    {t('meal_plans.your_meal_plans')}
                </h1>
                <p className="max-w-2xl text-muted-foreground">
                    {t('meal_plans.description')}
                </p>
            </div>

            <section className="grid gap-6 rounded-xl border bg-card p-5 shadow-sm md:p-8 lg:grid-cols-[minmax(0,1fr)_18rem] lg:items-center">
                <div className="space-y-5">
                    <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <Sparkles className="h-6 w-6" />
                    </div>
                    <div className="space-y-2">
                        <h2 className="text-2xl font-semibold">
                            {t('meal_plans.empty_title')}
                        </h2>
                        <p className="max-w-xl text-muted-foreground">
                            {t('meal_plans.no_plans')}
                        </p>
                    </div>
                    <div className="grid gap-3 sm:flex sm:flex-wrap">
                        <Button className="w-full sm:w-auto" asChild>
                            <Link
                                href={`${chat.create(generateUUID()).url}?mode=create-meal-plan`}
                            >
                                <MessageSquare className="h-4 w-4" />
                                {t('meal_plans.create_with_altani')}
                            </Link>
                        </Button>
                        <GenerateMealPlanDialog
                            defaultDietType={userDietType}
                            dietTypes={dietTypes}
                        />
                    </div>
                </div>

                <div className="mx-auto hidden w-full max-w-64 lg:block">
                    <img
                        src="/images/altani/altani_holding_plate-320.webp"
                        alt=""
                        aria-hidden="true"
                        loading="lazy"
                        className="h-auto w-full"
                    />
                </div>
            </section>
        </>
    );
}

interface PlanSummaryProps {
    mealPlan: MealPlan;
    dietTypes: Record<string, string>;
}

function PlanSummary({ mealPlan, dietTypes }: PlanSummaryProps) {
    const { t } = useTranslation('common');
    const dietTypeKey =
        typeof mealPlan.metadata?.diet_type === 'string'
            ? mealPlan.metadata.diet_type
            : null;
    const dietLabel = dietTypeKey ? (dietTypes[dietTypeKey] ?? null) : null;

    return (
        <section className="relative overflow-hidden rounded-xl border bg-card p-4 shadow-sm md:p-5">
            <div className="absolute inset-x-0 top-0 h-1 bg-linear-to-r from-emerald-500 via-cyan-400 to-emerald-400" />
            <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div className="space-y-3">
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge variant="default" className="gap-1.5 capitalize">
                            <CalendarDays className="h-3.5 w-3.5" />
                            {mealPlan.type}
                        </Badge>
                        <Badge variant="outline" className="gap-1.5">
                            <CheckCircle2 className="h-3.5 w-3.5 text-primary" />
                            {mealPlan.duration_days} {t('meal_plans.days')}
                        </Badge>
                        {dietLabel && (
                            <Badge variant="secondary" className="gap-1.5">
                                <Utensils className="h-3.5 w-3.5" />
                                <span className="sr-only">
                                    {t('meal_plans.diet_badge')}:{' '}
                                </span>
                                {dietLabel}
                            </Badge>
                        )}
                    </div>
                    <h1 className="max-w-4xl text-3xl font-bold tracking-tight">
                        {mealPlan.name || t('meal_plans.fallback_name')}
                    </h1>
                </div>

                <div className="grid gap-2 sm:flex sm:flex-wrap lg:justify-end">
                    <Button
                        variant="outline"
                        size="sm"
                        className="min-h-10 w-full sm:w-auto"
                        asChild
                    >
                        <Link href={showGroceryList(mealPlan.id).url}>
                            <ShoppingCart className="h-4 w-4" />
                            {t('meal_plans.grocery_list')}
                        </Link>
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        className="min-h-10 w-full sm:w-auto"
                        asChild
                    >
                        <a
                            href={mealPlans.print(mealPlan.id).url}
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <Printer className="h-4 w-4" />
                            {t('meal_plans.print')}
                        </a>
                    </Button>
                </div>
            </div>
        </section>
    );
}

interface CalorieComparisonProps {
    actual: number;
    target: number;
}

function CalorieComparison({ actual, target }: CalorieComparisonProps) {
    const diff = actual - target;
    const targetProgress = target > 0 ? (actual / target) * 100 : 0;
    const differencePercentage =
        target > 0 ? Math.round((diff / target) * 100) : 0;
    const isWithinRange = Math.abs(diff) <= 50;
    const { t } = useTranslation('common');

    const status = isWithinRange
        ? t('meal_plans.on_target')
        : diff > 0
          ? t('meal_plans.over_target')
          : t('meal_plans.under_target');

    return (
        <div className="rounded-lg border bg-background/70 p-3">
            <div className="flex items-start justify-between gap-3">
                <div className="flex items-center gap-2 text-sm font-medium">
                    <Target className="h-4 w-4 text-primary" />
                    {status}
                </div>
                <div className="text-right">
                    <div
                        className={cn(
                            'font-semibold',
                            isWithinRange
                                ? 'text-green-700 dark:text-green-300'
                                : 'text-foreground',
                        )}
                    >
                        {diff > 0 ? '+' : ''}
                        {Math.round(diff)} {t('meal_plans.cal')}
                    </div>
                    <div className="text-xs text-muted-foreground">
                        {diff > 0 ? '+' : ''}
                        {differencePercentage}% {t('meal_plans.vs_target')}
                    </div>
                </div>
            </div>
            <Progress value={targetProgress} className="mt-3 h-2" />
        </div>
    );
}

interface GeneratingMealsStateProps {
    status: MealPlanGenerationStatus;
    dayNumber: number;
    mealPlanId: number;
    onRetry: () => void;
}

function GeneratingMealsState({
    status,
    dayNumber,
    mealPlanId,
    onRetry,
}: GeneratingMealsStateProps) {
    const { t } = useTranslation('common');
    const retryForm = useForm({ day: dayNumber });

    if (status === GenerationStatus.Failed) {
        return (
            <Alert variant="destructive" aria-live="polite">
                <Info className="h-4 w-4" />
                <AlertTitle>
                    {t('meal_plans.generation.failed_title')}
                </AlertTitle>
                <AlertDescription className="space-y-3">
                    <p>{t('meal_plans.generation.failed_description')}</p>
                    <Button
                        variant="outline"
                        size="sm"
                        className="min-h-10 w-full sm:w-auto"
                        disabled={retryForm.processing}
                        onClick={() =>
                            retryForm.post(
                                mealPlans.regenerateDay(mealPlanId).url,
                                { onSuccess: onRetry },
                            )
                        }
                    >
                        {retryForm.processing ? (
                            <Loader2 className="h-4 w-4 animate-spin" />
                        ) : (
                            <RefreshCw className="h-4 w-4" />
                        )}
                        {t('meal_plans.generation.try_again')}
                    </Button>
                </AlertDescription>
            </Alert>
        );
    }

    return (
        <div className="space-y-4" aria-live="polite">
            <Alert className="border-primary/30 bg-primary/5">
                <Loader2 className="h-4 w-4 animate-spin text-primary" />
                <AlertTitle className="text-primary">
                    {t('meal_plans.generation.generating_title')}
                </AlertTitle>
                <AlertDescription className="text-muted-foreground">
                    {t('meal_plans.generation.generating_description')}
                </AlertDescription>
            </Alert>

            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                {[1, 2, 3, 4].map((i) => (
                    <MealCardSkeleton key={i} />
                ))}
            </div>
        </div>
    );
}

function MealCardSkeleton() {
    return (
        <div className="rounded-xl border bg-card p-4 shadow-sm">
            <div className="space-y-4">
                <div className="flex items-center justify-between gap-3">
                    <Skeleton className="h-6 w-24" />
                    <Skeleton className="h-4 w-16" />
                </div>
                <Skeleton className="h-6 w-3/4" />
                <div className="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    {[1, 2, 3, 4].map((i) => (
                        <Skeleton key={i} className="h-16 rounded-lg" />
                    ))}
                </div>
                <Skeleton className="h-11 w-full" />
            </div>
        </div>
    );
}

interface DayPaginationProps {
    currentDay: number;
    navigation: Navigation;
}

function DayPagination({ currentDay, navigation }: DayPaginationProps) {
    const days = Array.from({ length: navigation.total_days }, (_, i) => i + 1);
    const { t } = useTranslation('common');

    return (
        <nav
            aria-label={t('meal_plans.day_navigation')}
            className="rounded-xl border bg-card p-2 shadow-sm"
        >
            <div className="flex items-center gap-2">
                <Link
                    href={
                        mealPlans.index({
                            query: { day: navigation.previous_day },
                        }).url
                    }
                    preserveScroll
                    aria-label={t('meal_plans.previous_day')}
                    className="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-lg border px-3 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                >
                    <ChevronLeft className="h-4 w-4" />
                    <span className="hidden sm:inline">
                        {t('meal_plans.previous')}
                    </span>
                </Link>

                <div className="no-scrollbar flex flex-1 gap-2 overflow-x-auto">
                    {days.map((day) => (
                        <Link
                            key={day}
                            href={mealPlans.index({ query: { day } }).url}
                            preserveScroll
                            aria-current={
                                day === currentDay ? 'page' : undefined
                            }
                            aria-label={t('meal_plans.view_day', { day })}
                            className={cn(
                                'flex min-h-11 min-w-11 flex-col items-center justify-center rounded-lg border px-3 text-sm font-medium transition-colors focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none',
                                day === currentDay
                                    ? 'border-primary bg-primary text-primary-foreground shadow-sm'
                                    : 'border-transparent bg-muted/40 text-muted-foreground hover:bg-accent hover:text-accent-foreground',
                            )}
                        >
                            <span className="text-xs">
                                {t('meal_plans.day_short')}
                            </span>
                            <span>{day}</span>
                        </Link>
                    ))}
                </div>

                <Link
                    href={
                        mealPlans.index({
                            query: { day: navigation.next_day },
                        }).url
                    }
                    preserveScroll
                    aria-label={t('meal_plans.next_day')}
                    className="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-lg border px-3 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                >
                    <span className="hidden sm:inline">
                        {t('meal_plans.next')}
                    </span>
                    <ChevronRight className="h-4 w-4" />
                </Link>
            </div>
            <p className="sr-only">
                {t('meal_plans.day_of', {
                    current: currentDay,
                    total: navigation.total_days,
                })}
            </p>
        </nav>
    );
}

interface RegenerateDayButtonProps {
    mealPlan: MealPlan;
    currentDay: CurrentDay;
    onRegenerateStart: () => void;
}

function RegenerateDayButton({
    mealPlan,
    currentDay,
    onRegenerateStart,
}: RegenerateDayButtonProps) {
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

    if (!canRegenerate) {
        return null;
    }

    return (
        <AlertDialog>
            <AlertDialogTrigger asChild>
                <Button
                    variant="outline"
                    size="sm"
                    className="min-h-10 w-full sm:w-auto"
                    disabled={isRegenerating || regenerateForm.processing}
                >
                    {regenerateForm.processing || isRegenerating ? (
                        <Loader2 className="h-4 w-4 animate-spin" />
                    ) : (
                        <RefreshCw className="h-4 w-4" />
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
                            __html: t('meal_plans.regenerate_description', {
                                dayName: currentDay.day_name,
                            }),
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
    );
}

interface GenerateMealPlanDialogProps {
    defaultDietType: string;
    dietTypes: Record<string, string>;
}

function GenerateMealPlanDialog({
    defaultDietType,
    dietTypes,
}: GenerateMealPlanDialogProps) {
    const { t } = useTranslation('common');
    const form = useForm({
        duration_days: 7,
        diet_type: defaultDietType,
        prompt: '',
    });
    const dayOptions = [1, 2, 3, 4, 5, 6, 7];
    const promptMaxLength = 2000;
    const dietTypeEntries = Object.entries(dietTypes);
    const isDefaultDiet = form.data.diet_type === defaultDietType;

    const handleSubmit = () => {
        form.post(mealPlans.store.url());
    };

    return (
        <AlertDialog>
            <AlertDialogTrigger asChild>
                <Button variant="outline" className="w-full sm:w-auto">
                    <Sparkles className="h-4 w-4" />
                    {t('meal_plans.generate_now')}
                </Button>
            </AlertDialogTrigger>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        {t('meal_plans.generate_dialog.title')}
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        {t('meal_plans.generate_dialog.description')}
                    </AlertDialogDescription>
                </AlertDialogHeader>

                <div className="space-y-4 py-2">
                    <div className="space-y-2">
                        <Label htmlFor="meal-plan-duration">
                            {t('meal_plans.generate_dialog.duration_label')}
                        </Label>
                        <ToggleGroup
                            id="meal-plan-duration"
                            type="single"
                            variant="outline"
                            joined
                            value={String(form.data.duration_days)}
                            onValueChange={(value) => {
                                if (value) {
                                    form.setData(
                                        'duration_days',
                                        Number(value),
                                    );
                                }
                            }}
                            className="w-full"
                        >
                            {dayOptions.map((n) => (
                                <ToggleGroupItem
                                    key={n}
                                    value={String(n)}
                                    aria-label={`${n} ${t('meal_plans.days')}`}
                                    className="flex-1"
                                >
                                    {n}
                                </ToggleGroupItem>
                            ))}
                        </ToggleGroup>
                        {form.errors.duration_days && (
                            <p className="text-sm text-destructive">
                                {form.errors.duration_days}
                            </p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="meal-plan-diet-type">
                            {t('meal_plans.generate_dialog.diet_type_label')}
                        </Label>
                        <Select
                            value={form.data.diet_type}
                            onValueChange={(value) =>
                                form.setData('diet_type', value)
                            }
                        >
                            <SelectTrigger
                                id="meal-plan-diet-type"
                                className="w-full"
                            >
                                <SelectValue
                                    placeholder={t(
                                        'meal_plans.generate_dialog.diet_type_placeholder',
                                    )}
                                />
                            </SelectTrigger>
                            <SelectContent>
                                {dietTypeEntries.map(([value, label]) => (
                                    <SelectItem key={value} value={value}>
                                        {label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <p className="text-xs text-muted-foreground">
                            {isDefaultDiet
                                ? t(
                                      'meal_plans.generate_dialog.diet_type_default_hint',
                                  )
                                : t(
                                      'meal_plans.generate_dialog.diet_type_custom_hint',
                                  )}
                        </p>
                        {form.errors.diet_type && (
                            <p className="text-sm text-destructive">
                                {form.errors.diet_type}
                            </p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <div className="flex items-baseline justify-between gap-2">
                            <Label htmlFor="meal-plan-prompt">
                                {t('meal_plans.generate_dialog.prompt_label')}
                            </Label>
                            <span className="text-xs text-muted-foreground">
                                {t(
                                    'meal_plans.generate_dialog.prompt_optional',
                                )}
                            </span>
                        </div>
                        <Textarea
                            id="meal-plan-prompt"
                            rows={3}
                            maxLength={promptMaxLength}
                            placeholder={t(
                                'meal_plans.generate_dialog.prompt_placeholder',
                            )}
                            value={form.data.prompt}
                            onChange={(event) =>
                                form.setData('prompt', event.target.value)
                            }
                        />
                        <div className="flex items-center justify-between gap-2">
                            <p className="text-xs text-muted-foreground">
                                {t('meal_plans.generate_dialog.prompt_hint')}
                            </p>
                            <p className="text-xs text-muted-foreground tabular-nums">
                                {form.data.prompt.length}/{promptMaxLength}
                            </p>
                        </div>
                        {form.errors.prompt && (
                            <p className="text-sm text-destructive">
                                {form.errors.prompt}
                            </p>
                        )}
                    </div>
                </div>

                <AlertDialogFooter>
                    <AlertDialogCancel disabled={form.processing}>
                        {t('meal_plans.cancel')}
                    </AlertDialogCancel>
                    <AlertDialogAction
                        onClick={handleSubmit}
                        disabled={form.processing}
                    >
                        {form.processing ? (
                            <Loader2 className="h-4 w-4 animate-spin" />
                        ) : (
                            <Sparkles className="h-4 w-4" />
                        )}
                        {t('meal_plans.generate_dialog.confirm')}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
