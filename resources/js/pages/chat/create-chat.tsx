import ShowSnapToTrackController from '@/actions/App/Http/Controllers/SnapToTrack/ShowSnapToTrackController';
import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AiAllowanceNotice } from '@/components/billing/ai-allowance';
import { LifecycleBanner } from '@/components/billing/lifecycle-banner';
import { BiometricsReviewBanner } from '@/components/biometrics-review-banner';
import { extractApprovalPayload } from '@/components/chat/approval-part';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
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
import type { AiAllowance } from '@/types';
import type { ChatPageProps, ChatStatus, UIMessage } from '@/types/chat';
import { Head, Link, router, usePage } from '@inertiajs/react';
import type { FileUIPart } from 'ai';
import {
    ArrowDown,
    Bookmark,
    Clock,
    MoreHorizontal,
    Pin,
    ScanLine,
    SquarePen,
    type LucideIcon,
} from 'lucide-react';
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
import ChatMessages, { ChatErrorNotice } from './chat-messages';

const COLUMN = 'mx-auto w-full max-w-[43rem] px-4 sm:px-6';

const PINNED_TO_BOTTOM_THRESHOLD = 96;

const DEFAULT_RETENTION_HOURS = 48;

const RETENTION_PROPS = ['isPinned', 'isKept'];

const HEADER_BUTTON =
    'inline-flex size-9 cursor-pointer items-center justify-center rounded-lg text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:ring-2 focus-visible:ring-emerald-600 dark:focus-visible:ring-emerald-400 focus-visible:outline-none data-[state=open]:bg-muted data-[state=open]:text-foreground';

function clearInitialPromptQuery() {
    const url = new URL(window.location.href);

    if (!url.searchParams.has('prompt') && !url.searchParams.has('auto_send')) {
        return;
    }

    url.searchParams.delete('prompt');
    url.searchParams.delete('auto_send');
    router.replace({
        url: `${url.pathname}${url.search}${url.hash}`,
        props: (props) => ({ ...props, initialPrompt: null }),
        preserveScroll: true,
        preserveState: true,
    });
}

function hasPendingApproval(messages: UIMessage[]): boolean {
    const lastMessage = messages.at(-1);

    if (lastMessage?.role !== 'assistant') {
        return false;
    }

    return (lastMessage.parts ?? []).some(
        (part) => extractApprovalPayload(part)?.status === 'pending',
    );
}

function useStatusAnnouncement(
    status: ChatStatus,
    messages: UIMessage[],
): string {
    const { t } = useTranslation('common');
    const [announcement, setAnnouncement] = useState('');
    const previousStatusRef = useRef(status);
    const messagesRef = useRef(messages);
    messagesRef.current = messages;

    useEffect(() => {
        const previous = previousStatusRef.current;
        previousStatusRef.current = status;

        if (status === previous) {
            return;
        }

        if (status === 'submitted') {
            setAnnouncement(t('chat.announce.working'));
        } else if (status === 'error') {
            setAnnouncement(t('chat.announce.failed'));
        } else if (status === 'ready' && previous === 'streaming') {
            setAnnouncement(
                hasPendingApproval(messagesRef.current)
                    ? t('chat.announce.approval')
                    : t('chat.announce.replied'),
            );
        }
    }, [status, t]);

    return announcement;
}

function HeaderLink({
    label,
    icon: Icon,
    href,
}: {
    label: string;
    icon: LucideIcon;
    href: string;
}) {
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Link href={href} aria-label={label} className={HEADER_BUTTON}>
                    <Icon
                        className="size-[18px]"
                        strokeWidth={1.75}
                        aria-hidden="true"
                    />
                </Link>
            </TooltipTrigger>
            <TooltipContent>{label}</TooltipContent>
        </Tooltip>
    );
}

