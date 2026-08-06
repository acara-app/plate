import type { ApprovalCardData, ApprovalStatus } from '@/types/chat';
import type { UIMessage } from '@ai-sdk/react';

const STATUSES: ApprovalStatus[] = ['pending', 'approved', 'rejected'];

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null;
}

export function isApprovalCardData(value: unknown): value is ApprovalCardData {
    return (
        isRecord(value) &&
        typeof value.toolCallId === 'string' &&
        STATUSES.includes(value.status as ApprovalStatus)
    );
}

/**
 * Normalize one entry of a `tool_approval_request` stream event.
 */
export function parseApprovalRequest(value: unknown): ApprovalCardData | null {
    if (!isRecord(value) || typeof value.id !== 'string') {
        return null;
    }

    return {
        toolCallId: value.id,
        tool: typeof value.tool === 'string' ? value.tool : '',
        reason: typeof value.reason === 'string' ? value.reason : null,
        arguments: isRecord(value.arguments) ? value.arguments : {},
        status: 'pending',
    };
}

export function extractApprovalPayload(
    part: UIMessage['parts'][number],
): ApprovalCardData | null {
    if (part.type !== 'data-approval' || !('data' in part)) {
        return null;
    }

    return isApprovalCardData(part.data) ? part.data : null;
}
