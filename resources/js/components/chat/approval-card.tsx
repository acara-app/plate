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
        'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
    footerLabel: 'Saving…',
    footerClassName: 'text-amber-600 dark:text-amber-400',
    FooterIcon: Loader2,
    footerSpin: true,
} satisfies StatusPresentation;

const STATUS_PRESENTATION: Record<ApprovalStatus, StatusPresentation> = {
    pending: {
        badgeLabel: 'Awaiting review',
        badgeClassName: 'bg-muted text-muted-foreground',
        footerLabel: 'Please approve or dismiss.',
        footerClassName: 'text-muted-foreground',
        FooterIcon: Clock,
    },
    approved: SAVING_PRESENTATION,
    executing: SAVING_PRESENTATION,
    executed: {
        badgeLabel: 'Saved',
        badgeClassName:
            'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
        footerLabel: 'Saved successfully.',
        footerClassName: 'text-emerald-600 dark:text-emerald-400',
        FooterIcon: Check,
    },
    failed: {
        badgeLabel: 'Failed',
        badgeClassName:
            'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
        footerLabel: 'Could not be saved.',
        footerClassName: 'text-red-600 dark:text-red-400',
        FooterIcon: AlertCircle,
    },
    rejected: {
        badgeLabel: 'Dismissed',
        badgeClassName: 'bg-muted text-muted-foreground',
        footerLabel: 'Nothing was saved.',
        footerClassName: 'text-muted-foreground',
        FooterIcon: X,
    },
    expired: {
        badgeLabel: 'Expired',
        badgeClassName: 'bg-muted text-muted-foreground',
        footerLabel: 'This request expired.',
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
        <Card className="my-2 gap-0 overflow-hidden border border-border/60 bg-card/80 backdrop-blur-sm">
            <CardContent className="px-4 py-3">
                <div className="flex items-start justify-between gap-3">
                    <p className="text-sm text-foreground">{state.summary}</p>
                    <StatusBadge status={state.status} />
                </div>
                {state.notice && (
                    <p className="mt-2 text-xs text-muted-foreground">
                        {state.notice}
                    </p>
                )}
                {state.error && (
                    <p className="mt-2 flex items-center gap-1.5 text-xs text-red-500 dark:text-red-400">
                        <AlertCircle className="size-3.5 shrink-0" />
                        {state.error}
                    </p>
                )}
            </CardContent>

            <CardFooter className="border-t border-border/40 px-4 py-2.5">
                {state.can_approve || state.can_reject ? (
                    <div className="flex w-full gap-2">
                        <Button
                            size="sm"
                            className="flex-1 bg-linear-to-br from-emerald-500 to-emerald-600 text-white shadow-sm transition-all hover:from-emerald-600 hover:to-emerald-700 hover:shadow-md active:scale-[0.98]"
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
                            className="flex-1 transition-all hover:bg-destructive/5 hover:text-destructive active:scale-[0.98]"
                            disabled={action.processing || !state.can_reject}
                            onClick={() => void act('reject')}
                        >
                            <X className="size-4" />
                            Dismiss
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
