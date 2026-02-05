import type { AIModel, ChatMode } from '@/pages/chat/chat-input';
import { stream } from '@/routes/chat';
import type { ChatStatus } from '@/types/chat';
import { useChat, type UIMessage } from '@ai-sdk/react';
import { DefaultChatTransport } from 'ai';
import { useMemo } from 'react';

interface UseChatStreamOptions {
    conversationId?: string;
    mode: ChatMode;
    model: AIModel;
}

interface UseChatStreamReturn {
    messages: UIMessage[];
    sendMessage: (message: { text: string }) => void;
    status: ChatStatus;
    error: Error | undefined;
    isStreaming: boolean;
    isSubmitting: boolean;
}

export function useChatStream({
    conversationId,
    mode,
    model,
}: UseChatStreamOptions): UseChatStreamReturn {
    const transport = useMemo(
        () =>
            new DefaultChatTransport({
                api: stream.url({
                    query: { mode, model, conversationId },
                }),
            }),
        [mode, model, conversationId],
    );

    const { messages, sendMessage, status, error } = useChat({
        transport,
    });

    const isStreaming = status === 'streaming';
    const isSubmitting = status === 'submitted';

    return {
        messages,
        sendMessage,
        status: status as ChatStatus,
        error,
        isStreaming,
        isSubmitting,
    };
}
