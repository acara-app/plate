import ListHealthEntryController from '@/actions/App/Http/Controllers/HealthEntry/ListHealthEntryController';
import ListLoggedDatesController from '@/actions/App/Http/Controllers/HealthEntry/ListLoggedDatesController';
import { Button } from '@/components/ui/button';
import { addDaysToKey, parseDateKey } from '@/lib/format-local-datetime';
import { cn } from '@/lib/utils';
import { type Page } from '@inertiajs/core';
import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import useWeekScroll from './use-week-scroll';

const DAY_PROPS = [
    'date',
    'today',
    'newEntryAt',
    'entries',
    'exerciseSummary',
    'loggedDates',
    'wellness',
];
const LONG_DATE: Intl.DateTimeFormatOptions = {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
};
const dayUrl = (day: string) =>
    ListHealthEntryController({ query: { date: day } });

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
    const {
        currentWeek,
        selectedWeek,
        visibleWeek,
        weeks,
        showWeek,
        stripProps,
    } = useWeekScroll(date, today);
    const [weekLogs, setWeekLogs] = useState<Record<string, string[]>>({});
    const logsFor = (week: string) =>
        week === selectedWeek ? loggedDates : weekLogs[week];
    const visibleLogs = logsFor(visibleWeek);

    useEffect(() => {
        setWeekLogs((cache) => ({ ...cache, [selectedWeek]: loggedDates }));
    }, [selectedWeek, loggedDates]);

    useEffect(() => {
        if (visibleLogs) {
            return;
        }

        const request = new AbortController();

        fetch(ListLoggedDatesController.url({ query: { date: visibleWeek } }), {
            headers: { Accept: 'application/json' },
            signal: request.signal,
        })
            .then((response) =>
                response.ok ? response.json() : Promise.reject(response),
            )
            .then((dates: string[]) =>
                setWeekLogs((cache) => ({ ...cache, [visibleWeek]: dates })),
            )
            .catch(() => undefined);

        return () => request.abort();
    }, [visibleWeek, visibleLogs]);

    const visit = {
        only: DAY_PROPS,
        preserveScroll: true,
        preserveState: true,
        onStart: () => onLoadingChange(true),
        onFinish: () => onLoadingChange(false),
        onSuccess: (page: Page) => showWeek(page.props.date as string),
    };

    return (
        <nav
            aria-label={t('health_entries.logbook.week_label')}
            className="flex min-w-0 flex-col gap-1"
        >
            <p className="sr-only" aria-live="polite" aria-atomic="true">
                {parseDateKey(visibleWeek).toLocaleDateString(
                    locale,
                    LONG_DATE,
                )}
                {' – '}
                {parseDateKey(addDaysToKey(visibleWeek, 6)).toLocaleDateString(
                    locale,
                    LONG_DATE,
                )}
            </p>
            <div
                {...stripProps}
                id="health-entry-weeks"
                role="group"
                aria-label={t('health_entries.logbook.week_label')}
                data-week-start={visibleWeek}
                tabIndex={0}
                className="no-scrollbar flex cursor-grab snap-x snap-mandatory overflow-x-auto overscroll-x-contain rounded-2xl py-2 outline-none select-none focus-visible:ring-[3px] focus-visible:ring-ring/50 active:cursor-grabbing"
            >
                {weeks.map((week) => (
                    <ol
                        key={week}
                        aria-hidden={week !== visibleWeek}
                        className="grid w-full shrink-0 snap-start snap-always grid-cols-7"
                    >
                        {Array.from({ length: 7 }, (_, index) =>
                            addDaysToKey(week, index),
                        ).map((day) => {
                            const value = parseDateKey(day);
                            const isSelected = day === date;
                            const isToday = day === today;
                            const isFuture = day > today;
                            const isLogged =
                                logsFor(week)?.includes(day) ?? false;
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
                                            isLogged
                                                ? 'bg-primary'
                                                : 'bg-transparent',
                                        )}
                                    />
                                </>
                            );
                            const label = [
                                value.toLocaleDateString(locale, {
                                    weekday: 'long',
                                    ...LONG_DATE,
                                }),
                                isLogged
                                    ? t('health_entries.logbook.has_entries')
                                    : '',
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
                                            tabIndex={
                                                week === visibleWeek
                                                    ? undefined
                                                    : -1
                                            }
                                            className="group flex flex-col items-center gap-1 rounded-2xl px-1 py-1 outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                            {...visit}
                                        >
                                            {content}
                                        </Link>
                                    )}
                                </li>
                            );
                        })}
                    </ol>
                ))}
            </div>
            {(date !== today || visibleWeek !== currentWeek) && (
                <div className="flex justify-end">
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
                </div>
            )}
        </nav>
    );
}
