import type { PaywallCapTrigger } from '@/types';
import type { ApprovalCardData, ChatStatus } from '@/types/chat';
import type { UIMessage } from 'ai';

export type { ChatStatus } from '@/types/chat';

export interface UrlCitationPayload {
    url: string;
    title?: string | null;
}

export interface ChatState {
    messages: UIMessage[];
    status: ChatStatus;
    error: Error | undefined;
    usageLimitTrigger: PaywallCapTrigger | null;
}

export type ChatAction =
    | { type: 'SET_MESSAGES'; messages: UIMessage[] }
    | { type: 'ADD_USER_MESSAGE'; message: UIMessage }
    | { type: 'PROCESSING' }
    | { type: 'STREAM_START'; id: string }
    | { type: 'APPEND_TEXT'; delta: string }
    | { type: 'ADD_CITATION'; citation: UrlCitationPayload }
    | { type: 'ADD_APPROVAL'; approvalId: string; card: ApprovalCardData }
    | { type: 'RETRYING'; attempt: number; maxAttempts: number }
    | { type: 'FINISHED' }
    | { type: 'FAILED'; message: string }
    | { type: 'USAGE_LIMIT'; trigger: PaywallCapTrigger }
    | { type: 'CLEAR_ERROR' }
    | { type: 'CLEAR_USAGE_LIMIT' };

function updateLastAssistant(
    messages: UIMessage[],
    updater: (message: UIMessage) => UIMessage,
): UIMessage[] {
    const lastMessage = messages[messages.length - 1];

    if (lastMessage?.role !== 'assistant') {
        return messages;
    }

    const updated = updater(lastMessage);

    return updated === lastMessage
        ? messages
        : [...messages.slice(0, -1), updated];
}

function appendText(message: UIMessage, delta: string): UIMessage {
    const parts = [...(message.parts ?? [])];
    const lastPart = parts[parts.length - 1];

    if (lastPart?.type === 'text') {
        parts[parts.length - 1] = {
            ...lastPart,
            text: `${lastPart.text}${delta}`,
        };
    } else {
        parts.push({ type: 'text', text: delta });
    }

    return { ...message, parts };
}

function appendPart(
    message: UIMessage,
    part: UIMessage['parts'][number],
): UIMessage {
    return { ...message, parts: [...(message.parts ?? []), part] };
}

export function chatReducer(state: ChatState, action: ChatAction): ChatState {
    switch (action.type) {
        case 'SET_MESSAGES':
            return state.status !== 'ready' ||
                state.messages === action.messages
                ? state
                : { ...state, messages: action.messages };

        case 'ADD_USER_MESSAGE':
            return {
                ...state,
                status: 'submitted',
                error: undefined,
                usageLimitTrigger: null,
                messages: [...state.messages, action.message],
            };

        case 'PROCESSING':
            return state.status === 'streaming'
                ? state
                : { ...state, status: 'streaming', error: undefined };

        case 'STREAM_START': {
            const lastMessage = state.messages[state.messages.length - 1];

            if (lastMessage?.role === 'assistant') {
                return { ...state, status: 'streaming', error: undefined };
            }

            return {
                ...state,
                status: 'streaming',
                error: undefined,
                messages: [
                    ...state.messages,
                    { id: action.id, role: 'assistant', parts: [] },
                ],
            };
        }

        case 'APPEND_TEXT':
            return {
                ...state,
                messages: updateLastAssistant(state.messages, (message) =>
                    appendText(message, action.delta),
                ),
            };

        case 'ADD_CITATION':
            return {
                ...state,
                messages: updateLastAssistant(state.messages, (message) =>
                    appendPart(message, {
                        type: 'source-url',
                        sourceId: action.citation.url,
                        url: action.citation.url,
                        title: action.citation.title ?? action.citation.url,
                    } as UIMessage['parts'][number]),
                ),
            };

        case 'ADD_APPROVAL':
            return {
                ...state,
                messages: updateLastAssistant(state.messages, (message) =>
                    appendPart(message, {
                        type: 'data-approval',
                        data: {
                            approvalId: action.approvalId,
                            card: action.card,
                        },
                    } as UIMessage['parts'][number]),
                ),
            };

        case 'RETRYING':
            return {
                ...state,
                status: 'streaming',
                error: new Error(
                    `Retrying... ${action.attempt}/${action.maxAttempts}`,
                ),
                messages: updateLastAssistant(state.messages, (message) =>
                    (message.parts?.length ?? 0) === 0
                        ? message
                        : { ...message, parts: [] },
                ),
            };

        case 'FINISHED':
            return state.status === 'ready'
                ? state
                : { ...state, status: 'ready' };

        case 'FAILED':
            return {
                ...state,
                status: 'error',
                error: new Error(action.message),
            };

        case 'USAGE_LIMIT':
            return {
                ...state,
                status: 'error',
                error: undefined,
                usageLimitTrigger: action.trigger,
            };

        case 'CLEAR_ERROR':
            return state.error ? { ...state, error: undefined } : state;

        case 'CLEAR_USAGE_LIMIT':
            return state.usageLimitTrigger
                ? { ...state, usageLimitTrigger: null }
                : state;
    }
}

export function createInitialState(initialMessages: UIMessage[]): ChatState {
    return {
        messages: initialMessages,
        status: 'ready',
        error: undefined,
        usageLimitTrigger: null,
    };
}
