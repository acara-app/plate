import ChatStopController from '@/actions/App/Http/Controllers/ChatStopController';
import { stream } from '@/routes/chat';
import type { PaywallCapTrigger, SubscriptionTier } from '@/types';
import type { ChatStatus } from '@/types/chat';
import { useSocketId } from '@laravel/echo-react';
import type { FileUIPart, UIMessage } from 'ai';
import { useCallback, useEffect, useReducer, useRef } from 'react';
import { chatReducer, createInitialState } from './chat/message-reducer';
import { useStreamChannel } from './chat/use-stream-channel';
import { useStreamRecovery } from './chat/use-stream-recovery';

interface UseChatStreamOptions {
    conversationId: string;
    userId: number;
    initialMessages: UIMessage[];
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
    const socketId = useSocketId();
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
        void resumeOnMount();
    }, [resumeOnMount]);

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

            const headers: Record<string, string> = {
                'Content-Type': 'application/json',
            };

            if (socketId) {
                headers['X-Socket-ID'] = socketId;
            }

            void fetch(stream.url(conversationId), {
                method: 'POST',
                headers,
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
            socketId,
            startReplayPolling,
            stopReplayPolling,
        ],
    );

    const stop = useCallback(() => {
        stopReplayPolling();
        dispatch({ type: 'FINISHED' });

        void fetch(ChatStopController.url(conversationId), {
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
