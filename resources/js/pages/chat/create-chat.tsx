import { CreditWarningBanner } from '@/components/billing/credit-warning-banner';
import { UsageLimitNotice } from '@/components/billing/usage-limit-notice';
import { useChatStream } from '@/hooks/use-chat-stream';
import AppLayout from '@/layouts/app-layout';
import { generateUUID } from '@/lib/utils';
import chat from '@/routes/chat';
import checkout from '@/routes/checkout';
import type { BreadcrumbItem, CreditWarning } from '@/types';
import type { ChatPageProps, UIMessage } from '@/types/chat';
import { Head, router, usePage } from '@inertiajs/react';
import type { FileUIPart } from 'ai';
import { useCallback, useEffect, useRef, useState } from 'react';
import ChatInput, { type ChatMode } from './chat-input';

import ChatMessages, { ChatErrorBanner } from './chat-messages';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Chat (beta version)',
        href: '#',
    },
];

export default function CreateChat() {
    const page = usePage<
        ChatPageProps & { creditWarning?: CreditWarning | null }
    >();
    const {
        conversationId: initialConversationId,
        messages: messageHistories,
        mode: initialMode,
        creditWarning: sharedCreditWarning,
    } = page.props;

    const [conversationId, setConversationId] = useState<string>(
        initialConversationId,
    );
    const [mode, setMode] = useState<ChatMode>(initialMode ?? 'ask');
    const [dismissedWarningAt, setDismissedWarningAt] = useState<string | null>(
        null,
    );
    const visibleCreditWarning =
        sharedCreditWarning &&
        sharedCreditWarning.resets_at !== dismissedWarningAt
            ? sharedCreditWarning
            : null;

    const messagesEndRef = useRef<HTMLDivElement>(null);
    const lastMessageRef = useRef<{
        text: string;
        files?: FileUIPart[];
    } | null>(null);

    const initialMessages = (messageHistories ?? []) as UIMessage[];

    const handleStreamFinish = useCallback(() => {
        router.reload({ only: ['creditWarning'] });
    }, []);

    const {
        messages,
        sendMessage,
        clearError,
        status,
        error,
        isStreaming,
        isSubmitting,
        usageLimitTrigger,
        clearUsageLimitTrigger,
    } = useChatStream({
        conversationId,
        mode,
        initialMessages,
        onFinish: handleStreamFinish,
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

        lastMessageRef.current = { text: message, files };

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

    const handleRetry = useCallback(() => {
        if (lastMessageRef.current) {
            sendMessage(lastMessageRef.current);
        }
    }, [sendMessage]);

    const handleInputChange = useCallback(() => {
        if (lastMessageRef.current) {
            lastMessageRef.current = null;
        }
        if (error) {
            clearError();
        }
    }, [error, clearError]);

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
                        {!usageLimitTrigger && (
                            <ChatErrorBanner
                                error={error}
                                onRetry={
                                    lastMessageRef.current
                                        ? handleRetry
                                        : undefined
                                }
                            />
                        )}
                        {usageLimitTrigger && (
                            <div className="mt-4">
                                <UsageLimitNotice
                                    trigger={usageLimitTrigger}
                                    onDismiss={() => {
                                        clearUsageLimitTrigger();
                                        clearError();
                                    }}
                                />
                            </div>
                        )}
                        <div ref={messagesEndRef} />
                    </div>
                </div>

                <div className="shrink-0 space-y-2 bg-background">
                    {visibleCreditWarning && (
                        <div className="mx-auto w-full max-w-3xl px-4">
                            <CreditWarningBanner
                                warning={visibleCreditWarning}
                                onDismiss={() =>
                                    setDismissedWarningAt(
                                        visibleCreditWarning.resets_at,
                                    )
                                }
                                onUpgradeClick={() => {
                                    router.visit(checkout.subscription().url);
                                }}
                            />
                        </div>
                    )}
                    <ChatInput
                        className="w-full"
                        onSubmit={handleSubmit}
                        onInputChange={handleInputChange}
                        onModeChange={setMode}
                        disabled={isStreaming || isSubmitting}
                        isLoading={isStreaming || isSubmitting}
                        mode={mode}
                    />
                    <p className="px-2 pb-2 text-center text-xs text-muted-foreground sm:px-4 sm:pb-4 sm:text-sm">
                        ⚠️ For informational purposes only. Not a substitute for
                        professional medical or nutritional advice.
                    </p>
                </div>
            </section>
        </AppLayout>
    );
}
