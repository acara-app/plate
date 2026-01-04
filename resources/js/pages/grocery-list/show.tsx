import {
    store as generateGroceryList,
    show as showGroceryList,
    toggleItem,
} from '@/actions/App/Http/Controllers/GroceryListController';
import printGroceryList from '@/actions/App/Http/Controllers/PrintGroceryListController';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Progress } from '@/components/ui/progress';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import AppLayout from '@/layouts/app-layout';
import mealPlans from '@/routes/meal-plans';
import { type BreadcrumbItem } from '@/types';
import {
    categoryEmojis,
    type GroceryItem,
    type GroceryList,
    GroceryStatus,
    type MealPlanSummary,
} from '@/types/grocery-list';
import { Head, Link, router, useForm, usePoll } from '@inertiajs/react';
import {
    ArrowLeft,
    CalendarDays,
    LayoutGrid,
    Loader2,
    Printer,
    RefreshCw,
    ShoppingCart,
    Sparkles,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';

interface GroceryListPageProps {
    mealPlan: MealPlanSummary;
    groceryList: GroceryList | null;
}

type ViewMode = 'category' | 'day';

export default function GroceryListPage({
    mealPlan,
    groceryList,
}: GroceryListPageProps) {
    const regenerateForm = useForm({});
    const [viewMode, setViewMode] = useState<ViewMode>('category');
    const { t } = useTranslation('common');

    const isGenerating = groceryList?.status === GroceryStatus.Generating;
    const hasNoList = !groceryList;

    const { start, stop } = usePoll(
        4000,
        { only: ['groceryList'] },
        {
            autoStart: false,
        },
    );

    useEffect(() => {
        if (isGenerating) {
            start();
        } else {
            stop();
        }
    }, [isGenerating, start, stop]);

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t('meal_plans.title'),
            href: mealPlans.index().url,
        },
        {
            title: t('grocery_list.title'),
            href: showGroceryList(mealPlan.id).url,
        },
    ];

    const handleToggleItem = (item: GroceryItem) => {
        router.patch(toggleItem(item.id).url, {}, { preserveScroll: true });
    };

    const handleRegenerate = () => {
        regenerateForm.post(generateGroceryList.url(mealPlan.id));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('grocery_list.title')} />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div className="space-y-2">
                        <div className="flex items-center gap-2">
                            <Button variant="ghost" size="icon" asChild>
                                <Link href={mealPlans.index().url}>
                                    <ArrowLeft className="h-4 w-4" />
                                </Link>
                            </Button>
                            <Badge variant="default">
                                <ShoppingCart className="mr-1 h-3 w-3" />
                                {t('grocery_list.title')}
                            </Badge>
                        </div>
                        <h1 className="text-3xl font-bold tracking-tight">
                            {t('grocery_list.title')}
                        </h1>
                        <p className="text-muted-foreground">
                            {t('grocery_list.day_meal_plan', {
                                days: mealPlan.duration_days,
                            })}
                        </p>
                    </div>

                    {/* Actions */}
                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={handleRegenerate}
                            disabled={regenerateForm.processing || isGenerating}
                        >
                            {regenerateForm.processing || isGenerating ? (
                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                            ) : (
                                <RefreshCw className="mr-2 h-4 w-4" />
                            )}
                            {hasNoList
                                ? t('grocery_list.generate')
                                : t('grocery_list.regenerate')}
                        </Button>
                        {groceryList && (
                            <Button
                                variant="outline"
                                size="sm"
                                asChild
                                disabled={isGenerating}
                            >
                                <a
                                    href={printGroceryList(mealPlan.id).url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <Printer className="mr-2 h-4 w-4" />
                                    {t('grocery_list.print')}
                                </a>
                            </Button>
                        )}
                    </div>
                </div>

                {hasNoList ? (
                    <EmptyGroceryListState
                        onGenerate={handleRegenerate}
                        isGenerating={regenerateForm.processing}
                    />
                ) : isGenerating ? (
                    <GroceryListSkeleton />
                ) : (
                    <GroceryListContent
                        groceryList={groceryList}
                        onToggleItem={handleToggleItem}
                        onRegenerate={handleRegenerate}
                        isRegenerating={regenerateForm.processing}
                        viewMode={viewMode}
                        onViewModeChange={setViewMode}
                    />
                )}
            </div>
        </AppLayout>
    );
}

