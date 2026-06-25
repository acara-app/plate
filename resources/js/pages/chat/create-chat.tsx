import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { CreditWarningBanner } from '@/components/billing/credit-warning-banner';
import { LifecycleBanner } from '@/components/billing/lifecycle-banner';
import { UsageLimitNotice } from '@/components/billing/usage-limit-notice';
import { Button } from '@/components/ui/button';
import { ButtonGroup } from '@/components/ui/button-group';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarTrigger } from '@/components/ui/sidebar';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useChatStream } from '@/hooks/use-chat-stream';
import useSharedProps from '@/hooks/use-shared-props';
import { cn, generateUUID } from '@/lib/utils';
import chat from '@/routes/chat';
import checkout from '@/routes/checkout';
import type { CreditWarning } from '@/types';
import type { ChatPageProps, UIMessage } from '@/types/chat';
import { Head, router, usePage } from '@inertiajs/react';
import type { FileUIPart } from 'ai';
import { Bookmark, MoreHorizontal, Pin, SquarePen } from 'lucide-react';
import {
    useCallback,
    useEffect,
    useMemo,
    useRef,
    useState,
    type UIEvent,
} from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';
import ChatInput from './chat-input';

import ChatMessages, { ChatErrorBanner } from './chat-messages';

export default function CreateChat() {
    const page = usePage<
        ChatPageProps & { creditWarning?: CreditWarning | null }
    >();
    const {
        conversationId: initialConversationId,
        messages: messageHistories,
        initialPrompt,
        initialStreaming,
        isPinned,
        isKept,
        creditWarning: sharedCreditWarning,
    } = page.props;
    const { currentUser } = useSharedProps();
    const { t } = useTranslation('common');

    const [conversationId, setConversationId] = useState<string>(
        initialConversationId,
    );
    const [pinned, setPinned] = useState<boolean>(isPinned ?? false);
    const [kept, setKept] = useState<boolean>(isKept ?? false);
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
    const autoStartedPromptRef = useRef<string | null>(null);

    const initialMessages = useMemo(
        () => (messageHistories ?? []) as UIMessage[],
        [messageHistories],
    );
    const normalizedInitialPrompt = initialPrompt?.trim() ?? '';
    const shouldAutoStartInitialPrompt =
        normalizedInitialPrompt.length > 0 && initialMessages.length === 0;

    const handleStreamFinish = useCallback(() => {
        router.reload({ only: ['creditWarning'] });
    }, []);

    const togglePin = useCallback(() => {
        const next = !pinned;
        setPinned(next);

        const action = next ? chat.pin : chat.unpin;

        router.patch(
            action(conversationId).url,
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onError: () => setPinned(!next),
            },
        );
    }, [pinned, conversationId]);

    const toggleKeep = useCallback(() => {
        const next = !kept;
        setKept(next);

        const action = next ? chat.keep : chat.unkeep;

        router.patch(
            action(conversationId).url,
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onError: () => setKept(!next),
            },
        );
    }, [kept, conversationId]);

    const {
        messages,
        sendMessage,
        stop,
        clearError,
        status,
        error,
        isStreaming,
        isSubmitting,
        usageLimitTrigger,
        clearUsageLimitTrigger,
    } = useChatStream({
        conversationId,
        userId: currentUser.id,
        initialMessages,
        initialStreaming,
        onFinish: handleStreamFinish,
    });

    const [isResuming, setIsResuming] = useState<boolean>(
        initialStreaming ?? false,
    );

    useEffect(() => {
        if (status !== 'ready') {
            setIsResuming(false);
        }
    }, [status]);

    useEffect(() => {
        if (messagesEndRef.current) {
            messagesEndRef.current.scrollIntoView({ behavior: 'smooth' });
        }
    }, [messages]);

    useEffect(() => {
        if (error?.message === 'conversation_expired') {
            toast.error(t('conversations.chat_expired'));
            router.visit(chat.create(generateUUID()).url);
        }
    }, [error, t]);

    useEffect(() => {
        if (!shouldAutoStartInitialPrompt) {
            return;
        }

        const promptKey = `${conversationId}:${normalizedInitialPrompt}`;

        if (autoStartedPromptRef.current === promptKey) {
            return;
        }

        autoStartedPromptRef.current = promptKey;
        lastMessageRef.current = { text: normalizedInitialPrompt };
        sendMessage({ text: normalizedInitialPrompt });

        const url = new URL(window.location.href);

        if (!url.searchParams.has('prompt')) {
            return;
        }

        url.searchParams.delete('prompt');
        window.history.replaceState(
            window.history.state,
            '',
            `${url.pathname}${url.search}${url.hash}`,
        );
    }, [
        conversationId,
        normalizedInitialPrompt,
        sendMessage,
        shouldAutoStartInitialPrompt,
    ]);

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

    const showWorkingIndicator =
        (isSubmitting || isResuming) && messages.length > 0;

    const [isMobileNavVisible, setIsMobileNavVisible] = useState(true);
    const lastScrollTopRef = useRef(0);

    const handleScroll = useCallback((event: UIEvent<HTMLDivElement>) => {
        const scrollTop = Math.max(0, event.currentTarget.scrollTop);
        const lastScrollTop = lastScrollTopRef.current;

        if (scrollTop <= 16 || scrollTop < lastScrollTop - 4) {
            setIsMobileNavVisible(true);
        } else if (scrollTop > lastScrollTop + 4) {
            setIsMobileNavVisible(false);
        }

        lastScrollTopRef.current = scrollTop;
    }, []);

    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent
                variant="sidebar"
                className="h-svh overflow-hidden overflow-x-hidden"
            >
                <LifecycleBanner className="mx-4 mt-4" />
                <Head title="Chat" />
                <section className="relative flex min-h-0 flex-1 flex-col overflow-hidden">
                    <div
                        className={cn(
                            'absolute top-3 left-3 z-20 transition-all duration-300 ease-out md:hidden',
                            isMobileNavVisible
                                ? 'translate-y-0 opacity-100'
                                : 'pointer-events-none -translate-y-2 opacity-0',
                        )}
                    >
                        <SidebarTrigger className="size-10 rounded-full border border-border/40 bg-background/80 shadow-md backdrop-blur-md supports-[backdrop-filter]:bg-background/60" />
                    </div>
                    <div
                        className={cn(
                            'absolute top-3 right-3 z-20 transition-all duration-300 ease-out',
                            isMobileNavVisible
                                ? 'translate-y-0 opacity-100'
                                : 'pointer-events-none -translate-y-2 opacity-0 md:pointer-events-auto md:translate-y-0 md:opacity-100',
                        )}
                    >
                        <ButtonGroup className="rounded-full border border-border/40 bg-background/80 shadow-md backdrop-blur-md supports-[backdrop-filter]:bg-background/60">
                            <Tooltip>
                                <TooltipTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() =>
                                            router.visit(
                                                chat.create(generateUUID()).url,
                                            )
                                        }
                                        aria-label={t('conversations.new_chat')}
                                        className="size-10 text-muted-foreground"
                                    >
                                        <SquarePen className="size-4" />
                                    </Button>
                                </TooltipTrigger>
                                <TooltipContent>
                                    {t('conversations.new_chat')}
                                </TooltipContent>
                            </Tooltip>
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        aria-label={t('conversations.actions')}
                                        className="size-10 text-muted-foreground data-[state=open]:bg-accent"
                                    >
                                        <MoreHorizontal className="size-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" className="w-44">
                                    <DropdownMenuItem onSelect={toggleKeep}>
                                        <Bookmark
                                            className={cn(
                                                'size-4',
                                                kept && 'fill-current text-primary',
                                            )}
                                        />
                                        {t(
                                            kept
                                                ? 'conversations.unkeep'
                                                : 'conversations.keep',
                                        )}
                                    </DropdownMenuItem>
                                    <DropdownMenuItem onSelect={togglePin}>
                                        <Pin
                                            className={cn(
                                                'size-4',
                                                pinned &&
                                                    'fill-current text-primary',
                                            )}
                                        />
                                        {t(
                                            pinned
                                                ? 'conversations.unpin'
                                                : 'conversations.pin',
                                        )}
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </ButtonGroup>
                    </div>
                    <div
                        className="min-h-0 flex-1 overflow-y-auto scroll-smooth"
                        onScroll={handleScroll}
                    >
                        <div className="mx-auto w-full max-w-3xl px-4 pt-16 pb-6 md:py-6">
                            <ChatMessages
                                messages={messages}
                                status={status}
                                isSubmitting={showWorkingIndicator}
                                conversationId={conversationId}
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

                    <div className="mx-auto flex w-full max-w-3xl shrink-0 flex-col bg-background/80 backdrop-blur-md transition-colors duration-200 supports-[backdrop-filter]:bg-background/60">
                        {visibleCreditWarning && (
                            <div className="px-4 pt-2">
                                <CreditWarningBanner
                                    warning={visibleCreditWarning}
                                    onDismiss={() =>
                                        setDismissedWarningAt(
                                            visibleCreditWarning.resets_at,
                                        )
                                    }
                                    onUpgradeClick={() => {
                                        router.visit(
                                            checkout.subscription().url,
                                        );
                                    }}
                                />
                            </div>
                        )}
                        <ChatInput
                            className="w-full"
                            onSubmit={handleSubmit}
                            onStop={stop}
                            onInputChange={handleInputChange}
                            disabled={isStreaming || isSubmitting}
                            initialMessage={
                                shouldAutoStartInitialPrompt
                                    ? null
                                    : initialPrompt
                            }
                            isLoading={isStreaming || isSubmitting}
                        />
                        <p className="px-2 pb-2 text-center text-xs text-muted-foreground sm:px-4 sm:pb-4 sm:text-sm">
                            ⚠️ For informational purposes only. Not a substitute
                            for professional medical or nutritional advice.
                        </p>
                    </div>
                </section>
            </AppContent>
        </AppShell>
    );
}
