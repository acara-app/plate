import { useChatStream } from '@/hooks/use-chat-stream';
import AppLayout from '@/layouts/app-layout';
import { generateUUID } from '@/lib/utils';
import chat from '@/routes/chat';
import type { BreadcrumbItem } from '@/types';
import type { ChatPageProps, UIMessage } from '@/types/chat';
import { Head, router, usePage } from '@inertiajs/react';
import type { FileUIPart } from 'ai';
import { useEffect, useRef, useState } from 'react';
import ChatInput, { type AIModel, type ChatMode } from './chat-input';

import ChatMessages, { ChatErrorBanner } from './chat-messages';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Chat (beta version)',
        href: '#',
    },
];

export default function CreateChat() {
    const {
        conversationId: initialConversationId,
        messages: messageHistories,
        mode: initialMode,
    } = usePage<ChatPageProps>().props;

    const [conversationId, setConversationId] = useState<string>(
        initialConversationId,
    );
    const [mode, setMode] = useState<ChatMode>(initialMode ?? 'ask');
    const [model, setModel] = useState<AIModel>('gpt-5-mini');

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

    function handleSubmit(message: string, files?: FileUIPart[]) {
        if (!message.trim() && (!files || files.length === 0)) {
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

        sendMessage({ text: message, files });
    }

    const showThinkingIndicator = isSubmitting && messages.length > 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs} fixedHeight>
            <Head title="Chat" />
            <section className="flex min-h-0 flex-1 flex-col overflow-hidden">
                <div className="min-h-0 flex-1 overflow-y-auto scroll-smooth">
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
                        onModeChange={setMode}
                        onModelChange={setModel}
                        disabled={isStreaming || isSubmitting}
                        isLoading={isStreaming || isSubmitting}
                        mode={mode}
                        model={model}
                    />
                    <p className="px-4 pb-4 text-center text-xs text-muted-foreground">
                        For informational purposes only. Not a substitute for
                        professional medical or nutritional advice.
                    </p>
                </div>
            </section>
        </AppLayout>
    );
}
