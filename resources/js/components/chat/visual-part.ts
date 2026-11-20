import { z } from 'zod';

const visualPart = z.object({
    type: z.literal('data-spec'),
    id: z.string().min(1),
    data: z.object({
        spec: z.unknown(),
        fallbackText: z.string().trim().min(1),
    }),
});

export type ChatVisualPart = z.infer<typeof visualPart>;

export function parseVisualPart(value: unknown): ChatVisualPart | null {
    const parsed = visualPart.safeParse(value);

    return parsed.success ? parsed.data : null;
}
