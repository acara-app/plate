import { reconnect, useSsrSafeConnectionStatus } from '@/lib/echo';
import { useEcho } from '@laravel/echo-react';
import { useEffect, useRef } from 'react';
import type { ChatAction, ChatStatus } from './message-reducer';
import {
    applyStreamEvent,
    type RawStreamEvent,
    type StreamTracking,
} from './process-event';
import type { LiveEventDecision } from './use-stream-recovery';

const STREAM_EVENTS = [
    '.stream_start',
    '.text_start',
    '.text_delta',
    '.text_complete',
    '.thinking_start',
    '.thinking',
    '.thinking_complete',
    '.tool_call',
    '.tool_result',
    '.tool_approval_request',
    '.provider_tool',
    '.citation',
    '.visual_patch',
    '.visual_end',
];

interface LifecyclePayload {
    conversationId?: string;
}

interface RetryingPayload extends LifecyclePayload {
    attempt?: number;
    maxAttempts?: number;
}

interface ChatErrorPayload extends LifecyclePayload {
    message?: string;
}

interface StreamEndPayload extends RawStreamEvent, LifecyclePayload {}

interface UseStreamChannelOptions {
    userId: number;
    conversationId: string;
    status: ChatStatus;
    dispatch: React.Dispatch<ChatAction>;
    seenEventIdsRef: React.RefObject<Set<string>>;
    trackingRef: React.RefObject<StreamTracking>;
    streamActiveRef: React.RefObject<boolean>;
    startReplayPolling: () => void;
    stopReplayPolling: () => void;
    finishStream: () => void;
    resetReplayState: () => void;
    classifyLiveEvent: (raw: RawStreamEvent) => LiveEventDecision;
    catchUp: () => Promise<void>;
}

interface UseStreamChannelReturn {
    isConnected: boolean;
}

export function useStreamChannel({
    userId,
    conversationId,
    status,
    dispatch,
    seenEventIdsRef,
    trackingRef,
    streamActiveRef,
    startReplayPolling,
    stopReplayPolling,
    finishStream,
    resetReplayState,
    classifyLiveEvent,
    catchUp,
}: UseStreamChannelOptions): UseStreamChannelReturn {
    const channelName = `chat.${userId}`;
    const connectionStatus = useSsrSafeConnectionStatus();
    const wasStreamingRef = useRef(false);
    const previousConnectionStatusRef = useRef(connectionStatus);

    useEcho<RawStreamEvent>(
        channelName,
        STREAM_EVENTS,
        (raw) => {
            if (raw.conversation_id !== conversationId) {
                return;
            }

            const decision = classifyLiveEvent(raw);

            if (decision === 'gap') {
                void catchUp();

                return;
            }

            if (decision === 'duplicate') {
                return;
            }

            stopReplayPolling();

            if (raw.type === 'stream_start') {
                streamActiveRef.current = true;
            }

            applyStreamEvent(
                raw,
                dispatch,
                seenEventIdsRef.current,
                trackingRef.current,
            );
        },
        [
            conversationId,
            classifyLiveEvent,
            catchUp,
            stopReplayPolling,
            streamActiveRef,
            dispatch,
            seenEventIdsRef,
            trackingRef,
        ],
    );

    useEcho<LifecyclePayload>(
        channelName,
        '.processing',
        (event) => {
            if (event.conversationId !== conversationId) {
                return;
            }

            stopReplayPolling();
            streamActiveRef.current = true;
            dispatch({ type: 'PROCESSING' });
        },
        [conversationId, stopReplayPolling, streamActiveRef, dispatch],
    );

    useEcho<RetryingPayload>(
        channelName,
        '.retrying',
        (event) => {
            if (event.conversationId !== conversationId) {
                return;
            }

            resetReplayState();
            dispatch({
                type: 'RETRYING',
                attempt: event.attempt ?? 1,
                maxAttempts: event.maxAttempts ?? 3,
            });
        },
        [conversationId, resetReplayState, dispatch],
    );

    useEcho<StreamEndPayload>(
        channelName,
        '.stream_end',
        (event) => {
            if (
                (event.conversation_id ?? event.conversationId) !==
                conversationId
            ) {
                return;
            }

            const decision = classifyLiveEvent(event);

            if (decision === 'gap') {
                void catchUp().then(finishStream);

                return;
            }

            if (decision === 'apply') {
                finishStream();
            }
        },
        [conversationId, classifyLiveEvent, catchUp, finishStream],
    );

    useEcho<ChatErrorPayload>(
        channelName,
        '.error',
        (event) => {
            if (event.conversationId !== conversationId) {
                return;
            }

            stopReplayPolling();
            dispatch({
                type: 'FAILED',
                message: event.message ?? 'Failed to process message.',
            });
        },
        [conversationId, stopReplayPolling, dispatch],
    );

    useEffect(() => {
        const connected = connectionStatus === 'connected';
        const previousStatus = previousConnectionStatusRef.current;

        const active = status === 'streaming' || status === 'submitted';

        if (!connected && active) {
            wasStreamingRef.current = true;
            startReplayPolling();
        }

        if (
            connected &&
            previousStatus !== 'connected' &&
            wasStreamingRef.current
        ) {
            wasStreamingRef.current = false;
            startReplayPolling();
        }

        previousConnectionStatusRef.current = connectionStatus;
    }, [connectionStatus, status, startReplayPolling]);

    useEffect(() => {
        const handleVisibilityChange = () => {
            if (document.visibilityState !== 'visible') {
                return;
            }

            if (connectionStatus !== 'connected') {
                reconnect();
            }

            if (status === 'streaming' || wasStreamingRef.current) {
                startReplayPolling();
            }
        };

        const handleOnline = () => {
            reconnect();

            if (status === 'streaming' || status === 'submitted') {
                startReplayPolling();
            }
        };

        const handleOffline = () => {
            if (status === 'streaming' || status === 'submitted') {
                wasStreamingRef.current = true;
            }
        };

        document.addEventListener('visibilitychange', handleVisibilityChange);
        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);

        return () => {
            document.removeEventListener(
                'visibilitychange',
                handleVisibilityChange,
            );
            window.removeEventListener('online', handleOnline);
            window.removeEventListener('offline', handleOffline);
        };
    }, [connectionStatus, status, startReplayPolling]);

    return { isConnected: connectionStatus === 'connected' };
}
