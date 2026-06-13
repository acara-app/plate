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
import { AlertCircle, User } from 'lucide-react';
import { memo } from 'react';
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
        <div className="flex w-full items-start gap-3 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900/50 dark:bg-red-950/50">
            <AlertCircle className="mt-0.5 size-5 shrink-0 text-red-600 dark:text-red-400" />
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
                        className="text-sm font-medium text-red-700 underline hover:text-red-900 dark:text-red-300 dark:hover:text-red-100"
                    >
                        Try again
                    </button>
                )}
            </div>
        </div>
    );
}

function EmptyState() {
    return (
        <div className="flex flex-1 flex-col items-center justify-center text-center">
            <div className="mb-4 overflow-hidden rounded-full ring-4 ring-emerald-100">
                <img
                    src="https://pub-plate-assets.acara.app/images/altani_with_hand_on_chin_considering_expression_thought-1024.webp"
                    alt="Altani"
                    className="size-20 object-cover"
                />
            </div>
            <h2 className="mb-2 text-xl font-semibold text-foreground">
                How are you feeling today?
            </h2>
            <p className="max-w-md text-sm text-muted-foreground">
                Your personal AI health coach is here to help with nutrition,
                meal planning, glucose predictions, or just to chat.
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
                    ? 'bg-primary text-primary-foreground'
                    : 'ring-2 ring-emerald-100',
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
                        className="max-h-64 max-w-full rounded-lg object-contain"
                    />
                );
            }
            return (
                <div className="text-muted-foreground">📎 {part.filename}</div>
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
        <div className="flex justify-end gap-3">
            <div className="max-w-[80%] rounded-2xl rounded-br-md bg-primary px-4 py-3 text-primary-foreground shadow-sm">
                {imageParts && imageParts.length > 0 && (
                    <div className="mb-2 flex flex-wrap gap-2">
                        {imageParts.map((part, index) => (
                            <img
                                key={index}
                                src={part.type === 'file' ? part.url : ''}
                                alt="Uploaded image"
                                className="max-h-48 max-w-full rounded-lg object-contain"
                            />
                        ))}
                    </div>
                )}
                {textContent && <p className="text-sm">{textContent}</p>}
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
        <div className="flex gap-3">
            <MessageAvatar role="assistant" />
            <div className="max-w-[80%] rounded-2xl rounded-bl-md bg-muted px-4 py-3 text-foreground shadow-sm">
                <div className="space-y-2 text-sm">
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

function WorkingIndicator() {
    return (
        <div className="flex gap-3 duration-300 animate-in fade-in slide-in-from-bottom-2">
            <MessageAvatar role="assistant" />
            <div className="flex items-center gap-2 rounded-2xl rounded-bl-md bg-muted px-4 py-3">
                <span className="text-sm text-muted-foreground">On it…</span>
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
        <div className="flex w-full flex-1 flex-col gap-4">
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
