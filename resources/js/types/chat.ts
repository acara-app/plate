import { UIMessage } from '@ai-sdk/react';

export type { UIMessage } from '@ai-sdk/react';

export type ChatStatus = 'ready' | 'submitted' | 'streaming' | 'error';

export interface ChatPageProps {
    conversationId: string;
    isPinned?: boolean;
    temporaryRetentionHours?: number;
    initialPrompt?: string | null;
    initialStreaming?: boolean;
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
    notice: string | null;
}

export interface ChatApprovalsPageProps {
    approvals?: Record<string, ApprovalCardData>;
    [key: string]: unknown;
}

export interface ReasoningData {
    reasoningId: string;
    text: string;
    startedAt: number;
    completedAt: number | null;
    active: boolean;
}

export type ToolCallStatus = 'running' | 'complete' | 'error';

export interface ToolCallData {
    toolId: string;
    toolName: string;
    title: string | null;
    args: Record<string, unknown> | null;
    result: unknown;
    status: ToolCallStatus;
    error: string | null;
}

export type ProviderToolKind = 'web_search' | 'web_fetch' | 'other';

export interface ProviderToolData {
    itemId: string;
    toolType: string;
    kind: ProviderToolKind;
    status: 'running' | 'complete';
}
