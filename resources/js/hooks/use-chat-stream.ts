import type { AIModel, ChatMode } from '@/pages/chat/chat-input';
import { stream } from '@/routes/chat';
import type { ChatStatus } from '@/types/chat';
import { useChat, type UIMessage } from '@ai-sdk/react';
import type { FileUIPart } from 'ai';
import { DefaultChatTransport } from 'ai';
import { useCallback, useMemo, useRef, useState } from 'react';

interface UseChatStreamOptions {
    conversationId: string;
    mode: ChatMode;
    model: AIModel;
    initialMessages: UIMessage[];
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
}

export function useChatStream({
    conversationId,
    mode,
    model,
    initialMessages,
}: UseChatStreamOptions): UseChatStreamReturn {
    const modelRef = useRef({ model, mode });
    modelRef.current = { model, mode };
    const [networkError, setNetworkError] = useState<Error | undefined>();

    const transport = useMemo(
        () =>
            new DefaultChatTransport({
                api: stream.url(conversationId),
                body: () => modelRef.current,
            }),
        [conversationId],
    );

    const {
        messages,
        sendMessage: originalSendMessage,
        status,
        error,
    } = useChat({
        messages: initialMessages,
        transport,
    });

    const sendMessage = useCallback(
        (message: { text: string; files?: FileUIPart[] }) => {
            setNetworkError(undefined);

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
    };
}
