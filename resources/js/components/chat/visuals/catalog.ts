import { defineCatalog } from '@json-render/core';
import { schema } from '@json-render/react/schema';
import { z } from 'zod';
import { ACARA_CHAT_CATALOG } from './catalog-handshake';

export { ACARA_CHAT_CATALOG };

export const VISUAL_LIMITS = {
    bytes: 131072,
    elements: 128,
    depth: 12,
    pointsPerSeries: 366,
    visualsPerReply: 6,
    patchesPerVisual: 2048,
} as const;

export const VISUAL_ICONS = [
    'steps',
    'sleep',
    'glucose',
    'heart',
    'meal',
    'water',
    'weight',
    'workout',
    'energy',
    'medication',
] as const;

export const VISUAL_TINTS = [
    'brand',
    'positive',
    'caution',
    'critical',
    'info',
    'neutral',
    ...VISUAL_ICONS,
] as const;

export const VISUAL_TONES = [
    'info',
    'positive',
    'caution',
    'critical',
] as const;

export const VISUAL_DESTINATIONS = ['snap', 'log', 'sync'] as const;

const text = z.string().regex(/\S/);
const optionalText = z.string().nullish();
const tone = z.enum(VISUAL_TONES);
const gap = z.enum(['none', 'sm', 'md', 'lg']);
const quantity = z.union([z.number(), z.string()]);
const amount = z.number().nonnegative();
const uiPath = z.string().startsWith('/ui/');

const sendMessageParams = z.object({ text });
const openDestinationParams = z.object({
    destination: z.enum(VISUAL_DESTINATIONS),
});
const openLinkParams = z.object({ url: z.string().startsWith('https://') });

const appAction = z.discriminatedUnion('action', [
    z.object({ action: z.literal('sendMessage'), params: sendMessageParams }),
    z.object({
        action: z.literal('openDestination'),
        params: openDestinationParams,
    }),
    z.object({ action: z.literal('openLink'), params: openLinkParams }),
]);

const chartProps = z.object({
    title: text,
    data: z
        .array(
            z.record(z.string(), z.union([z.string(), z.number(), z.null()])),
        )
        .max(VISUAL_LIMITS.pointsPerSeries),
    xKey: text,
    yKey: text,
    unit: text,
    goal: z.number().nullish(),
    range: z.object({ low: z.number(), high: z.number() }).nullish(),
    dayBoundary: optionalText,
    tint: optionalText,
});

const week = [
    { day: '2026-11-10', steps: 4200, sleep: 6.5 },
    { day: '2026-11-11', steps: 0, sleep: 7 },
    { day: '2026-11-12', steps: null, sleep: null },
    { day: '2026-11-13', steps: 6100, sleep: 7.4 },
];

