import { ApprovalCard } from '@/components/chat/approval-card';
import {
    extractApprovalPayload,
    type ApprovalPartPayload,
} from '@/components/chat/approval-part';
import { ChatErrorBoundary } from '@/components/chat/chat-error-boundary';
import { ProviderToolRow } from '@/components/chat/provider-tool-row';
import { ReasoningBlock } from '@/components/chat/reasoning-block';
import { RunningDots } from '@/components/chat/running-dots';
import {
    SourcesSection,
    type SourceLink,
} from '@/components/chat/sources-section';
import {
    approvalOwnerToolId,
    providerToolData,
    reasoningData,
    toolCallData,
} from '@/components/chat/stream-parts';
import { ToolCallSection } from '@/components/chat/tool-call-section';
import { cn } from '@/lib/utils';
import type {
    ChatStatus,
    ProviderToolData,
    ReasoningData,
    ToolCallData,
} from '@/types/chat';
import { type UIMessage } from '@ai-sdk/react';
import { code } from '@streamdown/code';
import { AlertCircle, Sparkles, User } from 'lucide-react';
import { memo, useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Streamdown } from 'streamdown';

interface ChatMessagesProps {
    messages: UIMessage[];
    status: ChatStatus;
    isSubmitting?: boolean;
    conversationId: string;
}

export function ChatErrorBanner({
    error,
    onRetry,
}: {
    error?: Error;
    onRetry?: () => void;
}) {
    if (!error) {
        return null;
    }

    return (
        <div className="my-4 flex w-full items-start gap-3 rounded-xl border border-red-200/60 bg-red-50/80 p-4 backdrop-blur-sm dark:border-red-900/40 dark:bg-red-950/60">
            <AlertCircle className="mt-0.5 size-5 shrink-0 text-red-500 dark:text-red-400" />
            <div className="flex-1 space-y-2">
                <p className="text-sm font-medium text-red-800 dark:text-red-200">
                    Something went wrong
                </p>
                <p className="text-sm text-red-700 dark:text-red-300">
                    {error.message || 'An unexpected error occurred.'}
                </p>
                {onRetry && (
                    <button
                        type="button"
                        onClick={onRetry}
                        className="text-sm font-medium text-red-600 underline hover:text-red-800 dark:text-red-300 dark:hover:text-red-100"
                    >
                        Try again
                    </button>
                )}
            </div>
        </div>
    );
}

function EmptyState() {
    const { t } = useTranslation('common');

    return (
        <div className="flex flex-1 flex-col items-center justify-center text-center">
            <div className="group relative mb-6">
                <div className="absolute -inset-4 rounded-full bg-linear-to-br from-emerald-100 to-teal-100 opacity-50 blur-xl dark:from-emerald-900/20 dark:to-teal-900/20" />
                <div className="relative overflow-hidden rounded-full ring-4 ring-emerald-100/50 dark:ring-emerald-800/20">
                    <img
                        src="https://pub-plate-assets.acara.app/images/altani_with_hand_on_chin_considering_expression_thought-1024.webp"
                        alt="Altani"
                        className="size-20 object-cover transition-transform duration-500 group-hover:scale-105"
                    />
                </div>
            </div>
            <p className="max-w-md text-sm leading-relaxed text-muted-foreground sm:text-base">
                {t('dashboard_ai.description')}
            </p>
        </div>
    );
}

function MessageAvatar({ role }: { role: string }) {
    const isUser = role === 'user';

    return (
        <div
            className={cn(
                'flex shrink-0 items-center justify-center overflow-hidden rounded-full',
                isUser ? 'size-8' : 'size-10',
                isUser
                    ? 'bg-primary text-primary-foreground shadow-md shadow-primary/20'
                    : 'bg-linear-to-br from-emerald-400 to-teal-500 text-white shadow-lg shadow-emerald-500/20',
            )}
        >
            {isUser ? (
                <User className="size-4" />
            ) : (
                <img
                    src="https://pub-plate-assets.acara.app/images/altani-waving-hello-320.webp"
                    alt="Altani"
                    className="h-full w-full object-cover"
                />
            )}
        </div>
    );
}

