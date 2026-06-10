import { reconnect, useSsrSafeConnectionStatus } from '@/lib/echo';
import { useEcho } from '@laravel/echo-react';
import { useEffect, useRef } from 'react';
import type { ChatAction, ChatStatus } from './message-reducer';
import { applyStreamEvent, type RawStreamEvent } from './process-event';

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
    '.provider_tool',
    '.citation',
];

interface RetryingPayload {
    attempt?: number;
    maxAttempts?: number;
}

interface ChatErrorPayload {
    message?: string;
}

interface UseStreamChannelOptions {
    userId: number;
    status: ChatStatus;
    dispatch: React.Dispatch<ChatAction>;
    seenEventIdsRef: React.RefObject<Set<string>>;
    streamActiveRef: React.RefObject<boolean>;
    startReplayPolling: () => void;
    stopReplayPolling: () => void;
    finishStream: () => void;
    resetReplayState: () => void;
}

interface UseStreamChannelReturn {
    isConnected: boolean;
}

export function useStreamChannel({
    userId,
    status,
    dispatch,
    seenEventIdsRef,
    streamActiveRef,
    startReplayPolling,
    stopReplayPolling,
    finishStream,
    resetReplayState,
}: UseStreamChannelOptions): UseStreamChannelReturn {
    const channelName = `chat.${userId}`;
    const connectionStatus = useSsrSafeConnectionStatus();
    const wasStreamingRef = useRef(false);
    const previousConnectionStatusRef = useRef(connectionStatus);

    useEcho<RawStreamEvent>(
        channelName,
        STREAM_EVENTS,
        (raw) => {
            stopReplayPolling();

            if (raw.type === 'stream_start') {
                streamActiveRef.current = true;
            }

            applyStreamEvent(raw, dispatch, seenEventIdsRef.current);
        },
        [stopReplayPolling, streamActiveRef, dispatch, seenEventIdsRef],
    );

    useEcho(
        channelName,
        '.processing',
        () => {
            stopReplayPolling();
            streamActiveRef.current = true;
            dispatch({ type: 'PROCESSING' });
        },
        [stopReplayPolling, streamActiveRef, dispatch],
    );

    useEcho<RetryingPayload>(
        channelName,
        '.retrying',
        (event) => {
            resetReplayState();
            dispatch({
                type: 'RETRYING',
                attempt: event.attempt ?? 1,
                maxAttempts: event.maxAttempts ?? 3,
            });
        },
        [resetReplayState, dispatch],
    );

    useEcho(
        channelName,
        '.stream_end',
        () => {
            finishStream();
        },
        [finishStream],
    );

    useEcho<ChatErrorPayload>(
        channelName,
        '.error',
        (event) => {
            stopReplayPolling();
            dispatch({
                type: 'FAILED',
                message: event.message ?? 'Failed to process message.',
            });
        },
        [stopReplayPolling, dispatch],
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
