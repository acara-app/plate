import StreamEventsController from '@/actions/App/Http/Controllers/ChatStreamEventsController';
import { useCallback, useRef } from 'react';
import type { ChatAction } from './message-reducer';
import { applyStreamEvent, type RawStreamEvent } from './process-event';

const EVENT_REPLAY_INTERVAL = 1_000;
const MAX_EVENT_REPLAY_ATTEMPTS = 300;
const NO_SEQUENCE_CONSUMED = -1;

interface ReplayEnvelope {
    sequence: number;
    type: string;
    data: RawStreamEvent;
    vercel: unknown;
}

interface StreamEventsResponse {
    streaming: boolean;
    events: ReplayEnvelope[];
    lastSequence: number;
}

interface UseStreamRecoveryOptions {
    conversationId: string;
    dispatch: React.Dispatch<ChatAction>;
    seenEventIdsRef: React.RefObject<Set<string>>;
    streamActiveRef: React.RefObject<boolean>;
    onFinishRef: React.RefObject<(() => void) | undefined>;
}

interface UseStreamRecoveryReturn {
    startReplayPolling: () => void;
    stopReplayPolling: () => void;
    finishStream: () => void;
    resumeOnMount: () => Promise<void>;
    resetReplayState: () => void;
}

export function useStreamRecovery({
    conversationId,
    dispatch,
    seenEventIdsRef,
    streamActiveRef,
    onFinishRef,
}: UseStreamRecoveryOptions): UseStreamRecoveryReturn {
    const lastSequenceRef = useRef(NO_SEQUENCE_CONSUMED);
    const replayTimerRef = useRef<ReturnType<typeof setInterval> | null>(null);
    const attemptsRef = useRef(0);

    const stopReplayPolling = useCallback(() => {
        if (replayTimerRef.current) {
            clearInterval(replayTimerRef.current);
            replayTimerRef.current = null;
        }
        attemptsRef.current = 0;
    }, []);

    const resetReplayState = useCallback(() => {
        lastSequenceRef.current = NO_SEQUENCE_CONSUMED;
        seenEventIdsRef.current.clear();
    }, [seenEventIdsRef]);

    const finishStream = useCallback(() => {
        stopReplayPolling();
        resetReplayState();
        dispatch({ type: 'FINISHED' });

        if (streamActiveRef.current) {
            streamActiveRef.current = false;
            onFinishRef.current?.();
        }
    }, [
        dispatch,
        stopReplayPolling,
        resetReplayState,
        streamActiveRef,
        onFinishRef,
    ]);

    const replayEvents = useCallback(
        async (
            options: { requireStreaming?: boolean } = {},
        ): Promise<boolean> => {
            try {
                const response = await fetch(
                    StreamEventsController.url(conversationId, {
                        query: { after: lastSequenceRef.current },
                    }),
                    { credentials: 'include' },
                );

                if (!response.ok) {
                    return false;
                }

                const body = (await response.json()) as StreamEventsResponse;

                if (options.requireStreaming && !body.streaming) {
                    return false;
                }

                for (const envelope of body.events) {
                    applyStreamEvent(
                        envelope.data,
                        dispatch,
                        seenEventIdsRef.current,
                    );
                }

                if (
                    body.events.length > 0 &&
                    body.lastSequence > lastSequenceRef.current
                ) {
                    lastSequenceRef.current = body.lastSequence;
                }

                if (body.streaming) {
                    streamActiveRef.current = true;

                    return true;
                }

                if (seenEventIdsRef.current.size > 0) {
                    finishStream();
                }

                return false;
            } catch {
                return false;
            }
        },
        [
            conversationId,
            dispatch,
            seenEventIdsRef,
            streamActiveRef,
            finishStream,
        ],
    );

    const startReplayPolling = useCallback(() => {
        if (replayTimerRef.current) {
            return;
        }

        attemptsRef.current = 0;
        replayTimerRef.current = setInterval(() => {
            attemptsRef.current += 1;

            if (attemptsRef.current > MAX_EVENT_REPLAY_ATTEMPTS) {
                stopReplayPolling();

                return;
            }

            void replayEvents();
        }, EVENT_REPLAY_INTERVAL);
    }, [replayEvents, stopReplayPolling]);

    const resumeOnMount = useCallback(async () => {
        const streaming = await replayEvents({ requireStreaming: true });

        if (streaming) {
            startReplayPolling();
        }
    }, [replayEvents, startReplayPolling]);

    return {
        startReplayPolling,
        stopReplayPolling,
        finishStream,
        resumeOnMount,
        resetReplayState,
    };
}
