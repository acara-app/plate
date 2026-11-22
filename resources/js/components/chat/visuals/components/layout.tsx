import { cn } from '@/lib/utils';
import { ChevronDown } from 'lucide-react';
import { createContext, useContext, useId } from 'react';
import type { ViewProps } from './props';
import { GAPS, hasChildren, tint, visualIcon } from './style';

export const VISUAL_SURFACE = 'rounded-3xl bg-muted/60 p-5 dark:bg-muted/50';

const CardDepth = createContext(0);

export const CardTitleId = createContext<string | null>(null);

const ALIGN = {
    start: 'items-start',
    center: 'items-center',
    end: 'items-end',
    stretch: 'items-stretch',
} as const;

const COLUMNS: Record<number, string> = {
    1: 'grid-cols-1',
    2: 'grid-cols-1 @min-[20rem]:grid-cols-2',
    3: 'grid-cols-1 @min-[30rem]:grid-cols-3',
};

export function Card({ props, children }: ViewProps<'Card'>) {
    const depth = useContext(CardDepth);
    const outerTitleId = useContext(CardTitleId);
    const ownTitleId = useId();
    const titleId = props.title ? ownTitleId : outerTitleId;
    const Icon = visualIcon(props.icon);
    const accent = tint(props.tint ?? props.icon);
    const Title = depth === 0 ? 'h3' : 'h4';

    return (
        <section
            className={cn('flex flex-col gap-4', depth === 0 && VISUAL_SURFACE)}
        >
            {(props.title || props.subtitle) && (
                <header className="flex items-center gap-3">
                    {Icon && (
                        <span
                            className={cn(
                                'flex size-9 shrink-0 items-center justify-center rounded-full',
                                accent.soft,
                            )}
                        >
                            <Icon
                                className={cn('size-[18px]', accent.text)}
                                aria-hidden="true"
                            />
                        </span>
                    )}
                    <div className="min-w-0">
                        {props.title && (
                            <Title
                                id={ownTitleId}
                                className={cn(
                                    'font-semibold tracking-tight text-balance text-foreground',
                                    depth === 0 ? 'text-base' : 'text-[15px]',
                                )}
                            >
                                {props.title}
                            </Title>
                        )}
                        {props.subtitle && (
                            <p className="text-[13px] leading-5 text-muted-foreground">
                                {props.subtitle}
                            </p>
                        )}
                    </div>
                </header>
            )}
            {hasChildren(children) && (
                <CardDepth value={depth + 1}>
                    <CardTitleId value={titleId}>{children}</CardTitleId>
                </CardDepth>
            )}
        </section>
    );
}

export function Stack({ props, children }: ViewProps<'Stack'>) {
    const horizontal = props.direction === 'horizontal';

    return (
        <div
            className={cn(
                'flex min-w-0',
                horizontal ? 'flex-row flex-wrap' : 'flex-col',
                GAPS[props.gap ?? (horizontal ? 'sm' : 'md')],
                ALIGN[props.align ?? (horizontal ? 'center' : 'stretch')],
            )}
        >
            {children}
        </div>
    );
}

export function Grid({ props, children }: ViewProps<'Grid'>) {
    return (
        <div
            className={cn(
                'grid',
                COLUMNS[props.columns ?? 2] ?? COLUMNS[1],
                GAPS[props.gap ?? 'md'],
            )}
        >
            {children}
        </div>
    );
}

export function Separator() {
    return <hr className="border-foreground/10" />;
}

export function Disclosure({ props, children }: ViewProps<'Disclosure'>) {
    return (
        <details className="group">
            <summary className="flex cursor-pointer list-none items-center justify-between gap-3 rounded-lg text-[15px] leading-6 font-medium text-foreground select-none focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none dark:focus-visible:ring-emerald-400 [&::-webkit-details-marker]:hidden">
                {props.title}
                <ChevronDown
                    className="size-4 shrink-0 text-muted-foreground transition-transform duration-200 ease-out group-open:rotate-180 motion-reduce:transition-none"
                    aria-hidden="true"
                />
            </summary>
            {hasChildren(children) && (
                <div className="mt-3 flex flex-col gap-3">{children}</div>
            )}
        </details>
    );
}
