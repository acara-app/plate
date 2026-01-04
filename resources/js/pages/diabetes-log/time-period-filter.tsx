import DashboardDiabetesLogController from '@/actions/App/Http/Controllers/Diabetes/DashboardDiabetesLogController';
import { Button } from '@/components/ui/button';
import type { DiabetesTrackingPageProps, TimePeriod } from '@/types/diabetes';
import { Link, usePage } from '@inertiajs/react';
import { Calendar, CalendarDays, CalendarRange } from 'lucide-react';
import { useTranslation } from 'react-i18next';

const getPeriods = (
    t: (key: string) => string,
): Array<{
    value: TimePeriod;
    label: string;
    icon: typeof Calendar;
}> => [
    {
        value: '7d',
        label: t('diabetes_log.tracking_page.time_periods.7d'),
        icon: Calendar,
    },
    {
        value: '30d',
        label: t('diabetes_log.tracking_page.time_periods.30d'),
        icon: CalendarDays,
    },
    {
        value: '90d',
        label: t('diabetes_log.tracking_page.time_periods.90d'),
        icon: CalendarRange,
    },
];

export default function TimePeriodFilter() {
    const { timePeriod } = usePage<DiabetesTrackingPageProps>().props;
    const { t } = useTranslation('common');
    const periods = getPeriods(t);

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
