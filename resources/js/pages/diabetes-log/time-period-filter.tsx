import { Button } from '@/components/ui/button';
import { Calendar, CalendarDays, CalendarRange } from 'lucide-react';

export type TimePeriod = '7d' | '30d' | '90d';

interface Props {
    selected: TimePeriod;
    onChange: (period: TimePeriod) => void;
}

const periods: Array<{
    value: TimePeriod;
    label: string;
    icon: typeof Calendar;
}> = [
    { value: '7d', label: 'Last 7 Days', icon: Calendar },
    { value: '30d', label: 'Last 30 Days', icon: CalendarDays },
    { value: '90d', label: 'Last 90 Days', icon: CalendarRange },
];

export default function TimePeriodFilter({ selected, onChange }: Props) {
    return (
        <div className="flex flex-wrap gap-2">
            {periods.map((period) => {
                const Icon = period.icon;
                const isSelected = selected === period.value;

                return (
                    <Button
                        key={period.value}
                        variant={isSelected ? 'default' : 'outline'}
                        size="sm"
                        onClick={() => onChange(period.value)}
                        className="gap-2"
                    >
                        <Icon className="size-4" />
                        {period.label}
                    </Button>
                );
            })}
        </div>
    );
}
