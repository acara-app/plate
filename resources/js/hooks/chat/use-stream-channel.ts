import {
    echo,
    getConnectionState,
    onConnectionStateChange,
    reconnect,
} from '@/lib/echo';
import { useEffect, useRef, useState } from 'react';
import type { ChatAction, ChatStatus } from './message-reducer';
import { applyStreamEvent, type RawStreamEvent } from './process-event';

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
    const [isConnected, setIsConnected] = useState(
        () => getConnectionState() === 'connected',
    );
    const wasStreamingRef = useRef(false);

    useEffect(() => {
        const channelName = `chat.${userId}`;
        const channel = echo.private(channelName);

        const handleStreamEvent = (raw: RawStreamEvent) => {
            stopReplayPolling();

            if (raw.type === 'stream_start') {
                streamActiveRef.current = true;
            }

            applyStreamEvent(raw, dispatch, seenEventIdsRef.current);
        };

        channel.listen('.processing', () => {
            streamActiveRef.current = true;
            dispatch({ type: 'PROCESSING' });
        });

        channel.listen('.stream_start', handleStreamEvent);
        channel.listen('.text_delta', handleStreamEvent);
        channel.listen('.citation', handleStreamEvent);
        channel.listen('.tool_result', handleStreamEvent);

        channel.listen('.retrying', (event: RetryingPayload) => {
            resetReplayState();
            dispatch({
                type: 'RETRYING',
                attempt: event.attempt ?? 1,
                maxAttempts: event.maxAttempts ?? 3,
            });
        });

        channel.listen('.stream_end', () => {
            finishStream();
        });

        channel.listen('.error', (event: ChatErrorPayload) => {
            stopReplayPolling();
            dispatch({
                type: 'FAILED',
                message: event.message ?? 'Failed to process message.',
            });
        });

        return () => {
            channel.stopListening('.processing');
            channel.stopListening('.stream_start');
            channel.stopListening('.text_delta');
            channel.stopListening('.citation');
            channel.stopListening('.tool_result');
            channel.stopListening('.retrying');
            channel.stopListening('.stream_end');
            channel.stopListening('.error');
            echo.leave(channelName);
        };
    }, [
        userId,
        dispatch,
        seenEventIdsRef,
        streamActiveRef,
        stopReplayPolling,
        finishStream,
        resetReplayState,
    ]);

    useEffect(() => {
        return onConnectionStateChange((state, previousState) => {
            const connected = state === 'connected';
            setIsConnected(connected);

            const active = status === 'streaming' || status === 'submitted';

            if (!connected && active) {
                wasStreamingRef.current = true;
                startReplayPolling();
            }

            if (
                connected &&
                previousState === 'disconnected' &&
                wasStreamingRef.current
            ) {
                wasStreamingRef.current = false;
                startReplayPolling();
            }
        });
    }, [status, startReplayPolling]);

    useEffect(() => {
        const handleVisibilityChange = () => {
            if (document.visibilityState !== 'visible') {
                return;
            }

            if (getConnectionState() !== 'connected') {
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
    }, [status, startReplayPolling]);

    return { isConnected };
}
