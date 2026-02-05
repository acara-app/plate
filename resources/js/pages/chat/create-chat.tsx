import { useChatStream } from '@/hooks/use-chat-stream';
import AppLayout from '@/layouts/app-layout';
import { generateUUID } from '@/lib/utils';
import chat from '@/routes/chat';
import type { BreadcrumbItem } from '@/types';
import type { ChatPageProps } from '@/types/chat';
import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import ChatInput, { type AIModel, type ChatMode } from './chat-input';
import ChatMessages from './chate-messages';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Chat',
        href: chat.create().url,
    },
];

export default function CreateChat() {
    const { conversationId: initialConversationId } =
        usePage<ChatPageProps>().props;

    const [conversationId, setConversationId] = useState<string | undefined>(
        initialConversationId,
    );
    const [mode, setMode] = useState<ChatMode>('ask');
    const [model, setModel] = useState<AIModel>('gemini-3-flash-preview');

    const { messages, sendMessage, status, error, isStreaming, isSubmitting } =
        useChatStream({
            conversationId,
            mode,
            model,
        });

    function handleSubmit(
        message: string,
        chatMode: ChatMode,
        aiModel: AIModel,
    ) {
        if (!message.trim()) return;

        // Generate conversationId for new conversations
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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Chat" />
            <div className="flex h-full flex-1 flex-col items-center justify-center p-8">
                <div className="flex w-full max-w-4xl flex-col items-center gap-8">
                    <ChatMessages messages={messages} status={status} />
                    <ChatInput
                        className="w-full"
                        onSubmit={handleSubmit}
                        disabled={isStreaming || isSubmitting}
                    />
                    {error && (
                        <div className="text-sm text-red-500">
                            Error: {error.message}
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
