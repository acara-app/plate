import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { cn } from '@/lib/utils';
import {
    ChevronRight,
    CircleCheck,
    CircleDashed,
    CircleMinus,
    CirclePause,
    CircleStop,
    CircleX,
    type LucideIcon,
} from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import {
    type ActivityItem,
    formatTechnicalValue,
    isPrimaryArgument,
    latestThought,
    requestFields,
    type StepStatus,
    statusWord,
    stepLabel,
    stepOutcome,
    summarizeActivity,
} from './activity';
import { AnswerMarkdown } from './answer-markdown';

const STATUS_ICONS: Record<
    StepStatus,
    { Icon: LucideIcon; className: string }
> = {
    running: {
        Icon: CircleDashed,
        className:
            'text-foreground/70 motion-safe:animate-spin motion-safe:[animation-duration:2.4s]',
    },
    done: {
        Icon: CircleCheck,
        className: 'text-emerald-600 dark:text-emerald-400',
    },
    failed: { Icon: CircleX, className: 'text-red-600 dark:text-red-400' },
    stopped: { Icon: CircleStop, className: 'text-muted-foreground' },
    awaiting: {
        Icon: CirclePause,
        className: 'text-amber-600 dark:text-amber-400',
    },
    declined: { Icon: CircleMinus, className: 'text-muted-foreground' },
};

const ROW =
    'group flex min-h-8 w-full items-center gap-2 rounded-md px-2 py-1 text-left transition-colors focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-emerald-600 dark:focus-visible:ring-emerald-400 focus-visible:outline-none';

const INTERACTIVE_ROW = `${ROW} cursor-pointer hover:bg-muted/50`;

const STEP_ROW = cn(ROW, 'items-start py-1.5 leading-5');

const DISCLOSURE =
    'overflow-hidden data-[state=closed]:animate-collapse-up data-[state=open]:animate-collapse-down motion-reduce:animate-none';

function StatusIcon({
    status,
    className,
}: {
    status: StepStatus;
    className?: string;
}) {
    const { Icon, className: tone } = STATUS_ICONS[status];

    return (
        <Icon
            className={cn('size-4 shrink-0', tone, className)}
            strokeWidth={1.75}
            aria-hidden="true"
        />
    );
}

function Chevron({ className }: { className?: string }) {
    return (
        <ChevronRight
            className={cn(
                'size-3.5 shrink-0 text-muted-foreground/70 transition-transform duration-200 group-data-[state=open]:rotate-90 motion-reduce:transition-none',
                className,
            )}
            aria-hidden="true"
        />
    );
}

function TechnicalValue({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <p className="text-[11px] font-medium text-muted-foreground">
                {label}
            </p>
            <pre className="mt-1 max-h-56 overflow-auto rounded-md bg-muted/70 px-3 py-2 font-mono text-xs leading-5 [overflow-wrap:anywhere] whitespace-pre-wrap text-foreground/80">
                {value}
            </pre>
        </div>
    );
}

function TechnicalDetails({ item }: { item: ActivityItem }) {
    const { t } = useTranslation('common');

    if (item.kind !== 'tool') {
        return null;
    }

    const args = formatTechnicalValue(item.tool.args);
    const result = formatTechnicalValue(item.tool.result);

    return (
        <Collapsible className="mt-2">
            <CollapsibleTrigger
                data-test="chat-technical-toggle"
                className="group -ml-1 inline-flex min-h-7 cursor-pointer items-center gap-1 rounded px-1 text-xs text-muted-foreground transition-colors hover:text-foreground focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:outline-none focus-visible:ring-inset dark:focus-visible:ring-emerald-400"
            >
                <Chevron className="size-3" />
                {t('chat.activity.technical')}
            </CollapsibleTrigger>
            <CollapsibleContent className={DISCLOSURE}>
                <div className="mt-1.5 space-y-2.5 pb-1">
                    <TechnicalValue
                        label={t('chat.activity.tool')}
                        value={item.tool.toolName || item.tool.toolId}
                    />
                    {args && (
                        <TechnicalValue
                            label={t('chat.activity.arguments')}
                            value={args}
                        />
                    )}
                    {result && (
                        <TechnicalValue
                            label={t('chat.activity.result')}
                            value={result}
                        />
                    )}
                    {item.tool.error && (
                        <TechnicalValue
                            label={t('chat.activity.error')}
                            value={item.tool.error}
                        />
                    )}
                </div>
            </CollapsibleContent>
        </Collapsible>
    );
}

const OUTCOME_TONES = {
    default: 'text-foreground/80',
    muted: 'text-muted-foreground',
    error: 'text-red-700 dark:text-red-300',
} as const;

