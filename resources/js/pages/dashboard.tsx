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
import { dashboard } from '@/routes';
import chat from '@/routes/chat';
import diabetesLog from '@/routes/diabetes-log';
import mealPlans from '@/routes/meal-plans';
import biometrics from '@/routes/onboarding/biometrics';
import { BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    Activity,
    ChevronRight,
    Droplets,
    MessageSquare,
    Sparkles,
    TrendingUp,
    Utensils,
} from 'lucide-react';
import { useTranslation } from 'react-i18next';

interface DashboardProps {
    recentConversations: Array<{
        id: string;
        title: string;
        updated_at: string;
    }>;
    hasGlucoseData: boolean;
    hasHealthConditions: boolean;
}

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
    const { recentConversations, hasGlucoseData, hasHealthConditions } =
        usePage().props as unknown as DashboardProps;

    const getContextualPrompt = () => {
        if (!currentUser?.is_onboarded) {
            return {
                text: t('dashboard_cards.chat.prompts.complete_profile'),
                link: biometrics.show().url,
            };
        }
        if (hasGlucoseData) {
            return {
                text: t('dashboard_cards.chat.prompts.analyze_glucose'),
                link: chat.create().url,
            };
        }
        if (hasHealthConditions) {
            return {
                text: t('dashboard_cards.chat.prompts.health_conditions'),
                link: chat.create().url,
            };
        }
        return {
            text: t('dashboard_cards.chat.prompts.restaurant'),
            link: chat.create().url,
        };
    };

    const contextualPrompt = getContextualPrompt();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('dashboard')} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6">
                {!currentUser?.is_onboarded && <OnboardingBanner />}

                {/* Modular Grid Layout */}
                <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {/* AI Chat Card */}
                    <Card className="group relative flex flex-col overflow-hidden transition-all hover:shadow-lg">
                        <div className="absolute inset-x-0 top-0 h-1 bg-linear-to-r from-primary via-primary/80 to-primary/60" />
                        <CardHeader className="pb-3">
                            <div className="flex items-center gap-3">
                                <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10">
                                    <Sparkles className="h-6 w-6 text-primary" />
                                </div>
                                <div>
                                    <CardTitle className="text-lg">
                                        {t('dashboard_cards.chat.title')}
                                    </CardTitle>
                                    <Badge
                                        variant="secondary"
                                        className="mt-1 text-xs"
                                    >
                                        AI-Powered
                                    </Badge>
                                </div>
                            </div>
                            <CardDescription className="mt-3 text-sm leading-relaxed">
                                {t('dashboard_cards.chat.description')}
                            </CardDescription>
                        </CardHeader>

                        <CardContent className="flex flex-1 flex-col gap-4">
                            {/* Quick Actions */}
                            <div className="grid grid-cols-2 gap-2">
                                <Link
                                    href={`${chat.create().url}?mode=ask`}
                                    className="group/action"
                                >
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="h-auto w-full flex-col items-start justify-start gap-1 p-3 text-left transition-colors group-hover/action:border-primary group-hover/action:bg-primary/5"
                                    >
                                        <MessageSquare className="h-4 w-4 text-primary" />
                                        <span className="text-xs font-medium">
                                            {t(
                                                'dashboard_cards.chat.quick_actions.ask',
                                            )}
                                        </span>
                                    </Button>
                                </Link>
                                <Link
                                    href={`${chat.create().url}?mode=generate-meal-plan`}
                                    className="group/action"
                                >
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="h-auto w-full flex-col items-start justify-start gap-1 p-3 text-left transition-colors group-hover/action:border-primary group-hover/action:bg-primary/5"
                                    >
                                        <Utensils className="h-4 w-4 text-primary" />
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
                                <Link href={chat.create().url}>
                                    <Button className="w-full gap-2">
                                        <Sparkles className="h-4 w-4" />
                                        {t('dashboard_cards.chat.button')}
                                    </Button>
                                </Link>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Contextual Prompt Card */}
                    <Link href={contextualPrompt.link} className="group">
                        <Card className="h-full transition-all hover:border-primary/50 hover:shadow-md">
                            <CardContent className="flex h-full flex-col justify-between p-5">
                                <div className="flex items-start justify-between">
                                    <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/20">
                                        <Activity className="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                                    </div>
                                    <ChevronRight className="h-4 w-4 text-muted-foreground transition-transform group-hover:translate-x-1" />
                                </div>
                                <div>
                                    <h3 className="font-semibold text-emerald-700 dark:text-emerald-400">
                                        Quick Action
                                    </h3>
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        {contextualPrompt.text}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </Link>

                    {/* Recent Conversations Card */}
                    {recentConversations.length > 0 && (
                        <Card className="flex flex-col">
                            <CardHeader className="pb-3">
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <MessageSquare className="h-4 w-4 text-primary" />
                                    Recent Conversations
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="flex-1">
                                <div className="space-y-2">
                                    {recentConversations.map((conv) => (
                                        <Link
                                            key={conv.id}
                                            href={chat.create(conv.id).url}
                                            className="group flex items-center justify-between rounded-md px-2 py-2 text-sm transition-colors hover:bg-muted"
                                        >
                                            <span className="truncate text-muted-foreground group-hover:text-foreground">
                                                {conv.title}
                                            </span>
                                            <span className="ml-2 shrink-0 text-xs text-muted-foreground">
                                                {conv.updated_at}
                                            </span>
                                        </Link>
                                    ))}
                                </div>
                                <div className="mt-4 border-t pt-3">
                                    <Link href={chat.create().url}>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="w-full text-xs"
                                        >
                                            View All Conversations
                                        </Button>
                                    </Link>
                                </div>
                            </CardContent>
                        </Card>
                    )}

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
                    <Link href={diabetesLog.insights().url} className="group">
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
                                            'dashboard_cards.diabetes_insights.title',
                                        )}
                                    </h3>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {t(
                                            'dashboard_cards.diabetes_insights.description',
                                        )}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </Link>

                    {/* Logbook Card */}
                    <Link href={diabetesLog.dashboard().url} className="group">
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
                                            'dashboard_cards.diabetes_log.title',
                                        )}
                                    </h3>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {t(
                                            'dashboard_cards.diabetes_log.description',
                                        )}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </Link>

                    {/* Integrations Card */}
                    <Link href="/settings/integrations" className="group">
                        <Card className="h-full transition-all hover:border-primary/50 hover:shadow-md">
                            <CardContent className="flex h-full flex-col justify-between p-5">
                                <div className="flex items-start justify-between">
                                    <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/20">
                                        <MessageSquare className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <ChevronRight className="h-4 w-4 text-muted-foreground transition-transform group-hover:translate-x-1" />
                                </div>
                                <div>
                                    <h3 className="font-semibold">
                                        Integrations
                                    </h3>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        Connect Telegram and other messaging
                                        apps
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
