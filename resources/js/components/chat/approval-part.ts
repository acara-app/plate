import type { ApprovalCardData } from '@/types/chat';
import type { UIMessage } from '@ai-sdk/react';
import { isToolUIPart } from 'ai';

export interface ApprovalPartPayload {
    approvalId: string;
    card: ApprovalCardData;
}

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null;
}

export function isApprovalCardData(value: unknown): value is ApprovalCardData {
    return (
        isRecord(value) &&
        typeof value.status === 'string' &&
        typeof value.summary === 'string'
    );
}

export function extractApprovalPayload(
    part: UIMessage['parts'][number],
): ApprovalPartPayload | null {
    if (isToolUIPart(part) && part.state === 'output-available') {
        let output: unknown = part.output;
        if (typeof output === 'string') {
            try {
                output = JSON.parse(output);
            } catch {
                return null;
            }
        }

        if (
            isRecord(output) &&
            typeof output.approval_id === 'string' &&
            isApprovalCardData(output.card)
        ) {
            return { approvalId: output.approval_id, card: output.card };
        }

        return null;
    }

    if (part.type === 'data-approval') {
        const data = part.data;
        if (
            isRecord(data) &&
            typeof data.approvalId === 'string' &&
            isApprovalCardData(data.card)
        ) {
            return { approvalId: data.approvalId, card: data.card };
        }
    }

    return null;
}
