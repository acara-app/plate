import { cn } from '@/lib/utils';
import { code } from '@streamdown/code';
import { defaultComponents, Streamdown, type Components } from 'streamdown';

const {
    p: Paragraph,
    h1: Heading1,
    h2: Heading2,
    h3: Heading3,
    h4: Heading4,
    ul: BulletList,
    ol: NumberedList,
    li: ListItem,
    a: Link,
    blockquote: Quote,
    hr: Rule,
    strong: Strong,
    thead: TableHead,
    th: HeaderCell,
    td: Cell,
} = defaultComponents;

const HEADING =
    'mt-8 mb-3 font-semibold tracking-tight text-foreground first:mt-0';

const components: Components = {
    p: ({ className, ...props }) => (
        <Paragraph
            className={cn('my-4 first:mt-0 last:mb-0', className)}
            {...props}
        />
    ),
    h1: ({ className, ...props }) => (
        <Heading1 className={cn(HEADING, 'text-xl', className)} {...props} />
    ),
    h2: ({ className, ...props }) => (
        <Heading2 className={cn(HEADING, 'text-lg', className)} {...props} />
    ),
    h3: ({ className, ...props }) => (
        <Heading3
            className={cn(HEADING, 'mt-6 mb-2 text-base', className)}
            {...props}
        />
    ),
    h4: ({ className, ...props }) => (
        <Heading4
            className={cn(HEADING, 'mt-6 mb-2 text-base', className)}
            {...props}
        />
    ),
    ul: ({ className, ...props }) => (
        <BulletList
            className={cn(
                'my-4 list-outside list-disc pl-5 marker:text-muted-foreground/70 first:mt-0 last:mb-0 [li_&]:my-1 [li_&]:pl-5',
                className,
            )}
            {...props}
        />
    ),
    ol: ({ className, ...props }) => (
        <NumberedList
            className={cn(
                'my-4 list-outside list-decimal pl-6 marker:text-muted-foreground first:mt-0 last:mb-0 [li_&]:my-1 [li_&]:pl-5',
                className,
            )}
            {...props}
        />
    ),
    li: ({ className, ...props }) => (
        <ListItem className={cn('my-1.5 py-0 pl-1', className)} {...props} />
    ),
    a: ({ className, ...props }) => (
        <Link
            className={cn(
                'font-normal text-foreground decoration-emerald-600/60 decoration-1 underline-offset-[3px] transition-colors hover:decoration-emerald-600 hover:decoration-2 focus-visible:rounded-sm focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:outline-none dark:decoration-emerald-400/60 dark:hover:decoration-emerald-400 dark:focus-visible:ring-emerald-400',
                className,
            )}
            {...props}
        />
    ),
    strong: ({ className, ...props }) => (
        <Strong
            className={cn('font-semibold text-foreground', className)}
            {...props}
        />
    ),
    blockquote: ({ className, ...props }) => (
        <Quote
            className={cn(
                'my-5 border-l-2 border-border pl-4 text-muted-foreground not-italic',
                className,
            )}
            {...props}
        />
    ),
    hr: ({ className, ...props }) => (
        <Rule className={cn('my-8 border-border', className)} {...props} />
    ),
    table: ({ className, node: _node, children, ...props }) => (
        <div className="my-6 w-full overflow-x-auto first:mt-0 last:mb-0">
            <table
                className={cn(
                    'w-full divide-y divide-border border-b border-border text-left',
                    className,
                )}
                {...props}
            >
                {children}
            </table>
        </div>
    ),
    thead: ({ className, ...props }) => (
        <TableHead className={cn('bg-transparent', className)} {...props} />
    ),
    th: ({ className, ...props }) => (
        <HeaderCell
            className={cn(
                'px-3 py-2 text-[13px] font-medium text-muted-foreground first:pl-0 last:pr-0',
                className,
            )}
            {...props}
        />
    ),
    td: ({ className, ...props }) => (
        <Cell
            className={cn(
                'px-3 py-2.5 align-top text-[15px] tabular-nums first:pl-0 last:pr-0',
                className,
            )}
            {...props}
        />
    ),
};

const VARIANTS = {
    answer: 'text-base leading-7 text-foreground/90',
    quiet: 'text-[13px] leading-6 text-muted-foreground',
} as const;

export function AnswerMarkdown({
    children,
    isStreaming = false,
    variant = 'answer',
}: {
    children: string;
    isStreaming?: boolean;
    variant?: keyof typeof VARIANTS;
}) {
    return (
        <div
            className={cn(
                'chat-markdown min-w-0 [overflow-wrap:anywhere]',
                VARIANTS[variant],
            )}
        >
            <Streamdown
                animated
                isAnimating={isStreaming}
                plugins={{ code }}
                components={components}
            >
                {children}
            </Streamdown>
        </div>
    );
}
