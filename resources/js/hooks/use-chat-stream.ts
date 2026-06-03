import chat from '@/routes/chat';
import type { PaywallCapTrigger, SubscriptionTier } from '@/types';
import type { ActiveStream, ChatStatus } from '@/types/chat';
import { useChat, type UIMessage } from '@ai-sdk/react';
import type { ChatOnFinishCallback, FileUIPart } from 'ai';
import { DefaultChatTransport } from 'ai';
import { useCallback, useMemo, useState } from 'react';

interface UseChatStreamOptions {
    conversationId: string;
    initialMessages: UIMessage[];
    onFinish?: ChatOnFinishCallback<UIMessage>;
    activeStream?: ActiveStream | null;
}

interface UseChatStreamReturn {
    messages: UIMessage[];
    sendMessage: (message: { text: string; files?: FileUIPart[] }) => void;
    clearError: () => void;
    status: ChatStatus;
    error: Error | undefined;
    isStreaming: boolean;
    isSubmitting: boolean;
    initialMessages: UIMessage[];
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
    if (typeof body !== 'object' || body === null) {
        return false;
    }
    const candidate = body as Record<string, unknown>;
    return candidate.error === 'usage_limit_exceeded';
}

export function useChatStream({
    conversationId,
    initialMessages,
    onFinish,
    activeStream,
}: UseChatStreamOptions): UseChatStreamReturn {
    const [networkError, setNetworkError] = useState<Error | undefined>();
    const [usageLimitTrigger, setUsageLimitTrigger] =
        useState<PaywallCapTrigger | null>(null);

    const activeRunId = activeStream?.run_id ?? null;

    const transport = useMemo(
        () =>
            new DefaultChatTransport({
                api: chat.stream.url(conversationId),
                prepareReconnectToStreamRequest: activeRunId
                    ? () => ({
                          api: chat.stream.resume.url({
                              conversation: conversationId,
                              run: activeRunId,
                          }),
                      })
                    : undefined,
                fetch: async (input, init) => {
                    const response = await fetch(input, init);

                    if (response.status === 402) {
                        try {
                            const body: unknown = await response.clone().json();
                            if (isUsageLimitPayload(body)) {
                                setUsageLimitTrigger({
                                    kind: 'cap',
                                    limitType: body.limit_type,
                                    currentTier: body.tier,
                                    currentCredits: body.current_credits,
                                    limitCredits: body.limit_credits,
                                    resetsIn: body.resets_in,
                                });
                            }
                        } catch {
                            // Non-JSON 402 — fall through and let useChat surface the error.
                        }
                    }

                    return response;
                },
            }),
        [conversationId, activeRunId],
    );

    const seededMessages = useMemo<UIMessage[]>(() => {
        if (!activeStream) {
            return initialMessages;
        }

        const inflightUserMessage = {
            id: `inflight-${activeStream.run_id}`,
            role: 'user',
            parts: [{ type: 'text', text: activeStream.prompt }],
        } as UIMessage;

        return [...initialMessages, inflightUserMessage];
    }, [initialMessages, activeStream]);

    const {
        messages,
        sendMessage: originalSendMessage,
        status,
        error,
    } = useChat({
        messages: seededMessages,
        transport,
        resume: Boolean(activeRunId),
        onFinish,
    });

    const sendMessage = useCallback(
        (message: { text: string; files?: FileUIPart[] }) => {
            setNetworkError(undefined);
            setUsageLimitTrigger(null);

            try {
                originalSendMessage(message);
            } catch (e) {
                const errorMessage =
                    e instanceof Error
                        ? e.message
                        : 'Failed to send message. Please try again.';
                setNetworkError(new Error(errorMessage));
            }
        },
        [originalSendMessage],
    );

    const clearError = useCallback(() => {
        setNetworkError(undefined);
    }, []);

    const clearUsageLimitTrigger = useCallback(() => {
        setUsageLimitTrigger(null);
    }, []);

    const combinedError = error ?? networkError;
    const isStreaming = status === 'streaming';
    const isSubmitting = status === 'submitted';

    return {
        initialMessages,
        messages,
        sendMessage,
        clearError,
        status: status as ChatStatus,
        error: combinedError,
        isStreaming,
        isSubmitting,
        usageLimitTrigger,
        clearUsageLimitTrigger,
    };
}
