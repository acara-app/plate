import { defineCatalog } from '@json-render/core';
import { schema } from '@json-render/react/schema';
import { z } from 'zod';

const tone = z.enum(['green', 'amber', 'red', 'slate']);

export const caffeineGuidanceCatalog = defineCatalog(schema, {
    components: {
        Stack: {
            props: z.object({}),
            slots: ['default'],
            description: 'Vertical result stack.',
        },
        VerdictCard: {
            props: z.object({
                title: z.string(),
                body: z.string(),
                badge: z.string(),
                tone,
                limit_mg: z.number().nullable(),
            }),
            description: 'Primary caffeine limit verdict.',
        },
        LimitGauge: {
            props: z.object({
                label: z.string(),
                value_label: z.string(),
                limit_mg: z.number().nullable(),
                max_mg: z.number(),
                tone,
                caption: z.string(),
            }),
            description: 'Visual caffeine limit meter.',
        },
        GuidanceList: {
            props: z.object({
                title: z.string(),
                items: z.array(z.string()),
            }),
            description: 'Short practical next steps.',
        },
        ContextNote: {
            props: z.object({
                title: z.string(),
                body: z.string(),
            }),
            description:
                'Personal context note when context changes the recommendation.',
        },
        SafetyNote: {
            props: z.object({
                title: z.string(),
                body: z.string(),
                items: z.array(z.string()),
            }),
            description: 'Compact medical safety note.',
        },
    },
    actions: {},
});
