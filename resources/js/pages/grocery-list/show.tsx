import {
    store as generateGroceryList,
    show as showGroceryList,
    toggleItem,
} from '@/actions/App/Http/Controllers/GroceryListController';
import printGroceryList from '@/actions/App/Http/Controllers/PrintGroceryListController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import mealPlans from '@/routes/meal-plans';
import { type BreadcrumbItem } from '@/types';
import {
    GroceryStatus,
    type GroceryItem,
    type GroceryList,
    type MealPlanSummary,
} from '@/types/grocery-list';
import { Head, Link, router, useForm, usePoll } from '@inertiajs/react';
import {
    ArrowLeft,
    Loader2,
    Printer,
    RefreshCw,
    ShoppingCart,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import {
    EmptyGroceryListState,
    GroceryListContent,
    GroceryListSkeleton,
    type ViewMode,
} from './elements';

interface GroceryListPageProps {
    mealPlan: MealPlanSummary;
    groceryList: GroceryList | null;
}

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

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-hidden p-4 md:p-6">
                {/* Header */}
                <div className="flex flex-col gap-4 rounded-xl border bg-card p-4 shadow-sm sm:flex-row sm:items-start sm:justify-between md:p-5">
                    <div className="space-y-2">
                        <div className="flex items-center gap-2">
                            <Button
                                variant="ghost"
                                size="icon"
                                aria-label={t('grocery_list.back_to_meal_plan')}
                                asChild
                            >
                                <Link href={mealPlans.index().url}>
                                    <ArrowLeft className="h-4 w-4" />
                                </Link>
                            </Button>
                            <Badge variant="default" className="gap-1.5">
                                <ShoppingCart className="h-3.5 w-3.5" />
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
                    <div className="grid gap-2 sm:flex sm:items-center">
                        <Button
                            variant="outline"
                            size="sm"
                            className="min-h-10 w-full sm:w-auto"
                            onClick={handleRegenerate}
                            disabled={regenerateForm.processing || isGenerating}
                        >
                            {regenerateForm.processing || isGenerating ? (
                                <Loader2 className="h-4 w-4 animate-spin" />
                            ) : (
                                <RefreshCw className="h-4 w-4" />
                            )}
                            {hasNoList
                                ? t('grocery_list.generate')
                                : t('grocery_list.regenerate')}
                        </Button>
                        {groceryList && (
                            <Button
                                variant="outline"
                                size="sm"
                                className="min-h-10 w-full sm:w-auto"
                                asChild
                                disabled={isGenerating}
                            >
                                <a
                                    href={printGroceryList(mealPlan.id).url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <Printer className="h-4 w-4" />
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
