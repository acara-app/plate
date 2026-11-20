import { defineCatalog } from '@json-render/core';
import { createRenderer } from '@json-render/react';
import { schema } from '@json-render/react/schema';
import { useTranslation } from 'react-i18next';
import { z } from 'zod';

const day = z.strictObject({
    date: z.iso.date(),
    steps: z.int().nonnegative().nullable(),
});
const weeklyStepsProps = z.strictObject({
    days: z
        .array(day)
        .length(7)
        .refine((days) =>
            days.every(
                (day, i) =>
                    i === 0 ||
                    Date.parse(day.date) - Date.parse(days[i - 1].date) ===
                        86_400_000,
            ),
        ),
});

const chatCatalog = defineCatalog(schema, {
    components: {
        WeeklyStepsChart: {
            props: weeklyStepsProps,
            description:
                'Recorded daily step counts for seven consecutive UTC dates.',
        },
    },
    actions: {},
});

// The read-only chat catalog accepts one static leaf and no state or actions.
export const weeklyStepsSpec = z.strictObject({
    root: z.literal('weekly-steps'),
    elements: z.strictObject({
        'weekly-steps': z.strictObject({
            type: z.literal('WeeklyStepsChart'),
            props: weeklyStepsProps,
        }),
    }),
});

function WeeklyStepsChart({ days }: z.infer<typeof weeklyStepsProps>) {
    const { t, i18n } = useTranslation('common');
    const locale = i18n.resolvedLanguage ?? i18n.language;
    const dateFormat = new Intl.DateTimeFormat(locale, {
        month: 'short',
        day: 'numeric',
        weekday: 'short',
        timeZone: 'UTC',
    });
    const numberFormat = new Intl.NumberFormat(locale);
    const max = Math.max(1, ...days.map((day) => day.steps ?? 0));

    return (
        <figure className="rounded-2xl border border-border bg-card px-4 py-4 text-card-foreground">
            <figcaption className="mb-4 text-sm font-semibold">
                {t('chat.weekly_steps.title')}
            </figcaption>
            <ol
                className="grid grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-x-3 gap-y-2.5 text-xs tabular-nums"
                role="list"
            >
                {days.map(({ date, steps }) => (
                    <li
                        key={date}
                        className="col-span-full grid grid-cols-subgrid items-center"
                    >
                        <time dateTime={date}>
                            {dateFormat.format(new Date(date))}
                        </time>
                        <span
                            aria-hidden="true"
                            className={
                                steps === null
                                    ? 'border-t border-dashed border-border'
                                    : 'h-2 overflow-hidden rounded-full bg-muted'
                            }
                        >
                            {steps !== null && (
                                <span
                                    className="block h-full rounded-full bg-emerald-600 dark:bg-emerald-400"
                                    style={{ width: (steps / max) * 100 + '%' }}
                                />
                            )}
                        </span>
                        <span className="text-end">
                            {steps === null
                                ? t('chat.weekly_steps.no_reading')
                                : numberFormat.format(steps)}
                        </span>
                    </li>
                ))}
            </ol>
        </figure>
    );
}

export const ChatVisual = createRenderer(chatCatalog, {
    WeeklyStepsChart: ({ element }) => <WeeklyStepsChart {...element.props} />,
});
