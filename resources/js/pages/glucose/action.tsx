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
import { type GlucoseAnalysisData } from '@/types/glucose';
import { type MealPlan } from '@/types/meal-plan';
import { Head, Link, useForm } from '@inertiajs/react';
import {
    Activity,
    AlertTriangle,
    RefreshCw,
    Sparkles,
    TrendingUp,
} from 'lucide-react';

interface GlucoseActionProps {
    glucoseAnalysis: GlucoseAnalysisData;
    concerns: string[];
    hasMealPlan: boolean;
    mealPlan: MealPlan | null;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Glucose Insights', href: '#' },
];

export default function GlucoseAction({
    glucoseAnalysis,
    concerns,
    hasMealPlan,
    mealPlan,
}: GlucoseActionProps) {
    const { currentUser } = useSharedProps();

    const regenerateForm = useForm({});

    const handleGenerateNewPlan = () => {
        regenerateForm.post(regenerateMealPlan().url);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Your Glucose Insights" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                {!currentUser?.is_onboarded ? (
                    <>
                        <div className="space-y-2">
                            <h1 className="flex items-center gap-2 text-3xl font-bold tracking-tight">
                                <Activity className="h-8 w-8 text-primary" />
                                Your Glucose Insights
                            </h1>
                            <p className="text-muted-foreground">
                                Complete your profile to get personalized meal
                                recommendations
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
                                Your Glucose Insights
                            </h1>
                            <p className="text-muted-foreground">
                                Analysis from your past{' '}
                                {glucoseAnalysis.daysAnalyzed} days
                            </p>
                        </div>

                        {!glucoseAnalysis.hasData ? (
                            <Alert>
                                <AlertDescription>
                                    No glucose data available yet. Start
                                    tracking your glucose readings to get
                                    personalized insights.
                                </AlertDescription>
                            </Alert>
                        ) : (
                            <>
                                {/* Glucose Summary Card */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Glucose Overview</CardTitle>
                                        <CardDescription>
                                            Based on{' '}
                                            {glucoseAnalysis.totalReadings}{' '}
                                            readings
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="grid gap-4 md:grid-cols-3">
                                            <div className="space-y-1">
                                                <p className="text-sm text-muted-foreground">
                                                    Average Glucose
                                                </p>
                                                <p className="text-2xl font-bold">
                                                    {glucoseAnalysis.averages.overall?.toFixed(
                                                        1,
                                                    ) ?? 'N/A'}{' '}
                                                    {glucoseAnalysis.averages
                                                        .overall && (
                                                        <span className="text-sm font-normal">
                                                            mg/dL
                                                        </span>
                                                    )}
                                                </p>
                                            </div>
                                            <div className="space-y-1">
                                                <p className="text-sm text-muted-foreground">
                                                    Time in Range
                                                </p>
                                                <p className="text-2xl font-bold text-green-600">
                                                    {glucoseAnalysis.timeInRange.percentage.toFixed(
                                                        0,
                                                    )}
                                                    %
                                                </p>
                                            </div>
                                            <div className="space-y-1">
                                                <p className="text-sm text-muted-foreground">
                                                    Above Range
                                                </p>
                                                <p className="text-2xl font-bold text-orange-600">
                                                    {glucoseAnalysis.timeInRange.abovePercentage.toFixed(
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
                                            Areas Needing Attention
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
                                                Improve Your Glucose Control
                                            </CardTitle>
                                            <CardDescription>
                                                Generate a personalized meal
                                                plan designed to help stabilize
                                                your blood sugar levels
                                            </CardDescription>
                                        </CardHeader>
                                        <CardContent className="space-y-4">
                                            <p className="text-sm">
                                                Our AI will create meals
                                                tailored to your glucose
                                                patterns, focusing on foods that
                                                won&apos;t spike your blood
                                                sugar.
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
                                                    Generate Optimized Meal Plan
                                                </Link>
                                            </Button>
                                        </CardContent>
                                    </Card>
                                ) : (
                                    <div className="space-y-4">
                                        <h2 className="text-xl font-semibold">
                                            Recommended Actions
                                        </h2>
                                        <div className="grid gap-4 md:grid-cols-2">
                                            <Card>
                                                <CardHeader>
                                                    <CardTitle className="text-lg">
                                                        View Current Plan
                                                    </CardTitle>
                                                    <CardDescription>
                                                        Review your existing{' '}
                                                        {
                                                            mealPlan?.duration_days
                                                        }
                                                        -day meal plan
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
                                                            Go to Meal Plans
                                                        </Link>
                                                    </Button>
                                                </CardContent>
                                            </Card>

                                            <Card className="border-primary/30">
                                                <CardHeader>
                                                    <CardTitle className="text-lg">
                                                        Regenerate with Glucose
                                                        Focus
                                                    </CardTitle>
                                                    <CardDescription>
                                                        Create a new meal plan
                                                        optimized for your
                                                        current glucose patterns
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
                                                            ? 'Generating...'
                                                            : 'Regenerate Entire Plan'}
                                                    </Button>
                                                </CardContent>
                                            </Card>
                                        </div>
                                    </div>
                                )}

                                {/* Educational Footer */}
                                <Card className="bg-muted/30">
                                    <CardContent className="pt-6">
                                        <p className="text-sm text-muted-foreground">
                                            💡 <strong>Tip:</strong> Consistent
                                            meal timing and balanced
                                            macronutrients can help reduce
                                            glucose variability. Your
                                            personalized meal plan takes these
                                            factors into account.
                                        </p>
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