export default function CreateChat() {
    const page = usePage<
        ChatPageProps & { aiAllowance?: AiAllowance | null }
    >();
    const {
        conversationId: initialConversationId,
        messages: messageHistories,
        initialPrompt,
        autoSendInitialPrompt = true,
        initialStreaming,
        isPinned = false,
        isKept = false,
        temporaryRetentionHours = DEFAULT_RETENTION_HOURS,
        aiAllowance,
    } = page.props;
    const { currentUser } = useSharedProps();
    const { t } = useTranslation('common');

    const [conversationId, setConversationId] = useState<string>(
        initialConversationId,
    );
    const [newChatUrl] = useState(() => chat.create(generateUUID()).url);
    const [dismissedWarningAt, setDismissedWarningAt] = useState<string | null>(
        null,
    );
    const visibleAllowance =
        aiAllowance && aiAllowance.resets_at !== dismissedWarningAt
            ? aiAllowance
            : null;

    const scrollRef = useRef<HTMLDivElement>(null);
    const pinnedToBottomRef = useRef(true);
    const lastScrollTopRef = useRef(0);
    const lastMessageRef = useRef<{
        text: string;
        files?: FileUIPart[];
    } | null>(null);
    const autoStartedPromptRef = useRef<string | null>(null);

    const [isMobileNavVisible, setIsMobileNavVisible] = useState(true);
    const [isScrolled, setIsScrolled] = useState(false);
    const [isAwayFromLatest, setIsAwayFromLatest] = useState(false);

    const initialMessages = useMemo(
        () => (messageHistories ?? []) as UIMessage[],
        [messageHistories],
    );
    const normalizedInitialPrompt = initialPrompt?.trim() ?? '';
    const shouldAutoStartInitialPrompt =
        autoSendInitialPrompt &&
        normalizedInitialPrompt.length > 0 &&
        initialMessages.length === 0;

    const handleStreamFinish = useCallback(() => {
        router.reload({ only: ['aiAllowance'] });
    }, []);

    const togglePin = useCallback(() => {
        const action = isPinned ? chat.unpin : chat.pin;

        router.patch(
            action(conversationId).url,
            {},
            {
                optimistic: () => ({ isPinned: !isPinned }),
                only: RETENTION_PROPS,
                preserveScroll: true,
                preserveState: true,
            },
        );
    }, [isPinned, conversationId]);

    const toggleKeep = useCallback(() => {
        const action = isKept ? chat.unkeep : chat.keep;

        router.patch(
            action(conversationId).url,
            {},
            {
                optimistic: () => ({ isKept: !isKept }),
                only: RETENTION_PROPS,
                preserveScroll: true,
                preserveState: true,
            },
        );
    }, [isKept, conversationId]);

    const {
        messages,
        sendMessage,
        submitApprovalDecisions,
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

    const showWorkingIndicator =
        (isSubmitting || isResuming) && messages.length > 0;

    const [stoppedMessageIds, setStoppedMessageIds] = useState<
        ReadonlySet<string>
    >(() => new Set());

    const handleStop = useCallback(() => {
        const lastMessage = messages.at(-1);

        if (lastMessage) {
            setStoppedMessageIds((previous) =>
                new Set(previous).add(lastMessage.id),
            );
        }

        stop();
    }, [messages, stop]);

    const announcement = useStatusAnnouncement(status, messages);

    const scrollToLatest = useCallback((behavior: ScrollBehavior) => {
        const container = scrollRef.current;

        if (!container) {
            return;
        }

        container.scrollTo({ top: container.scrollHeight, behavior });
    }, []);

    useEffect(() => {
        if (messages.at(-1)?.role === 'user') {
            pinnedToBottomRef.current = true;
            setIsAwayFromLatest(false);
        }

        if (pinnedToBottomRef.current) {
            scrollToLatest('instant');
        }
    }, [
        messages,
        showWorkingIndicator,
        error,
        usageLimitTrigger,
        scrollToLatest,
    ]);

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
        clearInitialPromptQuery();
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
        clearInitialPromptQuery();
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

    const handleScroll = useCallback((event: UIEvent<HTMLDivElement>) => {
        const container = event.currentTarget;
        const scrollTop = Math.max(0, container.scrollTop);
        const lastScrollTop = lastScrollTopRef.current;
        const distanceFromBottom =
            container.scrollHeight - scrollTop - container.clientHeight;
        const pinnedToBottom = distanceFromBottom < PINNED_TO_BOTTOM_THRESHOLD;

        if (scrollTop <= 16 || scrollTop < lastScrollTop - 4) {
            setIsMobileNavVisible(true);
        } else if (scrollTop > lastScrollTop + 4) {
            setIsMobileNavVisible(false);
        }

        pinnedToBottomRef.current = pinnedToBottom;
        setIsAwayFromLatest(!pinnedToBottom);
        setIsScrolled(scrollTop > 8);
        lastScrollTopRef.current = scrollTop;
    }, []);

    const isKeptForGood = isKept || isPinned;

    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent
                variant="sidebar"
                className="h-dvh overflow-hidden overflow-x-hidden md:peer-data-[variant=inset]:h-[calc(100dvh-(--spacing(4)))]"
            >
                <LifecycleBanner className="mx-4 mt-4" />
                <BiometricsReviewBanner className="mx-4 mt-4" />
                <Head title="Chat" />
                <section className="relative flex min-h-0 flex-1 flex-col overflow-hidden">
                    <header
                        className={cn(
                            'absolute inset-x-0 top-0 z-20 flex h-14 items-center justify-between gap-2 border-b bg-background px-2 transition-[translate,opacity,border-color] duration-300 ease-out sm:px-3',
                            isScrolled
                                ? 'border-border/70'
                                : 'border-transparent',
                            isMobileNavVisible
                                ? 'translate-y-0 opacity-100'
                                : 'pointer-events-none -translate-y-full opacity-0 md:pointer-events-auto md:translate-y-0 md:opacity-100',
                        )}
                    >
                        <div className="flex items-center">
                            <SidebarTrigger className="size-9 md:hidden" />
                        </div>
                        <div className="flex items-center gap-0.5">
                            <HeaderLink
                                label={t('conversations.new_chat')}
                                icon={SquarePen}
                                href={newChatUrl}
                            />
                            <HeaderLink
                                label={t('sidebar.nav.snap_to_track')}
                                icon={ScanLine}
                                href={ShowSnapToTrackController().url}
                            />
                            <DropdownMenu>
                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <DropdownMenuTrigger
                                            aria-label={t(
                                                'conversations.actions',
                                            )}
                                            className={HEADER_BUTTON}
                                        >
                                            <MoreHorizontal
                                                className="size-[18px]"
                                                strokeWidth={1.75}
                                                aria-hidden="true"
                                            />
                                        </DropdownMenuTrigger>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        {t('conversations.actions')}
                                    </TooltipContent>
                                </Tooltip>
                                <DropdownMenuContent
                                    align="end"
                                    className="w-64"
                                >
                                    <DropdownMenuLabel className="flex items-start gap-2 text-xs leading-5 font-normal text-muted-foreground">
                                        {isKeptForGood ? (
                                            <Bookmark
                                                className="mt-0.5 size-3.5 shrink-0"
                                                aria-hidden="true"
                                            />
                                        ) : (
                                            <Clock
                                                className="mt-0.5 size-3.5 shrink-0"
                                                aria-hidden="true"
                                            />
                                        )}
                                        {isKeptForGood
                                            ? t('chat.retention.kept')
                                            : t('chat.retention.temporary', {
                                                  hours: temporaryRetentionHours,
                                              })}
                                    </DropdownMenuLabel>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem onSelect={toggleKeep}>
                                        <Bookmark
                                            className={cn(
                                                'size-4',
                                                isKept &&
                                                    'fill-current text-primary',
                                            )}
                                        />
                                        {t(
                                            isKept
                                                ? 'conversations.unkeep'
                                                : 'conversations.keep',
                                        )}
                                    </DropdownMenuItem>
                                    <DropdownMenuItem onSelect={togglePin}>
                                        <Pin
                                            className={cn(
                                                'size-4',
                                                isPinned &&
                                                    'fill-current text-primary',
                                            )}
                                        />
                                        {t(
                                            isPinned
                                                ? 'conversations.unpin'
                                                : 'conversations.pin',
                                        )}
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </header>

                    <div
                        ref={scrollRef}
                        onScroll={handleScroll}
                        className="@container min-h-0 flex-1 overflow-y-auto overscroll-contain"
                    >
                        <div
                            className={cn(
                                COLUMN,
                                'flex min-h-full flex-col pt-20 pb-12',
                            )}
                        >
                            <h1 className="sr-only">{t('chat.page_title')}</h1>
                            <ChatMessages
                                messages={messages}
                                status={status}
                                retentionHours={temporaryRetentionHours}
                                stoppedMessageIds={stoppedMessageIds}
                                isSubmitting={showWorkingIndicator}
                                onApprovalDecision={submitApprovalDecisions}
                            />
                            {!usageLimitTrigger && (
                                <ChatErrorNotice
                                    error={error}
                                    status={status}
                                    onRetry={
                                        lastMessageRef.current
                                            ? handleRetry
                                            : undefined
                                    }
                                />
                            )}
                            {usageLimitTrigger && (
                                <div className="mt-6">
                                    <AiAllowanceNotice
                                        allowance={usageLimitTrigger}
                                        onDismiss={() => {
                                            clearUsageLimitTrigger();
                                            clearError();
                                        }}
                                    />
                                </div>
                            )}
                        </div>
                    </div>

                    <div className="relative shrink-0 bg-background">
                        <div
                            aria-hidden="true"
                            className="pointer-events-none absolute inset-x-0 -top-8 h-8 bg-linear-to-t from-background to-transparent"
                        />
                        <div
                            className={cn(
                                COLUMN,
                                'relative pb-[max(1rem,env(safe-area-inset-bottom))]',
                            )}
                        >
                            {isAwayFromLatest && messages.length > 0 && (
                                <button
                                    type="button"
                                    onClick={() => scrollToLatest('smooth')}
                                    aria-label={t('chat.scroll_to_latest')}
                                    className="absolute -top-12 left-1/2 inline-flex size-9 -translate-x-1/2 cursor-pointer items-center justify-center rounded-full border border-border bg-background text-muted-foreground shadow-xs transition-colors duration-200 hover:text-foreground focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:outline-none motion-safe:animate-in motion-safe:fade-in dark:focus-visible:ring-emerald-400"
                                >
                                    <ArrowDown
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                </button>
                            )}
                            {visibleAllowance && !usageLimitTrigger && (
                                <div className="pb-2 empty:hidden">
                                    <AiAllowanceNotice
                                        allowance={visibleAllowance}
                                        onDismiss={() =>
                                            setDismissedWarningAt(
                                                visibleAllowance.resets_at,
                                            )
                                        }
                                    />
                                </div>
                            )}
                            <ChatInput
                                onSubmit={handleSubmit}
                                onStop={handleStop}
                                onInputChange={handleInputChange}
                                initialMessage={
                                    shouldAutoStartInitialPrompt
                                        ? null
                                        : initialPrompt
                                }
                                isLoading={isStreaming || isSubmitting}
                            />
                        </div>
                    </div>

                    <p role="status" aria-live="polite" className="sr-only">
                        {announcement}
                    </p>
                </section>
            </AppContent>
        </AppShell>
    );
}
