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
import { Deferred, Head, Link, router, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    Loader2,
    Printer,
    RefreshCw,
    ShoppingCart,
    Sparkles,
} from 'lucide-react';

interface GroceryListPageProps {
    mealPlan: MealPlanSummary;
    groceryList?: GroceryList;
}

export default function GroceryListPage({
    mealPlan,
    groceryList,
}: GroceryListPageProps) {
    const regenerateForm = useForm({});

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Meal Plans',
            href: mealPlans.index().url,
        },
        {
            title: 'Grocery List',
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
            <Head title="Grocery List" />

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
                                Grocery List
                            </Badge>
                        </div>
                        <h1 className="text-3xl font-bold tracking-tight">
                            Grocery List
                        </h1>
                        <p className="text-muted-foreground">
                            {mealPlan.duration_days}-day meal plan
                        </p>
                    </div>

                    {/* Actions */}
                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={handleRegenerate}
                            disabled={regenerateForm.processing}
                        >
                            {regenerateForm.processing ? (
                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                            ) : (
                                <RefreshCw className="mr-2 h-4 w-4" />
                            )}
                            Regenerate
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <a
                                href={printGroceryList(mealPlan.id).url}
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <Printer className="mr-2 h-4 w-4" />
                                Print
                            </a>
                        </Button>
                    </div>
                </div>

                <Deferred data="groceryList" fallback={<GroceryListSkeleton />}>
                    <GroceryListContent
                        groceryList={groceryList!}
                        onToggleItem={handleToggleItem}
                        onRegenerate={handleRegenerate}
                        isRegenerating={regenerateForm.processing}
                    />
                </Deferred>
            </div>
        </AppLayout>
    );
}

function GroceryListSkeleton() {
    return (
        <div className="space-y-6">
            {/* Loading Alert */}
            <Alert className="border-primary/30 bg-primary/5">
                <Loader2 className="h-4 w-4 animate-spin text-primary" />
                <AlertTitle className="text-primary">
                    Generating Your Grocery List
                </AlertTitle>
                <AlertDescription className="text-muted-foreground">
                    Our AI is consolidating ingredients from your meal plan.
                    This usually takes a few seconds.
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
}

function GroceryListContent({
    groceryList,
    onToggleItem,
    onRegenerate,
    isRegenerating,
}: GroceryListContentProps) {
    const progress =
        groceryList.total_items > 0
            ? (groceryList.checked_items / groceryList.total_items) * 100
            : 0;

    const categories = Object.keys(groceryList.items_by_category);

    return (
        <>
            {/* Progress */}
            <div className="space-y-2">
                <div className="flex items-center justify-between text-sm">
                    <span className="text-muted-foreground">
                        Shopping progress
                    </span>
                    <span className="font-medium">
                        {groceryList.checked_items} of {groceryList.total_items}{' '}
                        items
                    </span>
                </div>
                <Progress value={progress} className="h-2" />
            </div>

            {/* Status Alerts */}
            {groceryList.status === GroceryStatus.Completed && (
                <Alert className="border-green-500/30 bg-green-500/5">
                    <Sparkles className="h-4 w-4 text-green-600" />
                    <AlertTitle className="text-green-700 dark:text-green-400">
                        Shopping Complete! 🎉
                    </AlertTitle>
                    <AlertDescription className="text-green-600 dark:text-green-300">
                        You've checked off all items on your grocery list.
                    </AlertDescription>
                </Alert>
            )}

            {groceryList.status === GroceryStatus.Failed && (
                <Alert className="border-red-500/30 bg-red-500/5">
                    <AlertTitle className="text-red-700 dark:text-red-400">
                        Generation Failed
                    </AlertTitle>
                    <AlertDescription className="text-red-600 dark:text-red-300">
                        Something went wrong while generating your grocery list.
                        Please try regenerating.
                    </AlertDescription>
                </Alert>
            )}

            {/* Grocery Items by Category */}
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
                                        <li
                                            key={item.id}
                                            className="flex items-center gap-3"
                                        >
                                            <Checkbox
                                                id={`item-${item.id}`}
                                                checked={item.is_checked}
                                                onCheckedChange={() =>
                                                    onToggleItem(item)
                                                }
                                            />
                                            <label
                                                htmlFor={`item-${item.id}`}
                                                className={`flex-1 cursor-pointer text-sm ${
                                                    item.is_checked
                                                        ? 'text-muted-foreground line-through'
                                                        : ''
                                                }`}
                                            >
                                                <span className="font-medium">
                                                    {item.name}
                                                </span>
                                                <span className="ml-2 text-muted-foreground">
                                                    {item.quantity}
                                                </span>
                                            </label>
                                        </li>
                                    ))}
                                </ul>
                            </CardContent>
                        </Card>
                    );
                })}
            </div>

            {/* Empty State */}
            {categories.length === 0 && (
                <div className="flex flex-col items-center justify-center rounded-lg border border-dashed p-12 text-center">
                    <ShoppingCart className="mb-4 h-12 w-12 text-muted-foreground" />
                    <h3 className="text-lg font-semibold">No Items Yet</h3>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Your grocery list is empty. This might happen if your
                        meal plan doesn't have any ingredients yet.
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
                        Try Regenerating
                    </Button>
                </div>
            )}
        </>
    );
}
