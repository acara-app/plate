import ListHealthEntryController from '@/actions/App/Http/Controllers/HealthEntry/ListHealthEntryController';
import { Button } from '@/components/ui/button';
import {
    addDaysToKey,
    parseDateKey,
    startOfWeekKey,
} from '@/lib/format-local-datetime';
import { cn } from '@/lib/utils';
import { Link, router } from '@inertiajs/react';
import { CalendarDays, ChevronLeft, ChevronRight } from 'lucide-react';
import { useRef } from 'react';
import { useTranslation } from 'react-i18next';

const DAY_PROPS = ['date', 'today', 'newEntryAt', 'entries'];

export default function WeekStrip({
    date,
    today,
    loggedDates,
    onLoadingChange,
}: {
    date: string;
    today: string;
    loggedDates: string[];
    onLoadingChange: (loading: boolean) => void;
}) {
    const { t, i18n } = useTranslation('common');
    const locale = i18n.resolvedLanguage;
    const pickerRef = useRef<HTMLInputElement>(null);
    const weekStart = startOfWeekKey(date);
    const days = Array.from({ length: 7 }, (_, index) =>
        addDaysToKey(weekStart, index),
    );
    const canGoForward = weekStart < startOfWeekKey(today);
    const nextWeekDay = addDaysToKey(date, 7);
    const dayVisit = {
        only: DAY_PROPS,
        preserveScroll: true,
        onStart: () => onLoadingChange(true),
        onFinish: () => onLoadingChange(false),
    };
    const visit = { ...dayVisit, only: [...DAY_PROPS, 'loggedDates'] };
    const dayUrl = (day: string) =>
        ListHealthEntryController({ query: { date: day } });

    const openPicker = () => {
        const picker = pickerRef.current;

        if (!picker) {
            return;
        }

        try {
            picker.showPicker();
        } catch {
            picker.focus();
        }
    };

    return (
        <nav
            aria-label={t('health_entries.logbook.week_label')}
            className="flex flex-col gap-3"
        >
            <div className="flex items-center justify-between gap-2">
                <div className="relative">
                    <Button
                        type="button"
                        variant="ghost"
                        className="-ml-3 h-11 gap-2 rounded-full px-3 text-base font-semibold capitalize"
                        onClick={openPicker}
                        aria-label={t('health_entries.logbook.pick_date')}
                    >
                        <CalendarDays
                            className="size-5 text-primary"
                            aria-hidden="true"
                        />
                        {parseDateKey(date).toLocaleDateString(locale, {
                            month: 'long',
                            year: 'numeric',
                        })}
                    </Button>
                    <input
                        ref={pickerRef}
                        type="date"
                        tabIndex={-1}
                        aria-hidden="true"
                        className="pointer-events-none absolute inset-0 opacity-0"
                        value={date}
                        max={today}
                        onChange={(event) =>
                            event.target.value &&
                            router.visit(dayUrl(event.target.value), visit)
                        }
                    />
                </div>
                <div className="flex items-center gap-1">
                    {date !== today && (
                        <Button
                            asChild
                            variant="outline"
                            size="sm"
                            className="h-9 rounded-full px-3"
                        >
                            <Link href={dayUrl(today)} {...visit}>
                                {t('health_entries.logbook.today')}
                            </Link>
                        </Button>
                    )}
                    <Button
                        asChild
                        variant="ghost"
                        size="icon"
                        className="size-11 rounded-full"
                    >
                        <Link
                            href={dayUrl(addDaysToKey(date, -7))}
                            aria-label={t(
                                'health_entries.logbook.previous_week',
                            )}
                            {...visit}
                        >
                            <ChevronLeft aria-hidden="true" />
                        </Link>
                    </Button>
                    {canGoForward ? (
                        <Button
                            asChild
                            variant="ghost"
                            size="icon"
                            className="size-11 rounded-full"
                        >
                            <Link
                                href={dayUrl(
                                    nextWeekDay > today ? today : nextWeekDay,
                                )}
                                aria-label={t(
                                    'health_entries.logbook.next_week',
                                )}
                                {...visit}
                            >
                                <ChevronRight aria-hidden="true" />
                            </Link>
                        </Button>
                    ) : (
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-11 rounded-full"
                            disabled
                            aria-label={t('health_entries.logbook.next_week')}
                        >
                            <ChevronRight aria-hidden="true" />
                        </Button>
                    )}
                </div>
            </div>
            <ol className="grid grid-cols-7">
                {days.map((day) => {
                    const value = parseDateKey(day);
                    const isSelected = day === date;
                    const isToday = day === today;
                    const isFuture = day > today;
                    const isLogged = loggedDates.includes(day);
                    const content = (
                        <>
                            <span
                                className={cn(
                                    'flex size-11 items-center justify-center rounded-full text-lg font-semibold tabular-nums transition-colors',
                                    isSelected
                                        ? 'bg-primary text-primary-foreground shadow-[0_0_16px_rgba(16,185,129,0.35)]'
                                        : isToday
                                          ? 'text-primary ring-1 ring-primary/40'
                                          : 'group-hover:bg-muted',
                                )}
                            >
                                {value.getDate()}
                            </span>
                            <span
                                className={cn(
                                    'text-xs capitalize',
                                    isSelected
                                        ? 'font-medium text-primary'
                                        : 'text-muted-foreground',
                                )}
                            >
                                {value.toLocaleDateString(locale, {
                                    weekday: 'short',
                                })}
                            </span>
                            <span
                                aria-hidden="true"
                                className={cn(
                                    'size-1.5 rounded-full',
                                    isLogged ? 'bg-primary' : 'bg-transparent',
                                )}
                            />
                        </>
                    );
                    const label = [
                        value.toLocaleDateString(locale, {
                            weekday: 'long',
                            month: 'long',
                            day: 'numeric',
                        }),
                        isLogged ? t('health_entries.logbook.has_entries') : '',
                    ]
                        .filter(Boolean)
                        .join(', ');

                    return (
                        <li key={day} className="flex justify-center">
                            {isFuture ? (
                                <span
                                    aria-disabled="true"
                                    aria-label={label}
                                    className="flex flex-col items-center gap-1 py-1 opacity-40"
                                >
                                    {content}
                                </span>
                            ) : (
                                <Link
                                    href={dayUrl(day)}
                                    aria-label={label}
                                    aria-current={
                                        isSelected ? 'date' : undefined
                                    }
                                    className="group flex flex-col items-center gap-1 rounded-2xl px-1 py-1 outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    {...dayVisit}
                                >
                                    {content}
                                </Link>
                            )}
                        </li>
                    );
                })}
            </ol>
        </nav>
    );
}
