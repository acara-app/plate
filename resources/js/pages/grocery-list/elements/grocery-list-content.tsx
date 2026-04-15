import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import {
    GroceryStatus,
    type GroceryItem,
    type GroceryList,
} from '@/types/grocery-list';
import {
    CalendarDays,
    CircleAlert,
    CircleCheck,
    LayoutGrid,
    Loader2,
    RefreshCw,
    ShoppingCart,
} from 'lucide-react';
import { useTranslation } from 'react-i18next';
import { GroceryCategoryLabel } from './grocery-category-label';
import { GroceryItemRow } from './grocery-item-row';

export type ViewMode = 'category' | 'day';

interface GroceryListContentProps {
    groceryList: GroceryList;
    onToggleItem: (item: GroceryItem) => void;
    onRegenerate: () => void;
    isRegenerating: boolean;
    viewMode: ViewMode;
    onViewModeChange: (mode: ViewMode) => void;
}

export function GroceryListContent({
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
            <div className="space-y-3 rounded-xl border bg-card p-4 shadow-sm">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex items-center gap-2">
                        <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <ShoppingCart className="h-4 w-4" />
                        </div>
                        <div>
                            <p className="font-medium">
                                {t('grocery_list.progress')}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {groceryList.checked_items}{' '}
                                {t('grocery_list.of')} {groceryList.total_items}{' '}
                                {t('grocery_list.items')}
                            </p>
                        </div>
                    </div>
                    <span className="text-sm font-semibold text-primary tabular-nums">
                        {Math.round(progress)}%
                    </span>
                </div>
                <Progress value={progress} className="h-2.5" />
            </div>

            {/* View Mode Toggle */}
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <ToggleGroup
                    type="single"
                    value={viewMode}
                    onValueChange={(value) =>
                        value && onViewModeChange(value as ViewMode)
                    }
                    variant="outline"
                    size="sm"
                    className="grid w-full grid-cols-1 sm:w-auto sm:grid-cols-2"
                >
                    <ToggleGroupItem
                        value="category"
                        aria-label={t('grocery_list.view_modes.by_category')}
                        className="min-h-11 justify-center"
                    >
                        <LayoutGrid className="h-4 w-4" />
                        {t('grocery_list.view_modes.by_category')}
                    </ToggleGroupItem>
                    <ToggleGroupItem
                        value="day"
                        aria-label={t('grocery_list.view_modes.by_day')}
                        className="min-h-11 justify-center"
                    >
                        <CalendarDays className="h-4 w-4" />
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
                    <CircleCheck className="h-4 w-4 text-green-600" />
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
                    <CircleAlert className="h-4 w-4 text-red-600" />
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
                                        <GroceryCategoryLabel
                                            category={category}
                                        />
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
                                                        <GroceryCategoryLabel
                                                            category={category}
                                                        />
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
                <div className="flex flex-col items-center justify-center rounded-xl border border-dashed bg-card p-8 text-center shadow-sm md:p-12">
                    <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <ShoppingCart className="h-6 w-6" />
                    </div>
                    <h3 className="text-lg font-semibold">
                        {t('grocery_list.empty_items.title')}
                    </h3>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {t('grocery_list.empty_items.description')}
                    </p>
                    <Button
                        variant="outline"
                        className="mt-4 w-full sm:w-auto"
                        onClick={onRegenerate}
                        disabled={isRegenerating}
                    >
                        {isRegenerating ? (
                            <Loader2 className="h-4 w-4 animate-spin" />
                        ) : (
                            <RefreshCw className="h-4 w-4" />
                        )}
                        {t('grocery_list.empty_items.button')}
                    </Button>
                </div>
            )}
        </>
    );
}