interface EmptyGroceryListStateProps {
    onGenerate: () => void;
    isGenerating: boolean;
}

function EmptyGroceryListState({
    onGenerate,
    isGenerating,
}: EmptyGroceryListStateProps) {
    const { t } = useTranslation('common');
    return (
        <div className="flex flex-1 flex-col items-center justify-center rounded-lg border border-dashed p-12 text-center">
            <ShoppingCart className="mb-4 h-16 w-16 text-muted-foreground" />
            <h3 className="text-xl font-semibold">
                {t('grocery_list.empty.title')}
            </h3>
            <p className="mt-2 max-w-md text-muted-foreground">
                {t('grocery_list.empty.description')}
            </p>
            <Button
                className="mt-6"
                onClick={onGenerate}
                disabled={isGenerating}
            >
                {isGenerating ? (
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                ) : (
                    <ShoppingCart className="mr-2 h-4 w-4" />
                )}
                {t('grocery_list.empty.button')}
            </Button>
        </div>
    );
}

function GroceryListSkeleton() {
    const { t } = useTranslation('common');
    return (
        <div className="space-y-6">
            {/* Loading Alert */}
            <Alert className="border-primary/30 bg-primary/5">
                <Loader2 className="h-4 w-4 animate-spin text-primary" />
                <AlertTitle className="text-primary">
                    {t('grocery_list.generating.title')}
                </AlertTitle>
                <AlertDescription className="text-muted-foreground">
                    {t('grocery_list.generating.description')}
                </AlertDescription>
            </Alert>

            {/* Skeleton Cards */}
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                {[1, 2, 3, 4, 5, 6].map((i) => (
                    <Card key={i} className="overflow-hidden">
                        <CardHeader className="pb-3">
                            <div className="flex items-center justify-between">
                                <div className="h-6 w-32 animate-pulse rounded bg-muted" />
                                <div className="h-5 w-12 animate-pulse rounded bg-muted" />
                            </div>
                        </CardHeader>
                        <CardContent className="pt-0">
                            <div className="space-y-3">
                                {[1, 2, 3, 4].map((j) => (
                                    <div
                                        key={j}
                                        className="flex items-center gap-3"
                                    >
                                        <div className="h-4 w-4 animate-pulse rounded bg-muted" />
                                        <div className="h-4 flex-1 animate-pulse rounded bg-muted" />
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                ))}
            </div>
        </div>
    );
}

interface GroceryListContentProps {
    groceryList: GroceryList;
    onToggleItem: (item: GroceryItem) => void;
    onRegenerate: () => void;
    isRegenerating: boolean;
    viewMode: ViewMode;
    onViewModeChange: (mode: ViewMode) => void;
}

function GroceryListContent({
    groceryList,
    onToggleItem,
    onRegenerate,
    isRegenerating,
    viewMode,
    onViewModeChange,
}: GroceryListContentProps) {
    const { t } = useTranslation('common');
    const progress =
        groceryList.total_items > 0
            ? (groceryList.checked_items / groceryList.total_items) * 100
            : 0;

    const categories = Object.keys(groceryList.items_by_category);
    const days = Object.keys(groceryList.items_by_day || {})
        .map(Number)
        .sort((a, b) => a - b);

    return (
        <>
            {/* Progress */}
            <div className="space-y-2">
                <div className="flex items-center justify-between text-sm">
                    <span className="text-muted-foreground">
                        {t('grocery_list.progress')}
                    </span>
                    <span className="font-medium">
                        {groceryList.checked_items} {t('grocery_list.of')}{' '}
                        {groceryList.total_items} {t('grocery_list.items')}
                    </span>
                </div>
                <Progress value={progress} className="h-2" />
            </div>

            {/* View Mode Toggle */}
            <div className="flex items-center justify-between">
                <ToggleGroup
                    type="single"
                    value={viewMode}
                    onValueChange={(value) =>
                        value && onViewModeChange(value as ViewMode)
                    }
                    variant="outline"
                    size="sm"
                >
                    <ToggleGroupItem
                        value="category"
                        aria-label="View by category"
                    >
                        <LayoutGrid className="mr-2 h-4 w-4" />
                        {t('grocery_list.view_modes.by_category')}
                    </ToggleGroupItem>
                    <ToggleGroupItem value="day" aria-label="View by day">
                        <CalendarDays className="mr-2 h-4 w-4" />
                        {t('grocery_list.view_modes.by_day')}
                    </ToggleGroupItem>
                </ToggleGroup>

                {viewMode === 'day' && days.length > 0 && (
                    <span className="text-sm text-muted-foreground">
                        {days.length === 1
                            ? t('grocery_list.view_modes.days_with_items', {
                                  count: days.length,
                              })
                            : t(
                                  'grocery_list.view_modes.days_with_items_plural',
                                  { count: days.length },
                              )}
                    </span>
                )}
            </div>

            {/* Status Alerts */}
            {groceryList.status === GroceryStatus.Completed && (
                <Alert className="border-green-500/30 bg-green-500/5">
                    <Sparkles className="h-4 w-4 text-green-600" />
                    <AlertTitle className="text-green-700 dark:text-green-400">
                        {t('grocery_list.status.complete_title')}
                    </AlertTitle>
                    <AlertDescription className="text-green-600 dark:text-green-300">
                        {t('grocery_list.status.complete_description')}
                    </AlertDescription>
                </Alert>
            )}

            {groceryList.status === GroceryStatus.Failed && (
                <Alert className="border-red-500/30 bg-red-500/5">
                    <AlertTitle className="text-red-700 dark:text-red-400">
                        {t('grocery_list.status.failed_title')}
                    </AlertTitle>
                    <AlertDescription className="text-red-600 dark:text-red-300">
                        {t('grocery_list.status.failed_description')}
                    </AlertDescription>
                </Alert>
            )}

            {/* Grocery Items by Category */}
            {viewMode === 'category' && (
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {categories.map((category) => {
                        const items = groceryList.items_by_category[category];
                        const checkedCount = items.filter(
                            (item) => item.is_checked,
                        ).length;

                        return (
                            <Card key={category} className="overflow-hidden">
                                <CardHeader className="pb-3">
                                    <CardTitle className="flex items-center justify-between text-lg">
                                        <span className="flex items-center gap-2">
                                            {categoryEmojis[category] || '📦'}{' '}
                                            {category}
                                        </span>
                                        <Badge
                                            variant="secondary"
                                            className="font-normal"
                                        >
                                            {checkedCount}/{items.length}
                                        </Badge>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="pt-0">
                                    <ul className="space-y-2">
                                        {items.map((item) => (
                                            <GroceryItemRow
                                                key={item.id}
                                                item={item}
                                                onToggle={onToggleItem}
                                                showDays
                                            />
                                        ))}
                                    </ul>
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>
            )}

            {/* Grocery Items by Day */}
            {viewMode === 'day' && (
                <div className="space-y-6">
                    {days.map((day) => {
                        const items = groceryList.items_by_day[day] || [];
                        const checkedCount = items.filter(
                            (item) => item.is_checked,
                        ).length;

                        // Group items by category within each day
                        const itemsByCategory = items.reduce<
                            Record<string, GroceryItem[]>
                        >((acc, item) => {
                            if (!acc[item.category]) {
                                acc[item.category] = [];
                            }
                            acc[item.category].push(item);
                            return acc;
                        }, {});

                        const dayCategories = Object.keys(itemsByCategory);

                        return (
                            <div key={day} className="space-y-4">
                                <div className="flex items-center justify-between">
                                    <h3 className="flex items-center gap-2 text-xl font-semibold">
                                        <CalendarDays className="h-5 w-5 text-primary" />
                                        {t('grocery_list.day', { number: day })}
                                    </h3>
                                    <Badge
                                        variant="outline"
                                        className="font-normal"
                                    >
                                        {checkedCount}/{items.length}{' '}
                                        {t('grocery_list.items')}
                                    </Badge>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                    {dayCategories.map((category) => {
                                        const categoryItems =
                                            itemsByCategory[category];
                                        const categoryCheckedCount =
                                            categoryItems.filter(
                                                (item) => item.is_checked,
                                            ).length;

                                        return (
                                            <Card
                                                key={`${day}-${category}`}
                                                className="overflow-hidden"
                                            >
                                                <CardHeader className="pb-3">
                                                    <CardTitle className="flex items-center justify-between text-lg">
                                                        <span className="flex items-center gap-2">
                                                            {categoryEmojis[
                                                                category
                                                            ] || '📦'}{' '}
                                                            {category}
                                                        </span>
                                                        <Badge
                                                            variant="secondary"
                                                            className="font-normal"
                                                        >
                                                            {
                                                                categoryCheckedCount
                                                            }
                                                            /
                                                            {
                                                                categoryItems.length
                                                            }
                                                        </Badge>
                                                    </CardTitle>
                                                </CardHeader>
                                                <CardContent className="pt-0">
                                                    <ul className="space-y-2">
                                                        {categoryItems.map(
                                                            (item) => (
                                                                <GroceryItemRow
                                                                    key={`${day}-${item.id}`}
                                                                    item={item}
                                                                    onToggle={
                                                                        onToggleItem
                                                                    }
                                                                    currentDay={
                                                                        day
                                                                    }
                                                                />
                                                            ),
                                                        )}
                                                    </ul>
                                                </CardContent>
                                            </Card>
                                        );
                                    })}
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}

            {/* Empty State */}
            {categories.length === 0 && (
                <div className="flex flex-col items-center justify-center rounded-lg border border-dashed p-12 text-center">
                    <ShoppingCart className="mb-4 h-12 w-12 text-muted-foreground" />
                    <h3 className="text-lg font-semibold">
                        {t('grocery_list.empty_items.title')}
                    </h3>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {t('grocery_list.empty_items.description')}
                    </p>
                    <Button
                        variant="outline"
                        className="mt-4"
                        onClick={onRegenerate}
                        disabled={isRegenerating}
                    >
                        {isRegenerating ? (
                            <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                        ) : (
                            <RefreshCw className="mr-2 h-4 w-4" />
                        )}
                        {t('grocery_list.empty_items.button')}
                    </Button>
                </div>
            )}
        </>
    );
}

interface GroceryItemRowProps {
    item: GroceryItem;
    onToggle: (item: GroceryItem) => void;
    showDays?: boolean;
    currentDay?: number;
}

function GroceryItemRow({
    item,
    onToggle,
    showDays,
    currentDay,
}: GroceryItemRowProps) {
    const days = item.days ?? [];
    const otherDays = currentDay ? days.filter((d) => d !== currentDay) : [];
    const hasOtherDays = otherDays.length > 0;
    const { t } = useTranslation('common');
    return (
        <li className="flex items-center gap-3">
            <Checkbox
                id={`item-${item.id}${currentDay ? `-day-${currentDay}` : ''}`}
                checked={item.is_checked}
                onCheckedChange={() => onToggle(item)}
            />
            <label
                htmlFor={`item-${item.id}${currentDay ? `-day-${currentDay}` : ''}`}
                className={`flex-1 cursor-pointer text-sm ${
                    item.is_checked ? 'text-muted-foreground line-through' : ''
                }`}
            >
                <span className="font-medium">{item.name}</span>
                <span className="ml-2 text-muted-foreground">
                    {item.quantity}
                </span>
                {showDays && days.length > 1 && (
                    <span className="ml-2 text-xs text-muted-foreground">
                        (Day {days.join(', ')})
                    </span>
                )}
                {hasOtherDays && (
                    <span className="ml-2 text-xs text-muted-foreground">
                        {t('grocery_list.also_day', {
                            days: otherDays.join(', '),
                        })}
                    </span>
                )}
            </label>
        </li>
    );
}