function MessagePart({
    part,
    isStreaming,
}: {
    part: UIMessage['parts'][number];
    isStreaming?: boolean;
}) {
    switch (part.type) {
        case 'text':
            return (
                <div className="prose prose-sm max-w-none dark:prose-invert">
                    <Streamdown
                        animated
                        isAnimating={isStreaming}
                        plugins={{ code }}
                    >
                        {part.text}
                    </Streamdown>
                </div>
            );
        case 'file':
            if (part.mediaType?.startsWith('image/')) {
                return (
                    <img
                        src={part.url}
                        alt={part.filename ?? 'Uploaded image'}
                        className="max-h-64 max-w-full rounded-xl object-contain shadow-sm"
                    />
                );
            }
            return (
                <div className="flex items-center gap-2 text-muted-foreground">
                    <span className="text-base">📎</span>
                    <span className="text-sm">{part.filename}</span>
                </div>
            );
        default:
            return null;
    }
}

function UserBubble({ message }: { message: UIMessage }) {
    const textContent = message.parts
        ?.filter((part) => part.type === 'text')
        .map((part) => (part.type === 'text' ? part.text : ''))
        .join('');

    const imageParts = message.parts?.filter(
        (part) => part.type === 'file' && part.mediaType?.startsWith('image/'),
    );

    return (
        <div className="flex justify-end gap-3 duration-300 animate-in fade-in slide-in-from-bottom-2">
            <div className="max-w-[85%] rounded-3xl rounded-br-md bg-linear-to-br from-emerald-500 to-emerald-600 px-4 py-3 text-primary-foreground shadow-lg shadow-emerald-500/20">
                {imageParts && imageParts.length > 0 && (
                    <div className="mb-2 flex flex-wrap gap-2">
                        {imageParts.map((part, index) => (
                            <img
                                key={index}
                                src={part.type === 'file' ? part.url : ''}
                                alt="Uploaded image"
                                className="max-h-48 max-w-full rounded-xl object-contain shadow-sm"
                            />
                        ))}
                    </div>
                )}
                {textContent && (
                    <p className="text-[15px] leading-relaxed">{textContent}</p>
                )}
            </div>
            <MessageAvatar role="user" />
        </div>
    );
}

interface AssistantParts {
    reasoning: ReasoningData[];
    toolCalls: ToolCallData[];
    providerTools: ProviderToolData[];
    approvals: ApprovalPartPayload[];
    sources: SourceLink[];
    body: UIMessage['parts'];
    hasContent: boolean;
}

function partitionAssistantParts(message: UIMessage): AssistantParts {
    const parts = message.parts ?? [];

    const ownerToolIds = new Set(
        parts
            .map(approvalOwnerToolId)
            .filter((id): id is string => id !== null),
    );

    const reasoning = parts
        .map(reasoningData)
        .filter((data): data is ReasoningData => data !== null);

    const toolCalls = parts
        .map(toolCallData)
        .filter((data): data is ToolCallData => data !== null)
        .filter((data) => !ownerToolIds.has(data.toolId));

    const providerTools = parts
        .map(providerToolData)
        .filter((data): data is ProviderToolData => data !== null);

    const approvals = parts
        .map((part) => extractApprovalPayload(part))
        .filter(
            (approval): approval is ApprovalPartPayload => approval !== null,
        );

    const sources: SourceLink[] = parts
        .filter((part) => part.type === 'source-url')
        .map((part) => {
            const source = part as { url: string; title?: string | null };

            return { url: source.url, title: source.title ?? source.url };
        });

    const body = parts.filter(
        (part) => part.type === 'text' || part.type === 'file',
    );

    const hasBody = body.some(
        (part) =>
            part.type === 'file' ||
            (part.type === 'text' && part.text.trim().length > 0),
    );

    const hasContent =
        reasoning.length > 0 ||
        toolCalls.length > 0 ||
        providerTools.length > 0 ||
        approvals.length > 0 ||
        sources.length > 0 ||
        hasBody;

    return {
        reasoning,
        toolCalls,
        providerTools,
        approvals,
        sources,
        body,
        hasContent,
    };
}

function hasRenderableContent(message: UIMessage): boolean {
    for (const part of message.parts ?? []) {
        if (part.type === 'text') {
            if (part.text.trim().length > 0) {
                return true;
            }

            continue;
        }

        if (
            part.type === 'file' ||
            part.type === 'data-reasoning' ||
            part.type === 'data-tool-call' ||
            part.type === 'data-provider-tool' ||
            part.type === 'data-approval' ||
            part.type === 'source-url'
        ) {
            return true;
        }
    }

    return false;
}

