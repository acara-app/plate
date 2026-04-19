import { OnboardingBanner } from '@/components/onboarding-banner';
import { Badge } from '@/components/ui/badge';
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
import { generateUUID } from '@/lib/utils';
import { dashboard } from '@/routes';
import chat from '@/routes/chat';
import healthEntries from '@/routes/health-entries';
import integrations from '@/routes/integrations';
import mealPlans from '@/routes/meal-plans';
import mobileSync from '@/routes/mobile-sync';
import { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    ChevronRight,
    Droplets,
    History,
    MessageSquare,
    Send,
    Smartphone,
    Sparkles,
    TrendingUp,
    Utensils,
} from 'lucide-react';
import { useTranslation } from 'react-i18next';

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

                <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {/* AI Chat Card - Altani */}
                    <Card className="group relative flex flex-col overflow-hidden transition-all hover:shadow-lg">
                        <div className="absolute inset-x-0 top-0 h-1 bg-linear-to-r from-emerald-500 via-emerald-400 to-teal-400" />
                        <CardHeader className="pb-3">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <div className="relative flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full ring-2 ring-emerald-100">
                                        <img
                                            src="https://pub-plate-assets.acara.app/images/altani-waving-hello-320.webp"
                                            alt="Altani"
                                            className="h-full w-full object-cover"
                                        />
                                    </div>
                                    <div>
                                        <CardTitle className="text-lg">
                                            {t('dashboard_cards.chat.title')}
                                        </CardTitle>
                                        <Badge
                                            variant="secondary"
                                            className="mt-1 bg-emerald-100 text-xs text-emerald-700 hover:bg-emerald-100"
                                        >
                                            24/7 Available
                                        </Badge>
                                    </div>
                                </div>
                                <Link
                                    href={chat.index().url}
                                    className="rounded-md p-1.5 text-muted-foreground transition-colors hover:bg-emerald-50 hover:text-emerald-600"
                                >
                                    <History className="h-5 w-5" />
                                </Link>
                            </div>
                            <CardDescription className="mt-3 text-sm leading-relaxed">
                                {t('dashboard_cards.chat.description')}
                            </CardDescription>
                        </CardHeader>

                        <CardContent className="flex flex-1 flex-col gap-4">
                            {/* Quick Actions */}
                            <div className="grid grid-cols-2 gap-2">
                                <Link
                                    href={`${chat.create(generateUUID()).url}?mode=ask`}
                                    className="group/action"
                                >
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="h-auto w-full flex-col items-start justify-start gap-1 p-3 text-left transition-colors group-hover/action:border-emerald-500 group-hover/action:bg-emerald-50"
                                    >
                                        <MessageSquare className="h-4 w-4 text-emerald-600" />
                                        <span className="text-xs font-medium">
                                            {t(
                                                'dashboard_cards.chat.quick_actions.ask',
                                            )}
                                        </span>
                                    </Button>
                                </Link>
                                <Link
                                    href={`${chat.create(generateUUID()).url}?mode=create-meal-plan`}
                                    className="group/action"
                                >
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="h-auto w-full flex-col items-start justify-start gap-1 p-3 text-left transition-colors group-hover/action:border-emerald-500 group-hover/action:bg-emerald-50"
                                    >
                                        <Utensils className="h-4 w-4 text-emerald-600" />
                                        <span className="text-xs font-medium">
                                            {t(
                                                'dashboard_cards.chat.quick_actions.meal_plan',
                                            )}
                                        </span>
                                    </Button>
                                </Link>
                            </div>

                            {/* Main CTA */}
                            <div className="mt-auto pt-2">
                                <Link href={chat.create(generateUUID()).url}>
                                    <Button className="w-full gap-2 bg-emerald-600 hover:bg-emerald-500">
                                        <Sparkles className="h-4 w-4" />
                                        {t('dashboard_cards.chat.button')}
                                    </Button>
                                </Link>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Telegram Integration Card - High Priority */}
                    <Link href={integrations.edit().url} className="group">
                        <Card className="relative h-full overflow-hidden transition-all hover:border-blue-500/50 hover:shadow-md">
                            <div className="absolute inset-0 bg-linear-to-br from-blue-50/50 via-transparent to-transparent opacity-50 dark:from-blue-900/10" />
                            <CardContent className="relative flex h-full flex-col justify-between p-5">
                                <div className="flex items-start justify-between">
                                    <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400">
                                        <Send className="h-5 w-5" />
                                    </div>
                                    <Badge
                                        variant="secondary"
                                        className="bg-blue-100 text-blue-700 hover:bg-blue-100 dark:bg-blue-500/20 dark:text-blue-400"
                                    >
                                        Beta
                                    </Badge>
                                </div>
                                <div>
                                    <h3 className="font-semibold text-blue-950 dark:text-blue-50">
                                        {t('dashboard_cards.telegram.title')}
                                    </h3>
                                    <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                                        {t(
                                            'dashboard_cards.telegram.description',
                                        )}
                                    </p>
                                    <div className="mt-4 flex items-center gap-2 text-sm font-medium text-blue-600 dark:text-blue-400">
                                        {t('dashboard_cards.telegram.cta')}
                                        <ArrowRight className="h-4 w-4 transition-transform group-hover:translate-x-1" />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </Link>

                    {/* Mobile Sync Card */}
                    <Link href={mobileSync.edit().url} className="group">
                        <Card className="h-full transition-all hover:border-violet-500/50 hover:shadow-md">
                            <CardContent className="flex h-full flex-col justify-between p-5">
                                <div className="flex items-start justify-between">
                                    <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-violet-100 dark:bg-violet-900/20">
                                        <Smartphone className="h-5 w-5 text-violet-600 dark:text-violet-400" />
                                    </div>
                                    <ChevronRight className="h-4 w-4 text-muted-foreground transition-transform group-hover:translate-x-1" />
                                </div>
                                <div>
                                    <h3 className="font-semibold">
                                        {t('dashboard_cards.mobile_sync.title')}
                                    </h3>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {t(
                                            'dashboard_cards.mobile_sync.description',
                                        )}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </Link>

                    {/* My Menu Card */}
                    <Link href={mealPlans.index().url} className="group">
                        <Card className="h-full transition-all hover:border-primary/50 hover:shadow-md">
                            <CardContent className="flex h-full flex-col justify-between p-5">
                                <div className="flex items-start justify-between">
                                    <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-orange-100 dark:bg-orange-900/20">
                                        <Utensils className="h-5 w-5 text-orange-600 dark:text-orange-400" />
                                    </div>
                                    <ChevronRight className="h-4 w-4 text-muted-foreground transition-transform group-hover:translate-x-1" />
                                </div>
                                <div>
                                    <h3 className="font-semibold">
                                        {t('dashboard_cards.meal_plans.title')}
                                    </h3>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {t(
                                            'dashboard_cards.meal_plans.description',
                                        )}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </Link>

                    {/* My Trends Card */}
                    <Link href={healthEntries.insights().url} className="group">
                        <Card className="h-full transition-all hover:border-primary/50 hover:shadow-md">
                            <CardContent className="flex h-full flex-col justify-between p-5">
                                <div className="flex items-start justify-between">
                                    <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/20">
                                        <TrendingUp className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <ChevronRight className="h-4 w-4 text-muted-foreground transition-transform group-hover:translate-x-1" />
                                </div>
                                <div>
                                    <h3 className="font-semibold">
                                        {t(
                                            'dashboard_cards.health_insights.title',
                                        )}
                                    </h3>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {t(
                                            'dashboard_cards.health_insights.description',
                                        )}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </Link>

                    {/* Logbook Card */}
                    <Link
                        href={healthEntries.dashboard().url}
                        className="group"
                    >
                        <Card className="h-full transition-all hover:border-primary/50 hover:shadow-md">
                            <CardContent className="flex h-full flex-col justify-between p-5">
                                <div className="flex items-start justify-between">
                                    <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-red-100 dark:bg-red-900/20">
                                        <Droplets className="h-5 w-5 text-red-600 dark:text-red-400" />
                                    </div>
                                    <ChevronRight className="h-4 w-4 text-muted-foreground transition-transform group-hover:translate-x-1" />
                                </div>
                                <div>
                                    <h3 className="font-semibold">
                                        {t(
                                            'dashboard_cards.health_entries.title',
                                        )}
                                    </h3>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {t(
                                            'dashboard_cards.health_entries.description',
                                        )}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </Link>
                </div>
            </div>
        </AppLayout>
    );
}