export const acaraChatComponents = {
    Card: {
        props: z.object({
            title: optionalText,
            subtitle: optionalText,
            icon: optionalText,
            tint: optionalText,
        }),
        slots: ['default'],
        description:
            'The surface for one visual. A Card inside a Card renders as a titled section.',
        example: {
            title: 'Movement this week',
            subtitle: 'November 10–16',
            icon: 'steps',
            tint: 'brand',
        },
    },
    Stack: {
        props: z.object({
            direction: z.enum(['vertical', 'horizontal']).nullish(),
            gap: gap.nullish(),
            align: z.enum(['start', 'center', 'end', 'stretch']).nullish(),
        }),
        slots: ['default'],
        description: 'Lays out its children in a column, or in a row.',
        example: { direction: 'vertical', gap: 'sm', align: 'start' },
    },
    Grid: {
        props: z.object({
            columns: z.number().int().min(1).max(3).nullish(),
            gap: gap.nullish(),
        }),
        slots: ['default'],
        description:
            'Lays out its children in one to three columns. It collapses to one column on narrow screens.',
        example: { columns: 2, gap: 'md' },
    },
    Separator: {
        props: z.object({}),
        description: 'A thin divider between sections.',
        example: {},
    },
    Heading: {
        props: z.object({
            text,
            level: z.enum(['h1', 'h2', 'h3', 'h4']).nullish(),
        }),
        description: 'A section heading.',
        example: { text: 'Weekly review', level: 'h2' },
    },
    Text: {
        props: z.object({
            text,
            variant: z.enum(['body', 'lead', 'caption', 'muted']).nullish(),
        }),
        description: 'A short paragraph or caption.',
        example: {
            text: 'A recorded zero and a day without a reading appear separately.',
            variant: 'caption',
        },
    },
    Badge: {
        props: z.object({ text, tone: tone.nullish() }),
        description: 'A short status label.',
        example: { text: 'On track', tone: 'positive' },
    },
    Callout: {
        props: z.object({
            title: optionalText,
            message: text,
            tone: tone.nullish(),
        }),
        description:
            'A note set apart from the prose. Use the caution or critical tone for warnings.',
        example: {
            title: 'Missing day',
            message: 'No reading was available for Thursday.',
            tone: 'caution',
        },
    },
    Metric: {
        props: z.object({
            label: text,
            value: quantity,
            unit: optionalText,
            detail: optionalText,
            trend: z.enum(['up', 'down', 'neutral']).nullish(),
            icon: optionalText,
            tint: optionalText,
            data: z
                .array(z.number().nullable())
                .max(VISUAL_LIMITS.pointsPerSeries)
                .nullish(),
        }),
        description:
            'A headline number with its label and unit. data is an optional sparkline in which null is a missing reading.',
        example: {
            label: 'Daily average',
            value: 5400,
            unit: 'steps',
            detail: 'Last 7 days',
            trend: 'up',
            icon: 'steps',
            tint: 'steps',
            data: [4200, 0, null, 6100],
        },
    },
    Progress: {
        props: z.object({
            label: text,
            value: z.number(),
            goal: z.number().positive(),
            unit: optionalText,
            style: z.enum(['bar', 'ring']).nullish(),
        }),
        description: 'Progress toward a goal, drawn as a bar or a ring.',
        example: {
            label: 'Daily movement',
            value: 5400,
            goal: 7000,
            unit: 'steps',
            style: 'ring',
        },
    },
    List: {
        props: z.object({ title: optionalText }),
        slots: ['default'],
        description: 'A list of ListItem children with an optional title.',
        example: { title: 'Activities' },
    },
    ListItem: {
        props: z.object({
            title: text,
            subtitle: optionalText,
            icon: optionalText,
            tint: optionalText,
            trailing: optionalText,
        }),
        slots: ['default'],
        description: 'One row of a List, with an optional trailing value.',
        example: {
            title: 'Walk',
            subtitle: 'Wednesday afternoon',
            icon: 'workout',
            tint: 'brand',
            trailing: '20 min',
        },
    },
    Steps: {
        props: z.object({
            items: z
                .array(
                    z.object({
                        title: text,
                        detail: optionalText,
                        duration: optionalText,
                    }),
                )
                .min(1),
        }),
        description:
            'Numbered steps to follow in order, each with an optional duration.',
        example: {
            items: [
                {
                    title: 'Warm up',
                    detail: 'Walk at an easy pace.',
                    duration: '5 min',
                },
                { title: 'Brisk walk', duration: '10 min' },
            ],
        },
    },
    Comparison: {
        props: z.object({
            columns: z.array(text).min(2).max(3),
            rows: z
                .array(
                    z.object({
                        label: text,
                        values: z.array(quantity).min(2).max(3),
                    }),
                )
                .min(1),
            winner: z.number().int().min(0).max(2).nullish(),
        }),
        description:
            'Two or three options side by side, row by row. winner is the zero-based index of the better column.',
        example: {
            columns: ['Oats', 'White rice'],
            rows: [
                { label: 'Fiber', values: ['4 g', '1 g'] },
                { label: 'Glycemic index', values: [55, 73] },
            ],
            winner: 0,
        },
    },
    BarChart: {
        props: chartProps,
        description:
            'Bars for a daily or categorical series. A null or missing y value is a missing reading, drawn differently from a recorded zero.',
        example: {
            title: 'Daily steps',
            data: week,
            xKey: 'day',
            yKey: 'steps',
            unit: 'steps',
            goal: 7000,
            dayBoundary: 'Days run midnight to midnight UTC.',
            tint: 'steps',
        },
    },
    LineChart: {
        props: chartProps,
        description:
            'A line for a series over time, with an optional goal line and target range band. A null y value is a gap.',
        example: {
            title: 'Sleep',
            data: week,
            xKey: 'day',
            yKey: 'sleep',
            unit: 'h',
            range: { low: 7, high: 9 },
            tint: 'sleep',
        },
    },
    MacroBreakdown: {
        props: z.object({
            calories: amount,
            carbs: amount,
            protein: amount,
            fat: amount,
            fiber: amount.nullish(),
        }),
        description:
            'Calories with carbohydrate, protein and fat grams for one meal or one day.',
        example: { calories: 410, carbs: 50, protein: 21, fat: 14, fiber: 5 },
    },
    RiskMeter: {
        props: z.object({
            level: z.enum(['low', 'medium', 'high']),
            label: text,
            detail: optionalText,
        }),
        description: 'A low, medium or high risk verdict with its reason.',
        example: {
            level: 'medium',
            label: 'Blood sugar spike',
            detail: 'White rice on its own digests quickly.',
        },
    },
    Segmented: {
        props: z.object({
            options: z.array(z.object({ label: text, value: text })).min(2),
            value: text,
        }),
        description:
            'Switches what a visual shows. Bind value to a /ui path with $bindState and give each switched element a visible condition on that path.',
        example: {
            options: [
                { label: 'Steps', value: 'steps' },
                { label: 'Sleep', value: 'sleep' },
            ],
            value: 'steps',
        },
    },
    Disclosure: {
        props: z.object({ title: text }),
        slots: ['default'],
        description: 'Detail tucked behind a title until it is opened.',
        example: { title: 'About these readings' },
    },
    Checklist: {
        props: z.object({
            items: z.array(z.object({ id: text, label: text })).min(1),
            checked: z.array(z.string()),
        }),
        description:
            'Items the person can tick off during this session. Bind checked to a /ui path with $bindState.',
        example: {
            items: [
                { id: 'oats', label: 'Oats' },
                { id: 'berries', label: 'Berries' },
            ],
            checked: ['oats'],
        },
    },
    Button: {
        props: z.object({ label: text }),
        description:
            'A tap target. Put its action in the on.press field of the element.',
        example: { label: 'Open logbook' },
    },
    SuggestionChips: {
        props: z.object({
            items: z.array(z.object({ label: text, action: appAction })).min(1),
        }),
        description:
            'Up to four follow-ups the person can tap after reading the reply.',
        example: {
            items: [
                {
                    label: 'Review my sleep',
                    action: {
                        action: 'sendMessage',
                        params: { text: 'Review my sleep this week.' },
                    },
                },
                {
                    label: 'Open sync',
                    action: {
                        action: 'openDestination',
                        params: { destination: 'sync' },
                    },
                },
            ],
        },
    },
};

