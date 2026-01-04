import { OnboardingBanner } from '@/components/onboarding-banner';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import useSharedProps from '@/hooks/use-shared-props';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import chat from '@/routes/chat';
import diabetesLog from '@/routes/diabetes-log';
import foodLog from '@/routes/food-log';
import mealPlans from '@/routes/meal-plans';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

// Breadcrumbs will be set dynamically using translation
const getBreadcrumbs = (t: (key: string) => string): BreadcrumbItem[] => [
    {
        title: t('home'),
        href: dashboard().url,
    },
];

export default function Dashboard() {
    const { t } = useTranslation('common');
    const breadcrumbs = getBreadcrumbs(t);

    const { currentUser } = useSharedProps();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('dashboard')} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6">
                {!currentUser?.is_onboarded && <OnboardingBanner />}

                <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {/* Diabetes Insights Card */}
                    <Card className="flex flex-col transition-shadow hover:shadow-md">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <span className="text-2xl">📊</span>
                                {t('dashboard_cards.diabetes_insights.title')}
                            </CardTitle>
                            <CardDescription>
                                {t(
                                    'dashboard_cards.diabetes_insights.description',
                                )}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="mt-auto">
                            <Link href={diabetesLog.insights().url}>
                                <Button className="w-full">
                                    {t(
                                        'dashboard_cards.diabetes_insights.button',
                                    )}
                                </Button>
                            </Link>
                        </CardContent>
                    </Card>

                    {/* Diabetes Log Card */}
                    <Card className="flex flex-col transition-shadow hover:shadow-md">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <span className="text-2xl">🩸</span>
                                {t('dashboard_cards.diabetes_log.title')}
                            </CardTitle>
                            <CardDescription>
                                {t('dashboard_cards.diabetes_log.description')}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="mt-auto">
                            <Link href={diabetesLog.dashboard().url}>
                                <Button className="w-full">
                                    {t('dashboard_cards.diabetes_log.button')}
                                </Button>
                            </Link>
                        </CardContent>
                    </Card>

                    {/* Weekly Meal Plans Card */}
                    <Card className="flex flex-col transition-shadow hover:shadow-md">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <span className="text-2xl">🍽️</span>
                                {t('dashboard_cards.meal_plans.title')}
                            </CardTitle>
                            <CardDescription>
                                {t('dashboard_cards.meal_plans.description')}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="mt-auto">
                            <Link href={mealPlans.index().url}>
                                <Button className="w-full">
                                    {t('dashboard_cards.meal_plans.button')}
                                </Button>
                            </Link>
                        </CardContent>
                    </Card>

                    {/* Chat Card */}
                    <Card className="flex flex-col transition-shadow hover:shadow-md">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <span className="text-2xl">💬</span>
                                {t('dashboard_cards.chat.title')}
                            </CardTitle>
                            <CardDescription>
                                {t('dashboard_cards.chat.description')}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="mt-auto">
                            <Link href={chat.create().url}>
                                <Button className="w-full">
                                    {t('dashboard_cards.chat.button')}
                                </Button>
                            </Link>
                        </CardContent>
                    </Card>

                    {/* Food Log Card */}
                    <Card className="flex flex-col transition-shadow hover:shadow-md">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <span className="text-2xl">📝</span>
                                {t('dashboard_cards.food_log.title')}
                            </CardTitle>
                            <CardDescription>
                                {t('dashboard_cards.food_log.description')}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="mt-auto">
                            <Link href={foodLog.create().url}>
                                <Button className="w-full">
                                    {t('dashboard_cards.food_log.button')}
                                </Button>
                            </Link>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
