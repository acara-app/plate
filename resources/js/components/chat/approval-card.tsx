import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import type {
    ApprovalCardData,
    ApprovalDecisionsPayload,
    ApprovalStatus,
} from '@/types/chat';
import { AlertCircle, Check, Clock, Loader2, X } from 'lucide-react';
import { type ComponentType, useState } from 'react';
import { useTranslation } from 'react-i18next';

interface ApprovalCardProps {
    approval: ApprovalCardData;
    onDecide?: (
        decisions: ApprovalDecisionsPayload,
    ) => Promise<'resumed' | 'recorded'>;
}

interface StatusPresentation {
    badgeLabel: string;
    badgeClassName: string;
    footerLabel: string;
    footerClassName: string;
    FooterIcon: ComponentType<{ className?: string }>;
    footerSpin?: boolean;
}

const STATUS_PRESENTATION: Record<ApprovalStatus, StatusPresentation> = {
    pending: {
        badgeLabel: 'Awaiting review',
        badgeClassName: 'bg-muted text-muted-foreground',
        footerLabel: 'Please approve or dismiss.',
        footerClassName: 'text-muted-foreground',
        FooterIcon: Clock,
    },
    submitted: {
        badgeLabel: 'Submitted',
        badgeClassName:
            'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
        footerLabel: 'Waiting on the other entries.',
        footerClassName: 'text-amber-600 dark:text-amber-400',
        FooterIcon: Loader2,
        footerSpin: true,
    },
    approved: {
        badgeLabel: 'Saved',
        badgeClassName:
            'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
        footerLabel: 'Saved successfully.',
        footerClassName: 'text-emerald-600 dark:text-emerald-400',
        FooterIcon: Check,
    },
    rejected: {
        badgeLabel: 'Dismissed',
        badgeClassName: 'bg-muted text-muted-foreground',
        footerLabel: 'Nothing was saved.',
        footerClassName: 'text-muted-foreground',
        FooterIcon: X,
    },
    abandoned: {
        badgeLabel: 'Not confirmed',
        badgeClassName: 'bg-muted text-muted-foreground',
        footerLabel: 'The conversation moved on, so nothing was saved.',
        footerClassName: 'text-muted-foreground',
        FooterIcon: Clock,
    },
};

function summaryOf(approval: ApprovalCardData): string {
    if (approval.reason) {
        return approval.reason;
    }

    const summary = approval.arguments.summary;

    return typeof summary === 'string' ? summary : approval.tool;
}

export function ApprovalCard({ approval, onDecide }: ApprovalCardProps) {
    const { t } = useTranslation();
    const [processing, setProcessing] = useState(false);
    const [submitted, setSubmitted] = useState(false);
    const [error, setError] = useState<string | null>(null);

    // A decision only queues a turn, so the card stays non-committal until the
    // resumed turn reports the tool's result — or a reload reads it back.
    const status: ApprovalStatus =
        approval.status === 'pending' && submitted
            ? 'submitted'
            : approval.status;

    const isFoodEntry = approval.arguments.log_type === 'food';
    const canDecide = approval.status === 'pending' && !submitted && !!onDecide;

    async function submit(intent: 'approve' | 'reject') {
        if (!onDecide || processing) {
            return;
        }

        setProcessing(true);
        setError(null);

        try {
            await onDecide({ [approval.toolCallId]: { action: intent } });
            setSubmitted(true);
        } catch {
            setError('Something went wrong. Please try again.');
        } finally {
            setProcessing(false);
        }
    }

    return (
        <Card className="my-2 gap-0 overflow-hidden border border-border/60 bg-card/80 backdrop-blur-sm">
            <CardContent className="px-4 py-3">
                <div className="flex items-start justify-between gap-3">
                    <p className="text-sm text-foreground">
                        {summaryOf(approval)}
                    </p>
                    <StatusBadge status={status} />
                </div>
                {isFoodEntry && (
                    <p className="mt-2 text-xs text-muted-foreground">
                        {t('tools:carb_boundary_notice')}
                    </p>
                )}
                {error && (
                    <p className="mt-2 flex items-center gap-1.5 text-xs text-red-500 dark:text-red-400">
                        <AlertCircle className="size-3.5 shrink-0" />
                        {error}
                    </p>
                )}
            </CardContent>

            <CardFooter className="border-t border-border/40 px-4 py-2.5">
                {canDecide ? (
                    <div className="flex w-full gap-2">
                        <Button
                            size="sm"
                            className="flex-1 bg-linear-to-br from-emerald-500 to-emerald-600 text-white shadow-sm transition-all hover:from-emerald-600 hover:to-emerald-700 hover:shadow-md active:scale-[0.98]"
                            disabled={processing}
                            onClick={() => void submit('approve')}
                        >
                            {processing ? (
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
                            disabled={processing}
                            onClick={() => void submit('reject')}
                        >
                            <X className="size-4" />
                            Dismiss
                        </Button>
                    </div>
                ) : (
                    <StatusFooter status={status} />
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
