import { useChatStream } from '@/hooks/use-chat-stream';
import AppLayout from '@/layouts/app-layout';
import { generateUUID } from '@/lib/utils';
import chat from '@/routes/chat';
import type { BreadcrumbItem } from '@/types';
import type { ChatPageProps, UIMessage } from '@/types/chat';
import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import ChatInput, { type AIModel, type ChatMode } from './chat-input';
import ChatMessages, { ChatErrorBanner } from './chate-messages';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Chat',
        href: chat.create().url,
    },
];

export default function CreateChat() {
    const {
        conversationId: initialConversationId,
        messages: messageHistories,
        mode: initialMode,
    } = usePage<ChatPageProps>().props;

    const [conversationId, setConversationId] = useState<string | undefined>(
        initialConversationId,
    );
    const [mode, setMode] = useState<ChatMode>(initialMode ?? 'ask');
    const [model, setModel] = useState<AIModel>('gemini-3-flash-preview');

    const messagesEndRef = useRef<HTMLDivElement>(null);

    const initialMessages = (messageHistories ?? []) as UIMessage[];

    const { messages, sendMessage, status, error, isStreaming, isSubmitting } =
        useChatStream({
            conversationId,
            mode,
            model,
            initialMessages,
        });

    useEffect(() => {
        if (messagesEndRef.current) {
            messagesEndRef.current.scrollIntoView({ behavior: 'smooth' });
        }
    }, [messages]);

    function handleSubmit(
        message: string,
        chatMode: ChatMode,
        aiModel: AIModel,
    ) {
        if (!message.trim()) {
            return;
        }

        const id = conversationId ?? generateUUID();
        if (!conversationId) {
            setConversationId(id);
            router.visit(chat.create(id).url, {
                replace: true,
                preserveState: true,
            });
        }

        setMode(chatMode);
        setModel(aiModel);
        sendMessage({ text: message });
    }

    const showThinkingIndicator = isSubmitting && messages.length > 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Chat" />
            <main className="flex h-[calc(100vh-4rem)] flex-col overflow-hidden">
                <div className="flex-1 overflow-y-auto scroll-smooth">
                    <div className="mx-auto w-full max-w-3xl px-4 py-6">
                        <ChatMessages
                            messages={messages}
                            status={status}
                            isSubmitting={showThinkingIndicator}
                        />
                        <ChatErrorBanner error={error} />
                        <div ref={messagesEndRef} />
                    </div>
                </div>

                <div className="shrink-0 bg-background">
                    <ChatInput
                        className="w-full"
                        onSubmit={handleSubmit}
                        disabled={isStreaming || isSubmitting}
                        isLoading={isStreaming || isSubmitting}
                        mode={mode}
                    />
                </div>
            </main>
        </AppLayout>
    );
}
