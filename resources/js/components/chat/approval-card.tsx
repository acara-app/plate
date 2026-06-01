import { isApprovalCardData } from '@/components/chat/approval-part';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { approve, reject } from '@/routes/approvals';
import type {
    ApprovalCardData,
    ApprovalStatus,
    ChatApprovalsPageProps,
} from '@/types/chat';
import { useHttp, usePage, usePoll } from '@inertiajs/react';
import { AlertCircle, Check, Clock, Loader2, X } from 'lucide-react';
import { type ComponentType, useEffect, useState } from 'react';

interface ApprovalCardProps {
    conversationId: string;
    approvalId: string;
    card: ApprovalCardData;
}

const POLL_INTERVAL_MS = 1500;

interface StatusPresentation {
    badgeLabel: string;
    badgeClassName: string;
    footerLabel: string;
    footerClassName: string;
    FooterIcon: ComponentType<{ className?: string }>;
    footerSpin?: boolean;
}

const SAVING_PRESENTATION = {
    badgeLabel: 'Saving…',
    badgeClassName:
        'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
    footerLabel: 'Saving…',
    footerClassName: 'text-emerald-700 dark:text-emerald-300',
    FooterIcon: Loader2,
    footerSpin: true,
} satisfies StatusPresentation;

const STATUS_PRESENTATION: Record<ApprovalStatus, StatusPresentation> = {
    pending: {
        badgeLabel: 'Not saved yet',
        badgeClassName:
            'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
        footerLabel: 'Awaiting your confirmation.',
        footerClassName: 'text-muted-foreground',
        FooterIcon: Clock,
    },
    approved: SAVING_PRESENTATION,
    executing: SAVING_PRESENTATION,
    executed: {
        badgeLabel: 'Saved',
        badgeClassName: 'bg-emerald-600 text-white',
        footerLabel: 'Saved.',
        footerClassName: 'text-emerald-700 dark:text-emerald-300',
        FooterIcon: Check,
    },
    failed: {
        badgeLabel: 'Not saved',
        badgeClassName:
            'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-200',
        footerLabel: 'This could not be saved.',
        footerClassName: 'text-red-600 dark:text-red-400',
        FooterIcon: AlertCircle,
    },
    rejected: {
        badgeLabel: 'Dismissed',
        badgeClassName: 'bg-muted text-muted-foreground',
        footerLabel: 'Dismissed — nothing was saved.',
        footerClassName: 'text-muted-foreground',
        FooterIcon: X,
    },
    expired: {
        badgeLabel: 'Expired',
        badgeClassName: 'bg-muted text-muted-foreground',
        footerLabel: 'This request expired and was not saved.',
        footerClassName: 'text-muted-foreground',
        FooterIcon: Clock,
    },
};

export function ApprovalCard({
    conversationId,
    approvalId,
    card,
}: ApprovalCardProps) {
    const action = useHttp<Record<string, never>, ApprovalCardData>({});
    const [state, setState] = useState<ApprovalCardData>(card);

    const live =
        usePage<ChatApprovalsPageProps>().props.approvals?.[approvalId];

    useEffect(() => {
        if (live) {
            setState(live);
        }
    }, [live]);

    const inFlight =
        state.status === 'approved' || state.status === 'executing';

    const { start, stop } = usePoll(
        POLL_INTERVAL_MS,
        { only: ['approvals'] },
        { autoStart: false },
    );

    useEffect(() => {
        if (!inFlight) {
            return;
        }

        start();

        return stop;
    }, [inFlight]);

    async function act(intent: 'approve' | 'reject') {
        if (action.processing) {
            return;
        }

        const url =
            intent === 'approve'
                ? approve.url({
                      conversation: conversationId,
                      approval: approvalId,
                  })
                : reject.url({
                      conversation: conversationId,
                      approval: approvalId,
                  });

        try {
            const fresh = await action.post(url);
            if (isApprovalCardData(fresh)) {
                setState(fresh);
            }
        } catch {
            setState((previous) => ({
                ...previous,
                error: 'Something went wrong. Please try again.',
            }));
        }
    }

    return (
        <Card className="my-1 gap-3 border-emerald-200 bg-emerald-50/60 py-4 dark:border-emerald-900/50 dark:bg-emerald-950/30">
            <CardContent className="px-4">
                <div className="flex items-start justify-between gap-3">
                    <p className="text-sm text-emerald-900 dark:text-emerald-100">
                        {state.summary}
                    </p>
                    <StatusBadge status={state.status} />
                </div>
                {state.error && (
                    <p className="mt-2 flex items-center gap-1 text-xs text-red-600 dark:text-red-400">
                        <AlertCircle className="size-3.5 shrink-0" />
                        {state.error}
                    </p>
                )}
            </CardContent>

            <CardFooter className="px-4">
                {state.can_approve || state.can_reject ? (
                    <div className="flex w-full gap-2">
                        <Button
                            size="sm"
                            className="flex-1 bg-emerald-600 text-white hover:bg-emerald-700"
                            disabled={action.processing || !state.can_approve}
                            onClick={() => void act('approve')}
                        >
                            {action.processing ? (
                                <Loader2 className="size-4 animate-spin" />
                            ) : (
                                <Check className="size-4" />
                            )}
                            Approve
                        </Button>
                        <Button
                            size="sm"
                            variant="outline"
                            className="flex-1 border-emerald-200 dark:border-emerald-900/50"
                            disabled={action.processing || !state.can_reject}
                            onClick={() => void act('reject')}
                        >
                            <X className="size-4" />
                            Reject
                        </Button>
                    </div>
                ) : (
                    <StatusFooter status={state.status} />
                )}
            </CardFooter>
        </Card>
    );
}

function StatusBadge({ status }: { status: ApprovalStatus }) {
    const { badgeLabel, badgeClassName } = STATUS_PRESENTATION[status];

    return (
        <span
            className={cn(
                'shrink-0 rounded-full px-2 py-0.5 text-xs font-medium',
                badgeClassName,
            )}
        >
            {badgeLabel}
        </span>
    );
}

function StatusFooter({ status }: { status: ApprovalStatus }) {
    const { footerLabel, footerClassName, FooterIcon, footerSpin } =
        STATUS_PRESENTATION[status];

    return (
        <p className={cn('flex items-center gap-1.5 text-xs', footerClassName)}>
            <FooterIcon
                className={cn('size-3.5', footerSpin && 'animate-spin')}
            />
            {footerLabel}
        </p>
    );
}
