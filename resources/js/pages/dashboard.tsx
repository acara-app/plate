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
import foodLog from '@/routes/food-log';
import glucoseReading from '@/routes/glucose';
import glucoseAction from '@/routes/glucose-action';
import mealPlans from '@/routes/meal-plans';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Home',
        href: dashboard().url,
    },
];

export default function Dashboard() {
    const { currentUser } = useSharedProps();

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-6">
                {!currentUser?.is_onboarded && <OnboardingBanner />}

                <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {/* Glucose Insights Card */}
                    <Card className="flex flex-col transition-shadow hover:shadow-md">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <span className="text-2xl">📊</span>
                                Glucose Insights
                            </CardTitle>
                            <CardDescription>
                                View your glucose analysis, trends, and
                                personalized recommendations
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="mt-auto">
                            <Link href={glucoseAction.show().url}>
                                <Button className="w-full">
                                    View Insights
                                </Button>
                            </Link>
                        </CardContent>
                    </Card>

                    {/* Glucose Tracking Card */}
                    <Card className="flex flex-col transition-shadow hover:shadow-md">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <span className="text-2xl">🩸</span>
                                Glucose Tracking
                            </CardTitle>
                            <CardDescription>
                                Monitor and track your blood glucose levels
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="mt-auto">
                            <Link href={glucoseReading.dashboard().url}>
                                <Button className="w-full">
                                    Track Glucose
                                </Button>
                            </Link>
                        </CardContent>
                    </Card>

                    {/* Weekly Meal Plans Card */}
                    <Card className="flex flex-col transition-shadow hover:shadow-md">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <span className="text-2xl">🍽️</span>
                                Weekly Meal Plans
                            </CardTitle>
                            <CardDescription>
                                View and manage your personalized weekly meal
                                plans
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="mt-auto">
                            <Link href={mealPlans.index().url}>
                                <Button className="w-full">
                                    View Meal Plans
                                </Button>
                            </Link>
                        </CardContent>
                    </Card>

                    {/* Chat Card */}
                    <Card className="flex flex-col transition-shadow hover:shadow-md">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <span className="text-2xl">💬</span>
                                Nutrition Chat
                            </CardTitle>
                            <CardDescription>
                                Get personalized nutrition advice and answers to
                                your questions
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="mt-auto">
                            <Link href={chat.create().url}>
                                <Button className="w-full">Start Chat</Button>
                            </Link>
                        </CardContent>
                    </Card>

                    {/* Food Log Card */}
                    <Card className="flex flex-col transition-shadow hover:shadow-md">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <span className="text-2xl">📝</span>
                                Food Log
                            </CardTitle>
                            <CardDescription>
                                Track your daily food intake and monitor your
                                progress
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="mt-auto">
                            <Link href={foodLog.create().url}>
                                <Button className="w-full">Log Food</Button>
                            </Link>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
