import { cn } from '@/lib/utils';
import type { ApprovalCardData, ApprovalDecisionsPayload } from '@/types/chat';
import {
    CircleAlert,
    CircleCheck,
    CircleDashed,
    CircleMinus,
    CirclePause,
    type LucideIcon,
} from 'lucide-react';
import { useId, useState } from 'react';
import { useTranslation } from 'react-i18next';

type Intent = 'approve' | 'reject';

interface Decision {
    intent: Intent;
    outcome: 'resumed' | 'recorded';
}

interface ApprovalCardProps {
    approval: ApprovalCardData;
    onDecide?: (
        decisions: ApprovalDecisionsPayload,
    ) => Promise<'resumed' | 'recorded'>;
}

const COPY_GROUPS = ['log_health_entry'];

const FOCUS_RING =
    'focus-visible:ring-2 focus-visible:ring-emerald-600 dark:focus-visible:ring-emerald-400 focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none';

const PRESSABLE =
    'touch-manipulation select-none [-webkit-tap-highlight-color:transparent] transition-[scale,background-color] duration-150 ease-out active:scale-[0.97]';

function summaryOf(approval: ApprovalCardData): string {
    if (approval.reason) {
        return approval.reason;
    }

    const summary = approval.arguments.summary;

    return typeof summary === 'string' ? summary : approval.tool;
}

function useApprovalCopy(tool: string) {
    const { t } = useTranslation('common');
    const group = COPY_GROUPS.includes(tool) ? tool : 'generic';

    return (key: string): string => t(`chat.approval.${group}.${key}`);
}

function ResolvedApproval({
    Icon,
    iconClassName,
    label,
    summary,
}: {
    Icon: LucideIcon;
    iconClassName: string;
    label: string;
    summary: string;
}) {
    return (
        <div role="status" className="flex items-start gap-2 text-sm">
            <Icon
                className={cn('mt-0.5 size-4 shrink-0', iconClassName)}
                strokeWidth={1.75}
                aria-hidden="true"
            />
            <div className="min-w-0">
                <p className="text-foreground/80">{label}</p>
                <p className="mt-0.5 text-[13px] leading-5 text-muted-foreground">
                    {summary}
                </p>
            </div>
        </div>
    );
}

export function ApprovalCard({ approval, onDecide }: ApprovalCardProps) {
    const { t } = useTranslation('common');
    const copy = useApprovalCopy(approval.tool);
    const titleId = useId();
    const descriptionId = useId();
    const [processing, setProcessing] = useState(false);
    const [decision, setDecision] = useState<Decision | null>(null);
    const [failed, setFailed] = useState(false);

    const summary = summaryOf(approval);

    async function decide(intent: Intent) {
        if (!onDecide || processing) {
            return;
        }

        setProcessing(true);
        setFailed(false);

        try {
            const outcome = await onDecide({
                [approval.toolCallId]: { action: intent },
            });
            setDecision({ intent, outcome });
        } catch {
            setFailed(true);
        } finally {
            setProcessing(false);
        }
    }

    if (approval.status === 'approved') {
        return (
            <ResolvedApproval
                Icon={CircleCheck}
                iconClassName="text-emerald-600 dark:text-emerald-400"
                label={copy('approved')}
                summary={summary}
            />
        );
    }

    if (approval.status === 'rejected' || approval.status === 'abandoned') {
        return (
            <ResolvedApproval
                Icon={CircleMinus}
                iconClassName="text-muted-foreground"
                label={
                    approval.status === 'rejected'
                        ? copy('rejected')
                        : t('chat.approval.abandoned')
                }
                summary={summary}
            />
        );
    }

    if (approval.status === 'submitted' || decision?.outcome === 'recorded') {
        return (
            <ResolvedApproval
                Icon={CirclePause}
                iconClassName="text-amber-600 dark:text-amber-400"
                label={t('chat.approval.recorded')}
                summary={summary}
            />
        );
    }

    if (decision) {
        return (
            <ResolvedApproval
                Icon={CircleDashed}
                iconClassName="text-foreground/70 motion-safe:animate-spin motion-safe:[animation-duration:2.4s]"
                label={copy(
                    decision.intent === 'approve' ? 'approving' : 'declining',
                )}
                summary={summary}
            />
        );
    }

    const isFoodEntry = approval.arguments.log_type === 'food';

    return (
        <section
            aria-labelledby={titleId}
            aria-describedby={descriptionId}
            className="rounded-3xl bg-muted/60 p-5 duration-300 ease-out motion-safe:animate-in motion-safe:fade-in motion-safe:slide-in-from-bottom-2 dark:bg-muted/50"
        >
            <div className="flex flex-wrap items-center justify-between gap-x-4 gap-y-1">
                <h3
                    id={titleId}
                    className="flex items-center gap-2 text-sm font-semibold text-foreground"
                >
                    <CirclePause
                        className="size-4 shrink-0 text-amber-600 dark:text-amber-400"
                        strokeWidth={1.75}
                        aria-hidden="true"
                    />
                    {copy('title')}
                </h3>
                <p className="text-xs text-muted-foreground">
                    {t('chat.approval.waiting')}
                </p>
            </div>

            <p className="mt-3 text-base leading-7 font-medium text-foreground">
                {summary}
            </p>

            <div id={descriptionId} className="mt-1 space-y-1.5">
                <p className="text-sm leading-6 text-muted-foreground">
                    {copy('body')}
                </p>
                {isFoodEntry && (
                    <p className="text-xs leading-5 text-muted-foreground">
                        {t('tools:carb_boundary_notice')}
                    </p>
                )}
            </div>

            {failed && (
                <p
                    role="alert"
                    className="mt-3 flex items-center gap-1.5 text-[13px] text-red-700 dark:text-red-300"
                >
                    <CircleAlert
                        className="size-4 shrink-0"
                        strokeWidth={1.75}
                        aria-hidden="true"
                    />
                    {t('chat.approval.error')}
                </p>
            )}

            {onDecide && (
                <div className="mt-4 flex flex-wrap gap-2">
                    <button
                        type="button"
                        disabled={processing}
                        onClick={() => void decide('approve')}
                        className={cn(
                            'inline-flex h-11 min-w-32 flex-1 cursor-pointer items-center justify-center rounded-full bg-emerald-700 px-5 text-[15px] font-semibold text-white hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-60 sm:flex-none dark:bg-emerald-500 dark:text-emerald-950 dark:hover:bg-emerald-400',
                            PRESSABLE,
                            FOCUS_RING,
                        )}
                    >
                        {copy('approve')}
                    </button>
                    <button
                        type="button"
                        disabled={processing}
                        onClick={() => void decide('reject')}
                        className={cn(
                            'inline-flex h-11 min-w-32 flex-1 cursor-pointer items-center justify-center rounded-full bg-background px-5 text-[15px] font-medium text-foreground shadow-[0_1px_2px_rgb(0_0_0/0.06)] hover:bg-background/70 disabled:cursor-not-allowed disabled:opacity-60 sm:flex-none dark:bg-foreground/10 dark:hover:bg-foreground/15',
                            PRESSABLE,
                            FOCUS_RING,
                        )}
                    >
                        {copy('reject')}
                    </button>
                </div>
            )}
        </section>
    );
}
