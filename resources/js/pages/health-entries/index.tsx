import ListHealthEntryController from '@/actions/App/Http/Controllers/HealthEntry/ListHealthEntryController';
import ShowSnapToTrackController from '@/actions/App/Http/Controllers/SnapToTrack/ShowSnapToTrackController';
import { Button } from '@/components/ui/button';
import useModalToggle from '@/hooks/use-modal-toggle';
import AppLayout from '@/layouts/app-layout';
import {
    addDaysToKey,
    formatLocalDatetime,
    parseDateKey,
} from '@/lib/format-local-datetime';
import { cn, generateUUID } from '@/lib/utils';
import chat from '@/routes/chat';
import { type BreadcrumbItem } from '@/types';
import {
    DailyNutritionGoals,
    DailyWellness,
    HealthEntry,
    ReadingType,
    RecentInsulin,
    RecentMedication,
    TodaysMeal,
} from '@/types/diabetes';
import { Head, Link, router } from '@inertiajs/react';
import { BookOpen, Camera, Plus, Sparkles } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import CalorieGoalCard from './components/calorie-goal-card';
import DailyTrackers from './components/daily-trackers';
import EntryCard from './components/entry-card';
import MacroGoals from './components/macro-goals';
import { sumNutrition } from './components/macros';
import WeekStrip from './components/week-strip';
import WellnessCards from './components/wellness-cards';
import HealthEntriesDialog from './health-entries-dialog';

interface Props {
    date: string;
    today: string;
    newEntryAt: string | null;
    entries: HealthEntry[];
    loggedDates: string[];
    goals: DailyNutritionGoals | null;
    wellness: DailyWellness;
    glucoseReadingTypes: ReadingType[];
    insulinTypes: ReadingType[];
    glucoseUnit: string;
    recentMedications?: RecentMedication[];
    recentInsulins?: RecentInsulin[];
    todaysMeals?: TodaysMeal[];
}

export default function HealthEntriesIndex({
    date,
    today,
    newEntryAt,
    entries,
    loggedDates,
    goals,
    wellness,
    glucoseReadingTypes,
    insulinTypes,
    glucoseUnit,
    recentMedications,
    recentInsulins,
    todaysMeals,
}: Props) {
    const { t, i18n } = useTranslation('common');
    const createModal = useModalToggle();
    const [loading, setLoading] = useState(false);
    const isToday = date === today;
    const totals = sumNutrition(entries);
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t('health_entries.index_page.title'),
            href: ListHealthEntryController().url,
        },
    ];
    const dayHeading = isToday
        ? t('health_entries.logbook.today_entries')
        : parseDateKey(date).toLocaleDateString(i18n.resolvedLanguage, {
              weekday: 'long',
              month: 'long',
              day: 'numeric',
          });

    function askAi() {
        router.visit(
            chat.create(generateUUID(), {
                query: {
                    prompt: t('health_entries.index_page.review_prompt', {
                        startDate: addDaysToKey(date, -6),
                        endDate: date,
                    }),
                    auto_send: false,
                },
            }),
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('health_entries.index_page.title')} />
            <div className="mx-auto flex w-full max-w-2xl min-w-0 flex-col gap-6 px-4 pt-4 pb-16 sm:px-6 sm:pt-6">
                <header className="flex items-center justify-between gap-3">
                    <h1 className="sr-only">
                        {t('health_entries.index_page.title')}
                    </h1>
                    <Button
                        type="button"
                        variant="secondary"
                        onClick={askAi}
                        className="h-11 rounded-full px-4"
                    >
                        <Sparkles data-icon="inline-start" aria-hidden="true" />
                        {t('health_entries.index_page.ask_ai')}
                    </Button>
                    <Button asChild className="h-11 rounded-full px-4">
                        <Link href={ShowSnapToTrackController()}>
                            <Camera aria-hidden="true" />
                            {t('health_entries.timeline.scan_meal')}
                        </Link>
                    </Button>
                </header>
                <WeekStrip
                    date={date}
                    today={today}
                    loggedDates={loggedDates}
                    onLoadingChange={setLoading}
                />
                <div
                    aria-busy={loading}
                    className={cn(
                        'flex flex-col gap-6 transition-opacity',
                        loading && 'opacity-60',
                    )}
                >
                    <CalorieGoalCard
                        calories={totals.calories ?? 0}
                        goals={goals}
                    />
                    <WellnessCards date={date} wellness={wellness} />
                    <MacroGoals totals={totals} goals={goals} />
                    <DailyTrackers
                        date={date}
                        isToday={isToday}
                        wellness={wellness}
                    />
                    <section
                        aria-labelledby="day-entries-heading"
                        className="flex flex-col gap-4"
                    >
                        <div className="flex items-center justify-between gap-3">
                            <h2
                                id="day-entries-heading"
                                className="text-xl font-semibold tracking-tight first-letter:uppercase"
                            >
                                {dayHeading}
                            </h2>
                            <Button
                                variant="ghost"
                                className="-mr-3 h-11 rounded-full px-3 text-base text-primary hover:text-primary"
                                onClick={createModal.open}
                            >
                                <Plus className="size-5" aria-hidden="true" />
                                {t('health_entries.logbook.add')}
                            </Button>
                        </div>
                        {entries.length === 0 ? (
                            <div className="flex flex-col items-center gap-4 rounded-3xl bg-card px-6 py-12 text-center ring-1 ring-border/60">
                                <span className="flex size-14 items-center justify-center rounded-2xl bg-primary/10">
                                    <BookOpen
                                        className="size-7 text-primary"
                                        aria-hidden="true"
                                    />
                                </span>
                                <div className="flex flex-col gap-1">
                                    <p className="font-semibold">
                                        {t(
                                            isToday
                                                ? 'health_entries.logbook.empty_today'
                                                : 'health_entries.logbook.empty_day',
                                        )}
                                    </p>
                                    <p className="max-w-xs text-sm text-muted-foreground">
                                        {t(
                                            'health_entries.logbook.empty_description',
                                        )}
                                    </p>
                                </div>
                                <div className="flex flex-wrap justify-center gap-2">
                                    <Button
                                        asChild
                                        className="h-11 rounded-full px-5"
                                    >
                                        <Link
                                            href={ShowSnapToTrackController()}
                                        >
                                            <Camera aria-hidden="true" />
                                            {t(
                                                'health_entries.timeline.scan_meal',
                                            )}
                                        </Link>
                                    </Button>
                                    <Button
                                        variant="outline"
                                        className="h-11 rounded-full px-5"
                                        onClick={createModal.open}
                                    >
                                        <Plus aria-hidden="true" />
                                        {t(
                                            'health_entries.index_page.add_entry',
                                        )}
                                    </Button>
                                </div>
                            </div>
                        ) : (
                            <ul className="flex flex-col gap-3">
                                {entries.map((entry) => (
                                    <li key={entry.id}>
                                        <EntryCard
                                            entry={entry}
                                            glucoseUnit={glucoseUnit}
                                        />
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                </div>
            </div>
            <HealthEntriesDialog
                mode="create"
                open={createModal.isOpen}
                onOpenChange={(open) =>
                    open ? createModal.open() : createModal.close()
                }
                defaultMeasuredAt={
                    newEntryAt === null
                        ? undefined
                        : formatLocalDatetime(new Date(newEntryAt))
                }
                glucoseReadingTypes={glucoseReadingTypes}
                insulinTypes={insulinTypes}
                glucoseUnit={glucoseUnit}
                recentMedications={recentMedications}
                recentInsulins={recentInsulins}
                todaysMeals={todaysMeals}
            />
        </AppLayout>
    );
}
