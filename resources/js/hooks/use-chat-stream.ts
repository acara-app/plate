import type { AIModel, ChatMode } from '@/pages/chat/chat-input';
import { stream } from '@/routes/chat';
import type { AgentType, ChatStatus } from '@/types/chat';
import { useChat, type UIMessage } from '@ai-sdk/react';
import { DefaultChatTransport } from 'ai';
import { useMemo } from 'react';

interface UseChatStreamOptions {
    agentType: AgentType;
    conversationId?: string;
    mode: ChatMode;
    model: AIModel;
    initialMessages: UIMessage[];
}

interface UseChatStreamReturn {
    messages: UIMessage[];
    sendMessage: (message: { text: string }) => void;
    status: ChatStatus;
    error: Error | undefined;
    isStreaming: boolean;
    isSubmitting: boolean;
    initialMessages: UIMessage[];
}

export function useChatStream({
    agentType,
    conversationId,
    mode,
    model,
    initialMessages,
}: UseChatStreamOptions): UseChatStreamReturn {
    const transport = useMemo(
        () =>
            new DefaultChatTransport({
                api: stream.url({
                    query: { agentType, mode, model, conversationId },
                }),
            }),
        [agentType, mode, model, conversationId],
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
