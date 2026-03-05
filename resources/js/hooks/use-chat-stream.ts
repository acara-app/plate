import type { AIModel, ChatMode } from '@/pages/chat/chat-input';
import { stream } from '@/routes/chat';
import type { ChatStatus } from '@/types/chat';
import { useChat, type UIMessage } from '@ai-sdk/react';
import type { FileUIPart } from 'ai';
import { DefaultChatTransport } from 'ai';
import { useMemo, useRef } from 'react';

interface UseChatStreamOptions {
    conversationId: string;
    mode: ChatMode;
    model: AIModel;
    initialMessages: UIMessage[];
}

interface UseChatStreamReturn {
    messages: UIMessage[];
    sendMessage: (message: { text: string; files?: FileUIPart[] }) => void;
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

    const transport = useMemo(
        () =>
            new DefaultChatTransport({
                api: stream.url(conversationId),
                body: () => modelRef.current,
            }),
        [conversationId],
    );

    const { messages, sendMessage, status, error } = useChat({
        messages: initialMessages,
        transport,
    });

    const isStreaming = status === 'streaming';
    const isSubmitting = status === 'submitted';

    return {
        initialMessages,
        messages,
        sendMessage,
        status: status as ChatStatus,
        error,
        isStreaming,
        isSubmitting,
    };
}
