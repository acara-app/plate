import { defineCatalog } from '@json-render/core';
import { schema } from '@json-render/react/schema';
import { z } from 'zod';

const tone = z.enum(['good', 'warn', 'danger', 'info']);

export const brewBuddyCatalog = defineCatalog(schema, {
    components: {
        Stack: {
            props: z.object({}),
            slots: ['default'],
            description: 'Vertical stack container — root layout for the plan.',
        },
        Hero: {
            props: z.object({
                title: z.string(),
                subtitle: z.string(),
            }),
            description: 'Plan headline. Always the first block.',
        },
        Stat: {
            props: z.object({
                label: z.string(),
                value: z.string(),
                tone,
            }),
            description: 'KPI stat (total mg, % of safe ceiling, sleep buffer).',
        },
        DrinkCard: {
            props: z.object({
                name: z.string(),
                volume_oz: z.number(),
                caffeine_mg: z.number(),
                time_hint: z.string(),
                reason: z.string(),
            }),
            description: 'A recommended drink from the catalog with timing and rationale.',
        },
        Timeline: {
            props: z.object({
                slots: z.array(
                    z.object({
                        time_label: z.string(),
                        label: z.string(),
                        caffeine_mg: z.number(),
                    }),
                ),
            }),
            description: 'Chronological day timeline of caffeine intake.',
        },
        Tip: {
            props: z.object({
                title: z.string(),
                body: z.string(),
            }),
            description: 'Short behavior nudge.',
        },
        Warning: {
            props: z.object({
                title: z.string(),
                body: z.string(),
            }),
            description: 'Risk callout (e.g. exceeds safe dose, after sleep cutoff).',
        },
    },
    actions: {},
});