export const acaraChatActions = {
    sendMessage: {
        params: sendMessageParams,
        description:
            'Sends the text as the person’s next message, exactly as if they had typed it.',
    },
    openDestination: {
        params: openDestinationParams,
        description:
            'Opens Snap (photo logging), Log (the logbook) or Sync (health sync).',
    },
    openLink: {
        params: openLinkParams,
        description: 'Opens an https page outside the chat.',
    },
};

export const acaraChatLocalActions = {
    setState: {
        params: z.object({ statePath: uiPath, value: z.unknown() }),
        description: 'Sets a value under /ui.',
    },
    pushState: {
        params: z.object({
            statePath: uiPath,
            value: z.unknown(),
            clearStatePath: uiPath.nullish(),
        }),
        description: 'Appends a value to an array under /ui.',
    },
    removeState: {
        params: z.object({
            statePath: uiPath,
            index: z.number().int().nonnegative(),
        }),
        description: 'Removes the item at index from an array under /ui.',
    },
};

export const COMPONENT_VERSIONS = {
    Card: 1,
    Stack: 1,
    Grid: 1,
    Separator: 1,
    Heading: 1,
    Text: 1,
    Badge: 1,
    Callout: 1,
    Metric: 1,
    Progress: 1,
    List: 1,
    ListItem: 1,
    Steps: 1,
    Comparison: 1,
    BarChart: 1,
    LineChart: 1,
    MacroBreakdown: 1,
    RiskMeter: 1,
    Segmented: 1,
    Disclosure: 1,
    Checklist: 1,
    Button: 1,
    SuggestionChips: 1,
} satisfies Record<keyof typeof acaraChatComponents, number>;

export const ACTION_VERSIONS = {
    sendMessage: 1,
    openDestination: 1,
    openLink: 1,
    setState: 1,
    pushState: 1,
    removeState: 1,
} satisfies Record<
    keyof typeof acaraChatActions | keyof typeof acaraChatLocalActions,
    number
>;

export const acaraChatCatalog = defineCatalog(schema, {
    components: acaraChatComponents,
    actions: acaraChatActions,
});