function AssistantBubble({
    message,
    isStreaming,
    conversationId,
}: {
    message: UIMessage;
    isStreaming?: boolean;
    conversationId: string;
}) {
    const {
        reasoning,
        toolCalls,
        providerTools,
        approvals,
        sources,
        body,
        hasContent,
    } = partitionAssistantParts(message);

    if (!hasContent) {
        return null;
    }

    return (
        <div className="flex gap-3 duration-300 animate-in fade-in slide-in-from-bottom-2">
            <MessageAvatar role="assistant" />
            <div className="max-w-[85%] rounded-3xl rounded-bl-md border border-border/60 bg-muted/80 px-4 py-3 text-foreground shadow-sm backdrop-blur-sm">
                <div className="space-y-3 text-sm">
                    {reasoning.map((data) => (
                        <ReasoningBlock
                            key={data.reasoningId}
                            data={data}
                            isStreaming={isStreaming}
                        />
                    ))}
                    <ToolCallSection
                        tools={toolCalls}
                        isStreaming={isStreaming}
                    />
                    {providerTools.map((data) => (
                        <ProviderToolRow key={data.itemId} tool={data} />
                    ))}
                    {body.map((part, index) => (
                        <MessagePart
                            key={index}
                            part={part}
                            isStreaming={isStreaming}
                        />
                    ))}
                    <SourcesSection sources={sources} />
                    {approvals.map((approval) => (
                        <ApprovalCard
                            key={approval.approvalId}
                            conversationId={conversationId}
                            approvalId={approval.approvalId}
                            card={approval.card}
                        />
                    ))}
                </div>
            </div>
        </div>
    );
}

const MessageBubble = memo(function MessageBubble({
    message,
    isStreaming,
    conversationId,
}: {
    message: UIMessage;
    isStreaming?: boolean;
    conversationId: string;
}) {
    return message.role === 'user' ? (
        <UserBubble message={message} />
    ) : (
        <AssistantBubble
            message={message}
            isStreaming={isStreaming}
            conversationId={conversationId}
        />
    );
});

const THINKING_LABEL_DELAY_MS = 4000;

function WorkingIndicator() {
    const [showLabel, setShowLabel] = useState(false);

    useEffect(() => {
        const timer = setTimeout(
            () => setShowLabel(true),
            THINKING_LABEL_DELAY_MS,
        );

        return () => clearTimeout(timer);
    }, []);

    return (
        <div className="flex gap-3 duration-300 animate-in fade-in slide-in-from-bottom-2">
            <div className="flex size-10 shrink-0 items-center justify-center rounded-full bg-linear-to-br from-emerald-400 to-teal-500 text-white shadow-lg shadow-emerald-500/20">
                <Sparkles className="size-5" />
            </div>
            <div className="flex items-center gap-2.5 rounded-2xl rounded-bl-md border border-border/60 bg-muted/80 px-4 py-3 shadow-sm backdrop-blur-sm">
                {showLabel && (
                    <span className="text-sm text-muted-foreground duration-300 animate-in fade-in">
                        Thinking
                    </span>
                )}
                <RunningDots />
            </div>
        </div>
    );
}

export default function ChatMessages({
    messages,
    status,
    isSubmitting,
    conversationId,
}: ChatMessagesProps) {
    if (messages.length === 0) {
        return <EmptyState />;
    }

    const lastIndex = messages.length - 1;
    const lastMessage = messages[lastIndex];
    const assistantIsRendering =
        lastMessage?.role === 'assistant' && hasRenderableContent(lastMessage);
    const showWorking =
        !assistantIsRendering && (isSubmitting || status === 'streaming');

    return (
        <div className="flex w-full flex-1 flex-col gap-5">
            {messages.map((message, index) => (
                <ChatErrorBoundary key={message.id}>
                    <MessageBubble
                        message={message}
                        isStreaming={
                            status === 'streaming' &&
                            index === lastIndex &&
                            message.role === 'assistant'
                        }
                        conversationId={conversationId}
                    />
                </ChatErrorBoundary>
            ))}
            {showWorking && <WorkingIndicator />}
        </div>
    );
}