function StepDetails({ item }: { item: ActivityItem }) {
    const { t } = useTranslation('common');

    if (item.kind === 'reasoning') {
        return (
            <div className="max-h-72 overflow-y-auto pr-2 pb-2">
                <AnswerMarkdown
                    variant="quiet"
                    isStreaming={item.status === 'running'}
                >
                    {item.reasoning.text}
                </AnswerMarkdown>
            </div>
        );
    }

    const fields = item.kind === 'tool' ? requestFields(item.tool.args) : [];
    const outcome = stepOutcome(item, t);
    const single = fields.length === 1 && isPrimaryArgument(fields[0]);

    return (
        <div className="pr-2 pb-3 text-[13px] leading-5">
            <dl className="space-y-2">
                {fields.length > 0 && (
                    <div>
                        <dt className="text-xs text-muted-foreground">
                            {t('chat.activity.request')}
                        </dt>
                        <dd className="mt-0.5 text-foreground/80">
                            {single ? (
                                <p className="line-clamp-4">
                                    {fields[0].value}
                                </p>
                            ) : (
                                <ul className="space-y-0.5">
                                    {fields.map((field) => (
                                        <li
                                            key={field.key}
                                            className="line-clamp-2"
                                        >
                                            <span className="text-muted-foreground">
                                                {field.label}:
                                            </span>{' '}
                                            {field.value}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </dd>
                    </div>
                )}
                <div>
                    <dt className="text-xs text-muted-foreground">
                        {t('chat.activity.outcome')}
                    </dt>
                    <dd
                        className={cn(
                            'mt-0.5 line-clamp-4',
                            OUTCOME_TONES[outcome.tone],
                        )}
                    >
                        {outcome.text}
                    </dd>
                </div>
            </dl>
            <TechnicalDetails item={item} />
        </div>
    );
}

function StepRowContent({ item }: { item: ActivityItem }) {
    const { t } = useTranslation('common');

    return (
        <>
            <StatusIcon status={item.status} className="mt-0.5" />
            <span className="line-clamp-2 min-w-0 flex-1 text-foreground/80">
                {stepLabel(item, t)}
            </span>
            {item.kind !== 'reasoning' && (
                <span className="max-w-[40%] shrink-0 text-right text-xs leading-5 text-muted-foreground">
                    {statusWord(item.status, t)}
                </span>
            )}
        </>
    );
}

function ActivityStep({ item }: { item: ActivityItem }) {
    const connector =
        "after:absolute after:top-6 after:-bottom-2 after:left-[15px] after:w-px after:bg-border after:content-[''] last:after:hidden";

    if (item.kind === 'provider') {
        return (
            <li className={cn('relative', connector)}>
                <div className={STEP_ROW}>
                    <StepRowContent item={item} />
                    <span
                        aria-hidden="true"
                        className="mt-[3px] size-3.5 shrink-0"
                    />
                </div>
            </li>
        );
    }

    return (
        <li className={cn('relative', connector)}>
            <Collapsible>
                <CollapsibleTrigger
                    data-test="chat-step-toggle"
                    className={cn(STEP_ROW, 'cursor-pointer hover:bg-muted/50')}
                >
                    <StepRowContent item={item} />
                    <Chevron className="mt-[3px]" />
                </CollapsibleTrigger>
                <CollapsibleContent className={cn(DISCLOSURE, 'pl-8')}>
                    <StepDetails item={item} />
                </CollapsibleContent>
            </Collapsible>
        </li>
    );
}

export function ActivityLog({ items }: { items: ActivityItem[] }) {
    const { t } = useTranslation('common');
    const [open, setOpen] = useState(false);
    const summary = summarizeActivity(items, t);

    if (!summary) {
        return null;
    }

    const thought = open ? null : latestThought(items);

    return (
        <Collapsible
            open={open}
            onOpenChange={setOpen}
            className="-mx-2 text-sm"
        >
            <CollapsibleTrigger
                data-test="chat-activity-toggle"
                className={cn(
                    INTERACTIVE_ROW,
                    'inline-flex min-h-9 w-auto max-w-full text-muted-foreground hover:text-foreground @min-[56rem]:min-h-7',
                )}
            >
                <StatusIcon status={summary.status} />
                <span className="min-w-0 truncate">{summary.label}</span>
                {summary.showStatusWord && (
                    <span className="shrink-0 text-xs text-muted-foreground">
                        {statusWord(summary.status, t)}
                    </span>
                )}
                <Chevron />
            </CollapsibleTrigger>

            {thought && (
                <p
                    aria-hidden="true"
                    className="mt-0.5 ml-8 line-clamp-2 max-w-xl [mask-image:linear-gradient(to_bottom,black_45%,transparent)] text-[13px] leading-5 text-muted-foreground/80"
                >
                    {thought}
                </p>
            )}

            <CollapsibleContent className={DISCLOSURE}>
                <ol className="mt-0.5 ml-6 max-w-lg pb-1">
                    {items.map((item) => (
                        <ActivityStep
                            key={`${item.kind}-${item.id}`}
                            item={item}
                        />
                    ))}
                </ol>
            </CollapsibleContent>
        </Collapsible>
    );
}
