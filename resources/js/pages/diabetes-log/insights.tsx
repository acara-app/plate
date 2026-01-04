import { store as regenerateMealPlan } from '@/actions/App/Http/Controllers/RegenerateMealPlanController';
import { OnboardingBanner } from '@/components/onboarding-banner';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
import { index as mealPlansIndex } from '@/routes/meal-plans';
import onboarding from '@/routes/onboarding';
import { type BreadcrumbItem } from '@/types';
import { type GlucoseAnalysisData } from '@/types/diabetes';
import { type MealPlan } from '@/types/meal-plan';
import { Head, Link, useForm } from '@inertiajs/react';
import {
    Activity,
    AlertTriangle,
    RefreshCw,
    Sparkles,
    TrendingUp,
} from 'lucide-react';
import { useTranslation } from 'react-i18next';

interface GlucoseActionProps {
    glucoseAnalysis: GlucoseAnalysisData;
    concerns: string[];
    hasMealPlan: boolean;
    mealPlan: MealPlan | null;
}

const getBreadcrumbs = (t: (key: string) => string): BreadcrumbItem[] => [
    { title: t('diabetes_log.insights_page.breadcrumb'), href: '#' },
];

export default function DiabetesInsights({
    glucoseAnalysis,
    concerns,
    hasMealPlan,
    mealPlan,
}: GlucoseActionProps) {
    const { currentUser } = useSharedProps();
    const { t } = useTranslation('common');

    const regenerateForm = useForm({});

    const handleGenerateNewPlan = () => {
        regenerateForm.post(regenerateMealPlan().url);
    };

    return (
        <AppLayout breadcrumbs={getBreadcrumbs(t)}>
            <Head title={t('diabetes_log.insights_page.title')} />

            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                {!currentUser?.is_onboarded ? (
                    <>
                        <div className="space-y-2">
                            <h1 className="flex items-center gap-2 text-3xl font-bold tracking-tight">
                                <Activity className="h-8 w-8 text-primary" />
                                {t(
                                    'diabetes_log.insights_page.your_glucose_insights',
                                )}
                            </h1>
                            <p className="text-muted-foreground">
                                {t(
                                    'diabetes_log.insights_page.complete_profile',
                                )}
                            </p>
                        </div>
                        <OnboardingBanner />
                    </>
                ) : (
                    <>
                        {/* Header */}
                        <div className="space-y-2">
                            <h1 className="flex items-center gap-2 text-3xl font-bold tracking-tight">
                                <Activity className="h-8 w-8 text-primary" />
                                {t(
                                    'diabetes_log.insights_page.your_glucose_insights',
                                )}
                            </h1>
                            <p className="text-muted-foreground">
                                {t(
                                    'diabetes_log.insights_page.analysis_from_days',
                                    { days: glucoseAnalysis.days_analyzed },
                                )}
                            </p>
                        </div>

                        {!glucoseAnalysis.has_data ? (
                            <Alert>
                                <AlertDescription>
                                    {t('diabetes_log.insights_page.no_data')}
                                </AlertDescription>
                            </Alert>
                        ) : (
                            <>
                                {/* Glucose Summary Card */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle>
                                            {t(
                                                'diabetes_log.insights_page.glucose_overview.title',
                                            )}
                                        </CardTitle>
                                        <CardDescription>
                                            {t(
                                                'diabetes_log.insights_page.glucose_overview.based_on_readings',
                                                {
                                                    count: glucoseAnalysis.total_readings,
                                                },
                                            )}
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="grid gap-4 md:grid-cols-3">
                                            <div className="space-y-1">
                                                <p className="text-sm text-muted-foreground">
                                                    {t(
                                                        'diabetes_log.insights_page.glucose_overview.average_glucose',
                                                    )}
                                                </p>
                                                <p className="text-2xl font-bold">
                                                    {glucoseAnalysis.averages.overall?.toFixed(
                                                        1,
                                                    ) ??
                                                        t(
                                                            'diabetes_log.insights_page.glucose_overview.na',
                                                        )}{' '}
                                                    {glucoseAnalysis.averages
                                                        .overall && (
                                                        <span className="text-sm font-normal">
                                                            {t(
                                                                'diabetes_log.insights_page.glucose_overview.mg_dl',
                                                            )}
                                                        </span>
                                                    )}
                                                </p>
                                            </div>
                                            <div className="space-y-1">
                                                <p className="text-sm text-muted-foreground">
                                                    {t(
                                                        'diabetes_log.insights_page.glucose_overview.time_in_range',
                                                    )}
                                                </p>
                                                <p className="text-2xl font-bold text-green-600">
                                                    {glucoseAnalysis.time_in_range.percentage.toFixed(
                                                        0,
                                                    )}
                                                    %
                                                </p>
                                            </div>
                                            <div className="space-y-1">
                                                <p className="text-sm text-muted-foreground">
                                                    {t(
                                                        'diabetes_log.insights_page.glucose_overview.above_range',
                                                    )}
                                                </p>
                                                <p className="text-2xl font-bold text-orange-600">
                                                    {glucoseAnalysis.time_in_range.above_percentage.toFixed(
                                                        0,
                                                    )}
                                                    %
                                                </p>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                {/* Concerns Alert */}
                                {concerns.length > 0 && (
                                    <Alert variant="destructive">
                                        <AlertTriangle className="h-4 w-4" />
                                        <AlertTitle>
                                            {t(
                                                'diabetes_log.insights_page.concerns.title',
                                            )}
                                        </AlertTitle>
                                        <AlertDescription>
                                            <ul className="mt-2 list-inside list-disc space-y-1">
                                                {concerns.map((concern, i) => (
                                                    <li key={i}>{concern}</li>
                                                ))}
                                            </ul>
                                        </AlertDescription>
                                    </Alert>
                                )}

                                {/* Action Section */}
                                {!hasMealPlan ? (
                                    <Card className="border-primary/50 bg-primary/5">
                                        <CardHeader>
                                            <CardTitle className="flex items-center gap-2">
                                                <TrendingUp className="h-5 w-5" />
                                                {t(
                                                    'diabetes_log.insights_page.improve_control.title',
                                                )}
                                            </CardTitle>
                                            <CardDescription>
                                                {t(
                                                    'diabetes_log.insights_page.improve_control.description',
                                                )}
                                            </CardDescription>
                                        </CardHeader>
                                        <CardContent className="space-y-4">
                                            <p className="text-sm">
                                                {t(
                                                    'diabetes_log.insights_page.improve_control.details',
                                                )}
                                            </p>
                                            <Button
                                                asChild
                                                size="lg"
                                                className="w-full sm:w-auto"
                                            >
                                                <Link
                                                    href={
                                                        onboarding.questionnaire.show()
                                                            .url
                                                    }
                                                >
                                                    <Sparkles className="mr-2 h-4 w-4" />
                                                    {t(
                                                        'diabetes_log.insights_page.improve_control.generate_button',
                                                    )}
                                                </Link>
                                            </Button>
                                        </CardContent>
                                    </Card>
                                ) : (
                                    <div className="space-y-4">
                                        <h2 className="text-xl font-semibold">
                                            {t(
                                                'diabetes_log.insights_page.recommended_actions.title',
                                            )}
                                        </h2>
                                        <div className="grid gap-4 md:grid-cols-2">
                                            <Card>
                                                <CardHeader>
                                                    <CardTitle className="text-lg">
                                                        {t(
                                                            'diabetes_log.insights_page.recommended_actions.view_plan.title',
                                                        )}
                                                    </CardTitle>
                                                    <CardDescription>
                                                        {t(
                                                            'diabetes_log.insights_page.recommended_actions.view_plan.description',
                                                            {
                                                                days: mealPlan?.duration_days,
                                                            },
                                                        )}
                                                    </CardDescription>
                                                </CardHeader>
                                                <CardContent>
                                                    <Button
                                                        asChild
                                                        variant="outline"
                                                        className="w-full"
                                                    >
                                                        <Link
                                                            href={
                                                                mealPlansIndex()
                                                                    .url
                                                            }
                                                        >
                                                            {t(
                                                                'diabetes_log.insights_page.recommended_actions.view_plan.button',
                                                            )}
                                                        </Link>
                                                    </Button>
                                                </CardContent>
                                            </Card>

                                            <Card className="border-primary/30">
                                                <CardHeader>
                                                    <CardTitle className="text-lg">
                                                        {t(
                                                            'diabetes_log.insights_page.recommended_actions.regenerate.title',
                                                        )}
                                                    </CardTitle>
                                                    <CardDescription>
                                                        {t(
                                                            'diabetes_log.insights_page.recommended_actions.regenerate.description',
                                                        )}
                                                    </CardDescription>
                                                </CardHeader>
                                                <CardContent>
                                                    <Button
                                                        onClick={
                                                            handleGenerateNewPlan
                                                        }
                                                        disabled={
                                                            regenerateForm.processing
                                                        }
                                                        className="w-full"
                                                    >
                                                        <RefreshCw
                                                            className={`mr-2 h-4 w-4 ${regenerateForm.processing ? 'animate-spin' : ''}`}
                                                        />
                                                        {regenerateForm.processing
                                                            ? t(
                                                                  'diabetes_log.insights_page.recommended_actions.regenerate.generating',
                                                              )
                                                            : t(
                                                                  'diabetes_log.insights_page.recommended_actions.regenerate.button',
                                                              )}
                                                    </Button>
                                                </CardContent>
                                            </Card>
                                        </div>
                                    </div>
                                )}

                                {/* Educational Footer */}
                                <Card className="bg-muted/30">
                                    <CardContent className="pt-6">
                                        <p
                                            className="text-sm text-muted-foreground"
                                            dangerouslySetInnerHTML={{
                                                __html: t(
                                                    'diabetes_log.insights_page.tip',
                                                ),
                                            }}
                                        />
                                    </CardContent>
                                </Card>
                            </>
                        )}
                    </>
                )}
            </div>
        </AppLayout>
    );
}
