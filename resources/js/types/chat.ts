import { UIMessage } from '@ai-sdk/react';

export type { UIMessage } from '@ai-sdk/react';

export type ChatStatus = 'ready' | 'submitted' | 'streaming' | 'error';

export interface ChatPageProps {
    conversationId: string;
    initialPrompt?: string | null;
    messages: UIMessage[];
    [key: string]: unknown;
}

export type ApprovalStatus =
    | 'pending'
    | 'approved'
    | 'executing'
    | 'executed'
    | 'failed'
    | 'rejected'
    | 'expired';

export interface ApprovalCardData {
    status: ApprovalStatus;
    summary: string;
    can_approve: boolean;
    can_reject: boolean;
    error: string | null;
}

export interface ChatApprovalsPageProps {
    approvals?: Record<string, ApprovalCardData>;
    [key: string]: unknown;
}
