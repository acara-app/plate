import { cn } from '@/lib/utils';
import type { ViewProps } from './props';
import { hasChildren, tint, visualIcon } from './style';

export function List({ props, children }: ViewProps<'List'>) {
    return (
        <div className="flex flex-col gap-1">
            {props.title && (
                <p className="text-[13px] leading-5 font-medium text-muted-foreground">
                    {props.title}
                </p>
            )}
            <ul role="list" className="divide-y divide-foreground/10">
                {children}
            </ul>
        </div>
    );
}

export function ListItem({ props, children }: ViewProps<'ListItem'>) {
    const Icon = visualIcon(props.icon);
    const accent = tint(props.tint ?? props.icon);

    return (
        <li className="flex gap-3 py-2.5 first:pt-1 last:pb-1">
            {Icon && (
                <span
                    className={cn(
                        'flex size-8 shrink-0 items-center justify-center rounded-full',
                        accent.soft,
                    )}
                >
                    <Icon
                        className={cn('size-4', accent.text)}
                        aria-hidden="true"
                    />
                </span>
            )}
            <div className="min-w-0 flex-1 self-center">
                <div className="flex items-baseline justify-between gap-3">
                    <p className="text-[15px] leading-6 font-medium text-foreground">
                        {props.title}
                    </p>
                    {props.trailing && (
                        <span className="shrink-0 text-sm text-muted-foreground tabular-nums">
                            {props.trailing}
                        </span>
                    )}
                </div>
                {props.subtitle && (
                    <p className="text-[13px] leading-5 text-muted-foreground">
                        {props.subtitle}
                    </p>
                )}
                {hasChildren(children) && (
                    <div className="mt-2 flex flex-col gap-2">{children}</div>
                )}
            </div>
        </li>
    );
}

export function Steps({ props }: ViewProps<'Steps'>) {
    const last = props.items.length - 1;

    return (
        <ol className="flex flex-col">
            {props.items.map((item, index) => (
                <li
                    key={`${index}-${item.title}`}
                    className="relative flex gap-3 pb-5 last:pb-0"
                >
                    {index < last && (
                        <span
                            className="absolute top-8 bottom-1.5 left-3 w-px bg-foreground/10"
                            aria-hidden="true"
                        />
                    )}
                    <span
                        className="flex size-6 shrink-0 items-center justify-center rounded-full bg-foreground/[0.08] text-xs font-semibold text-foreground tabular-nums"
                        aria-hidden="true"
                    >
                        {index + 1}
                    </span>
                    <div className="min-w-0 flex-1">
                        <div className="flex items-baseline justify-between gap-3">
                            <p className="text-[15px] leading-6 font-medium text-foreground">
                                {item.title}
                            </p>
                            {item.duration && (
                                <span className="shrink-0 text-[13px] text-muted-foreground tabular-nums">
                                    {item.duration}
                                </span>
                            )}
                        </div>
                        {item.detail && (
                            <p className="text-sm leading-6 text-muted-foreground">
                                {item.detail}
                            </p>
                        )}
                    </div>
                </li>
            ))}
        </ol>
    );
}
