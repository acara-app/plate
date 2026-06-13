import { parseApprovalOutput } from '@/components/chat/approval-part';
import type { ChatAction, UrlCitationPayload } from './message-reducer';

export interface RawStreamEvent {
    type: string;
    id?: string;
    delta?: string;
    citation?: UrlCitationPayload;
    result?: unknown;
    reasoning_id?: string;
    tool_id?: string;
    tool_name?: string;
    title?: string | null;
    arguments?: Record<string, unknown> | string | null;
    successful?: boolean;
    error?: string | null;
    item_id?: string;
    tool_type?: string;
    status?: string;
}

export interface StreamTracking {
    startedReasoningIds: Set<string>;
    startedProviderToolIds: Set<string>;
}

export function createStreamTracking(): StreamTracking {
    return {
        startedReasoningIds: new Set(),
        startedProviderToolIds: new Set(),
    };
}

function parseArgs(
    value: Record<string, unknown> | string | null | undefined,
): Record<string, unknown> | null {
    if (value === null || value === undefined) {
        return null;
    }

    if (typeof value === 'string') {
        try {
            const parsed: unknown = JSON.parse(value);

            return typeof parsed === 'object' && parsed !== null
                ? (parsed as Record<string, unknown>)
                : null;
        } catch {
            return null;
        }
    }

    return value;
}

export function applyStreamEvent(
    raw: RawStreamEvent,
    dispatch: React.Dispatch<ChatAction>,
    seenEventIds: Set<string>,
    tracking: StreamTracking,
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

        case 'thinking_start':
            if (typeof raw.reasoning_id === 'string') {
                seenEventIds.add(raw.id);
                tracking.startedReasoningIds.add(raw.reasoning_id);
                dispatch({
                    type: 'REASONING_START',
                    reasoningId: raw.reasoning_id,
                    at: Date.now(),
                });
            }
            break;

        case 'thinking':
            if (
                typeof raw.reasoning_id === 'string' &&
                typeof raw.delta === 'string' &&
                raw.delta !== ''
            ) {
                seenEventIds.add(raw.id);

                if (!tracking.startedReasoningIds.has(raw.reasoning_id)) {
                    tracking.startedReasoningIds.add(raw.reasoning_id);
                    dispatch({
                        type: 'REASONING_START',
                        reasoningId: raw.reasoning_id,
                        at: Date.now(),
                    });
                }

                dispatch({
                    type: 'REASONING_DELTA',
                    reasoningId: raw.reasoning_id,
                    delta: raw.delta,
                    at: Date.now(),
                });
            }
            break;

        case 'thinking_complete':
            if (typeof raw.reasoning_id === 'string') {
                seenEventIds.add(raw.id);
                dispatch({
                    type: 'REASONING_COMPLETE',
                    reasoningId: raw.reasoning_id,
                    at: Date.now(),
                });
            }
            break;

        case 'tool_call':
            if (typeof raw.tool_id === 'string') {
                seenEventIds.add(raw.id);
                dispatch({
                    type: 'ADD_TOOL_CALL',
                    toolId: raw.tool_id,
                    toolName: raw.tool_name ?? '',
                    title: raw.title ?? null,
                    args: parseArgs(raw.arguments),
                });
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
                    ownerToolId:
                        typeof raw.tool_id === 'string' ? raw.tool_id : null,
                });
                break;
            }

            if (typeof raw.tool_id === 'string') {
                seenEventIds.add(raw.id);
                dispatch({
                    type: 'UPDATE_TOOL_RESULT',
                    toolId: raw.tool_id,
                    result: raw.result,
                    successful: raw.successful !== false,
                    error: typeof raw.error === 'string' ? raw.error : null,
                });
            }
            break;
        }

        case 'provider_tool':
            if (typeof raw.item_id === 'string') {
                const finished =
                    raw.status === 'completed' ||
                    raw.status === 'result_received';

                seenEventIds.add(raw.id);

                if (!tracking.startedProviderToolIds.has(raw.item_id)) {
                    tracking.startedProviderToolIds.add(raw.item_id);
                    dispatch({
                        type: 'ADD_PROVIDER_TOOL',
                        itemId: raw.item_id,
                        toolType: raw.tool_type ?? raw.tool_name ?? '',
                    });
                }

                if (finished) {
                    dispatch({
                        type: 'UPDATE_PROVIDER_TOOL',
                        itemId: raw.item_id,
                    });
                }
            }
            break;

        case 'citation':
            if (typeof raw.citation?.url === 'string') {
                seenEventIds.add(raw.id);
                dispatch({ type: 'ADD_CITATION', citation: raw.citation });
            }
            break;

        case 'text_start':
        case 'text_complete':
        case 'stream_end':
            seenEventIds.add(raw.id);
            break;
    }
}
