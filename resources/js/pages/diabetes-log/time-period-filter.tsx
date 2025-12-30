import DashboardDiabetesLogController from '@/actions/App/Http/Controllers/Diabetes/DashboardDiabetesLogController';
import { Button } from '@/components/ui/button';
import type { DiabetesTrackingPageProps, TimePeriod } from '@/types/diabetes';
import { Link, usePage } from '@inertiajs/react';
import { Calendar, CalendarDays, CalendarRange } from 'lucide-react';

const periods: Array<{
    value: TimePeriod;
    label: string;
    icon: typeof Calendar;
}> = [
    { value: '7d', label: 'Last 7 Days', icon: Calendar },
    { value: '30d', label: 'Last 30 Days', icon: CalendarDays },
    { value: '90d', label: 'Last 90 Days', icon: CalendarRange },
];

export default function TimePeriodFilter() {
    const { timePeriod } = usePage<DiabetesTrackingPageProps>().props;

    return (
        <div className="flex flex-wrap gap-2">
            {periods.map((period) => {
                const Icon = period.icon;
                const isSelected = timePeriod === period.value;

                return (
                    <Button
                        key={period.value}
                        variant={isSelected ? 'default' : 'outline'}
                        size="sm"
                        className="gap-2"
                        asChild
                    >
                        <Link
                            href={DashboardDiabetesLogController.url({
                                query: { period: period.value },
                            })}
                            preserveScroll
                        >
                            <Icon className="size-4" />
                            {period.label}
                        </Link>
                    </Button>
                );
            })}
        </div>
    );
}
