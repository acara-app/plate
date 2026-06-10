import { stream } from '@/routes/chat';
import { stop as stopStream } from '@/routes/chat/stream';
import type { PaywallCapTrigger, SubscriptionTier } from '@/types';
import type { ChatStatus } from '@/types/chat';
import type { FileUIPart, UIMessage } from 'ai';
import { useCallback, useEffect, useReducer, useRef } from 'react';
import { chatReducer, createInitialState } from './chat/message-reducer';
import { useStreamChannel } from './chat/use-stream-channel';
import { useStreamRecovery } from './chat/use-stream-recovery';

interface UseChatStreamOptions {
    conversationId: string;
    userId: number;
    initialMessages: UIMessage[];
    initialStreaming?: boolean;
    onFinish?: () => void;
}

interface UseChatStreamReturn {
    messages: UIMessage[];
    sendMessage: (message: { text: string; files?: FileUIPart[] }) => void;
    stop: () => void;
    clearError: () => void;
    status: ChatStatus;
    error: Error | undefined;
    isStreaming: boolean;
    isSubmitting: boolean;
    isConnected: boolean;
    usageLimitTrigger: PaywallCapTrigger | null;
    clearUsageLimitTrigger: () => void;
}

interface UsageLimitExceededPayload {
    error: 'usage_limit_exceeded';
    limit_type: 'rolling' | 'weekly';
    tier: SubscriptionTier;
    current_credits: number;
    limit_credits: number;
    resets_in: string;
}

function isUsageLimitPayload(body: unknown): body is UsageLimitExceededPayload {
    return (
        typeof body === 'object' &&
        body !== null &&
        (body as { error?: unknown }).error === 'usage_limit_exceeded'
    );
}

export function useChatStream({
    conversationId,
    userId,
    initialMessages,
    initialStreaming = false,
    onFinish,
}: UseChatStreamOptions): UseChatStreamReturn {
    const [state, dispatch] = useReducer(
        chatReducer,
        initialMessages,
        createInitialState,
    );

    const seenEventIdsRef = useRef<Set<string>>(new Set());
    const streamActiveRef = useRef(false);
    const onFinishRef = useRef(onFinish);
    onFinishRef.current = onFinish;

    const {
        startReplayPolling,
        stopReplayPolling,
        finishStream,
        resumeOnMount,
        resetReplayState,
    } = useStreamRecovery({
        conversationId,
        dispatch,
        seenEventIdsRef,
        streamActiveRef,
        onFinishRef,
    });

    const { isConnected } = useStreamChannel({
        userId,
        status: state.status,
        dispatch,
        seenEventIdsRef,
        streamActiveRef,
        startReplayPolling,
        stopReplayPolling,
        finishStream,
        resetReplayState,
    });

    useEffect(() => {
        // initialStreaming = a turn was started server-side (e.g. from the
        // dashboard composer); force polling because it may not have buffered
        // its first event yet when this mounts.
        void resumeOnMount(initialStreaming);
    }, [resumeOnMount, initialStreaming]);

    useEffect(() => {
        dispatch({ type: 'SET_MESSAGES', messages: initialMessages });
    }, [initialMessages]);

    const clearError = useCallback(() => {
        dispatch({ type: 'CLEAR_ERROR' });
    }, []);

    const clearUsageLimitTrigger = useCallback(() => {
        dispatch({ type: 'CLEAR_USAGE_LIMIT' });
    }, []);

    const sendMessage = useCallback(
        (message: { text: string; files?: FileUIPart[] }) => {
            const parts: UIMessage['parts'] = [
                { type: 'text', text: message.text },
                ...(message.files ?? []),
            ];

            resetReplayState();

            dispatch({
                type: 'ADD_USER_MESSAGE',
                message: { id: `user-${Date.now()}`, role: 'user', parts },
            });

            void fetch(stream.url(conversationId), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ messages: [{ role: 'user', parts }] }),
            })
                .then(async (response) => {
                    if (response.status === 402) {
                        const body: unknown = await response.clone().json();

                        if (isUsageLimitPayload(body)) {
                            dispatch({
                                type: 'USAGE_LIMIT',
                                trigger: {
                                    kind: 'cap',
                                    limitType: body.limit_type,
                                    currentTier: body.tier,
                                    currentCredits: body.current_credits,
                                    limitCredits: body.limit_credits,
                                    resetsIn: body.resets_in,
                                },
                            });

                            return;
                        }
                    }

                    if (!response.ok) {
                        throw new Error('Failed to send message.');
                    }

                    startReplayPolling();
                })
                .catch((error: unknown) => {
                    stopReplayPolling();
                    dispatch({
                        type: 'FAILED',
                        message:
                            error instanceof Error
                                ? error.message
                                : 'Failed to send message.',
                    });
                });
        },
        [
            conversationId,
            resetReplayState,
            startReplayPolling,
            stopReplayPolling,
        ],
    );

    const stop = useCallback(() => {
        stopReplayPolling();
        dispatch({ type: 'FINISHED' });

        void fetch(stopStream.url(conversationId), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
        }).catch(() => {});
    }, [conversationId, stopReplayPolling]);

    return {
        messages: state.messages,
        sendMessage,
        stop,
        clearError,
        status: state.status,
        error: state.error,
        isStreaming: state.status === 'streaming',
        isSubmitting: state.status === 'submitted',
        isConnected,
        usageLimitTrigger: state.usageLimitTrigger,
        clearUsageLimitTrigger,
    };
}
