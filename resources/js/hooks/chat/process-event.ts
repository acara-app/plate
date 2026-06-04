import { isApprovalCardData } from '@/components/chat/approval-part';
import type { ApprovalCardData } from '@/types/chat';
import type { ChatAction, UrlCitationPayload } from './message-reducer';

export interface RawStreamEvent {
    type: string;
    id?: string;
    delta?: string;
    citation?: UrlCitationPayload;
    result?: unknown;
}

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null;
}

function parseApproval(
    result: unknown,
): { approvalId: string; card: ApprovalCardData } | null {
    let parsed = result;

    if (typeof parsed === 'string') {
        try {
            parsed = JSON.parse(parsed);
        } catch {
            return null;
        }
    }

    if (
        isRecord(parsed) &&
        typeof parsed.approval_id === 'string' &&
        isApprovalCardData(parsed.card)
    ) {
        return { approvalId: parsed.approval_id, card: parsed.card };
    }

    return null;
}

export function applyStreamEvent(
    raw: RawStreamEvent,
    dispatch: React.Dispatch<ChatAction>,
    seenEventIds: Set<string>,
): void {
    if (typeof raw.id !== 'string' || seenEventIds.has(raw.id)) {
        return;
    }

    switch (raw.type) {
        case 'stream_start':
            seenEventIds.add(raw.id);
            dispatch({ type: 'STREAM_START', id: raw.id });
            break;

        case 'text_delta':
            if (typeof raw.delta === 'string' && raw.delta !== '') {
                seenEventIds.add(raw.id);
                dispatch({ type: 'APPEND_TEXT', delta: raw.delta });
            }
            break;

        case 'citation':
            if (typeof raw.citation?.url === 'string') {
                seenEventIds.add(raw.id);
                dispatch({ type: 'ADD_CITATION', citation: raw.citation });
            }
            break;

        case 'tool_result': {
            const approval = parseApproval(raw.result);
            if (approval) {
                seenEventIds.add(raw.id);
                dispatch({
                    type: 'ADD_APPROVAL',
                    approvalId: approval.approvalId,
                    card: approval.card,
                });
            }
            break;
        }
    }
}
