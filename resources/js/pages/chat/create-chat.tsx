import AppLayout from '@/layouts/app-layout';
import { stream } from '@/routes/chat';
import { type BreadcrumbItem } from '@/types';
import { useChat } from '@ai-sdk/react';
import { Head } from '@inertiajs/react';
import { DefaultChatTransport } from 'ai';
import { useMemo, useState } from 'react';
import ChatInput, { type AIModel, type ChatMode } from './chat-input';
import ChatMessages from './chate-messages';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Chat',
        href: '/chat/create',
    },
];

export default function CreateChat() {
    const [mode, setMode] = useState<ChatMode>('ask');
    const [model, setModel] = useState<AIModel>('gemini-3-flash-preview');

    const transport = useMemo(
        () =>
            new DefaultChatTransport({
                api: stream.url({ query: { mode, model } }),
            }),
        [mode, model],
    );

    const { messages, sendMessage, status, error } = useChat({
        transport,
    });

    function handleSubmit(
        message: string,
        chatMode: ChatMode,
        aiModel: AIModel,
    ) {
        if (message.trim()) {
            setMode(chatMode);
            setModel(aiModel);
            sendMessage({ text: message });
        }
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
                        isLoading={
                            status === 'streaming' || status === 'submitted'
                        }
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
