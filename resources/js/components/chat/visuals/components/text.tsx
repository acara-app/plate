import { cn } from '@/lib/utils';
import { CircleAlert, CircleCheck, Info, TriangleAlert } from 'lucide-react';
import type { ViewProps } from './props';
import { TONES } from './style';

const HEADINGS = {
    h1: { tag: 'h3', size: 'text-lg' },
    h2: { tag: 'h4', size: 'text-base' },
    h3: { tag: 'h5', size: 'text-[15px]' },
    h4: { tag: 'h6', size: 'text-sm text-muted-foreground' },
} as const;

const TEXT = {
    body: 'text-[15px] leading-6 text-foreground/90',
    lead: 'text-base leading-7 text-foreground',
    caption: 'text-[13px] leading-5 text-muted-foreground',
    muted: 'text-sm leading-6 text-muted-foreground',
} as const;

const CALLOUT_ICONS = {
    info: Info,
    positive: CircleCheck,
    caution: TriangleAlert,
    critical: CircleAlert,
} as const;

export function Heading({ props }: ViewProps<'Heading'>) {
    const { tag: Tag, size } = HEADINGS[props.level ?? 'h2'];

    return (
        <Tag
            className={cn(
                'font-semibold tracking-tight text-balance text-foreground',
                size,
            )}
        >
            {props.text}
        </Tag>
    );
}

export function Text({ props }: ViewProps<'Text'>) {
    return (
        <p
            className={cn(
                '[overflow-wrap:anywhere] whitespace-pre-line',
                TEXT[props.variant ?? 'body'],
            )}
        >
            {props.text}
        </p>
    );
}

export function Badge({ props }: ViewProps<'Badge'>) {
    return (
        <span
            className={cn(
                'inline-flex w-fit items-center rounded-full px-2.5 py-0.5 text-xs leading-5 font-medium',
                props.tone
                    ? TONES[props.tone].badge
                    : 'bg-foreground/[0.06] text-foreground/80',
            )}
        >
            {props.text}
        </span>
    );
}

export function Callout({ props }: ViewProps<'Callout'>) {
    const tone = props.tone ?? 'info';
    const Icon = CALLOUT_ICONS[tone];

    return (
        <div
            role="note"
            className={cn('flex gap-3 rounded-2xl p-4', TONES[tone].surface)}
        >
            <Icon
                className={cn('mt-1 size-4 shrink-0', TONES[tone].text)}
                aria-hidden="true"
            />
            <div className="min-w-0 text-sm leading-6">
                {props.title && (
                    <p className="font-medium text-foreground">{props.title}</p>
                )}
                <p className="whitespace-pre-line text-foreground/80">
                    {props.message}
                </p>
            </div>
        </div>
    );
}
