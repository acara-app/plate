import { parseApprovalOutput } from '@/components/chat/approval-part';
import type { ChatAction, UrlCitationPayload } from './message-reducer';

export interface RawStreamEvent {
    type: string;
    id?: string;
    delta?: string;
    citation?: UrlCitationPayload;
    result?: unknown;
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
            const approval = parseApprovalOutput(raw.result);
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

        case 'text_start':
        case 'text_complete':
        case 'thinking_start':
        case 'thinking':
        case 'thinking_complete':
        case 'tool_call':
        case 'provider_tool':
        case 'stream_end':
            seenEventIds.add(raw.id);
            break;
    }
}
