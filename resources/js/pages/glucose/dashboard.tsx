import GlucoseReadingController from '@/actions/App/Http/Controllers/GlucoseReadingController';
import AdminPageWrap from '@/components/sections/admin-page-wrap';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import useModalToggle from '@/hooks/use-modal-toggle';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { List, Plus } from 'lucide-react';
import { useMemo, useState } from 'react';
import GlucoseChart from './glucose-chart';
import GlucoseReadingDialog from './glucose-reading-dialog';
import GlucoseStatistics from './glucose-statistics';
import TimePeriodFilter, { type TimePeriod } from './time-period-filter';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Glucose Tracking',
        href: GlucoseReadingController.index().url,
    },
    {
        title: 'Dashboard',
        href: GlucoseReadingController.dashboard().url,
    },
];

interface ReadingType {
    value: string;
    label: string;
}

interface GlucoseReading {
    id: number;
    reading_value: number;
    reading_type: string;
    measured_at: string;
    notes: string | null;
    created_at: string;
}

interface Props {
    readings: GlucoseReading[];
    readingTypes: ReadingType[];
}

function filterReadingsByPeriod(
    readings: GlucoseReading[],
    period: TimePeriod,
): GlucoseReading[] {
    const now = new Date();
    const daysMap: Record<TimePeriod, number> = {
        '7d': 7,
        '30d': 30,
        '90d': 90,
    };

    const days = daysMap[period];
    const cutoffDate = new Date(now);
    cutoffDate.setDate(cutoffDate.getDate() - days);

    return readings.filter((reading) => {
        const readingDate = new Date(reading.measured_at);
        return readingDate >= cutoffDate;
    });
}

export default function GlucoseDashboard({ readings, readingTypes }: Props) {
    const [timePeriod, setTimePeriod] = useState<TimePeriod>('30d');
    const createModal = useModalToggle();

    const filteredReadings = useMemo(
        () => filterReadingsByPeriod(readings, timePeriod),
        [readings, timePeriod],
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Glucose Dashboard" />
            <AdminPageWrap variant="full">
                <div className="space-y-6">
                    {/* Header */}
                    <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">
                                Glucose Dashboard
                            </h1>
                            <p className="text-muted-foreground">
                                Comprehensive analytics and trends for your
                                blood glucose levels
                            </p>
                        </div>
                        <div className="flex gap-2">
                            <Button variant="outline" size="icon" asChild>
                                <a href={GlucoseReadingController.index().url}>
                                    <List className="size-4" />
                                </a>
                            </Button>
                            <Button onClick={() => createModal.open()}>
                                <Plus className="mr-2 size-4" />
                                Add Reading
                            </Button>
                        </div>
                    </div>

                    {/* Add Reading Dialog */}
                    <GlucoseReadingDialog
                        mode="create"
                        open={createModal.isOpen}
                        onOpenChange={(open) =>
                            open ? createModal.open() : createModal.close()
                        }
                        readingTypes={readingTypes}
                    />

                    {/* Time Period Filter */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Time Period</CardTitle>
                            <CardDescription>
                                Select the time range for your analytics
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <TimePeriodFilter
                                selected={timePeriod}
                                onChange={setTimePeriod}
                            />
                        </CardContent>
                    </Card>

                    {/* Check if there are readings */}
                    {readings.length === 0 ? (
                        <Card>
                            <CardContent className="py-16 text-center">
                                <div className="mx-auto max-w-md space-y-4">
                                    <h3 className="text-lg font-semibold">
                                        No glucose readings yet
                                    </h3>
                                    <p className="text-muted-foreground">
                                        Start tracking your blood glucose levels
                                        to see comprehensive analytics and
                                        insights.
                                    </p>
                                    <Button onClick={() => createModal.open()}>
                                        <Plus className="mr-2 size-4" />
                                        Add Your First Reading
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    ) : filteredReadings.length === 0 ? (
                        <Card>
                            <CardContent className="py-16 text-center">
                                <div className="mx-auto max-w-md space-y-4">
                                    <h3 className="text-lg font-semibold">
                                        No readings in this period
                                    </h3>
                                    <p className="text-muted-foreground">
                                        Try selecting a different time period or
                                        add more readings.
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    ) : (
                        <>
                            {/* Statistics Cards */}
                            <GlucoseStatistics readings={filteredReadings} />

                            {/* Chart */}
                            <GlucoseChart readings={filteredReadings} />

                            {/* Summary Card */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Summary</CardTitle>
                                    <CardDescription>
                                        Key insights from your glucose readings
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-3 text-sm">
                                        <div className="flex justify-between border-b pb-2">
                                            <span className="text-muted-foreground">
                                                Total Readings
                                            </span>
                                            <span className="font-medium">
                                                {filteredReadings.length}
                                            </span>
                                        </div>
                                        <div className="flex justify-between border-b pb-2">
                                            <span className="text-muted-foreground">
                                                Time Period
                                            </span>
                                            <span className="font-medium">
                                                {timePeriod === '7d'
                                                    ? 'Last 7 Days'
                                                    : timePeriod === '30d'
                                                      ? 'Last 30 Days'
                                                      : 'Last 90 Days'}
                                            </span>
                                        </div>
                                        <div className="flex justify-between border-b pb-2">
                                            <span className="text-muted-foreground">
                                                Reading Types
                                            </span>
                                            <span className="font-medium">
                                                {
                                                    new Set(
                                                        filteredReadings.map(
                                                            (r) =>
                                                                r.reading_type,
                                                        ),
                                                    ).size
                                                }
                                            </span>
                                        </div>
                                        <div className="pt-2">
                                            <p className="text-xs text-muted-foreground">
                                                💡 <strong>Tip:</strong> Aim for
                                                at least 70% of readings within
                                                the normal range (70-140 mg/dL)
                                                for optimal glucose control.
                                            </p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </>
                    )}
                </div>
            </AdminPageWrap>
        </AppLayout>
    );
}
