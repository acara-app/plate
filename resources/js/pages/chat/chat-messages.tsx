import { buildActivity } from '@/components/chat/activity';
import { ActivityLog } from '@/components/chat/activity-log';
import { AnswerMarkdown } from '@/components/chat/answer-markdown';
import { ApprovalCard } from '@/components/chat/approval-card';
import { extractApprovalPayload } from '@/components/chat/approval-part';
import { ChatErrorBoundary } from '@/components/chat/chat-error-boundary';
import {
    SourcesSection,
    type SourceLink,
} from '@/components/chat/sources-section';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import useSharedProps from '@/hooks/use-shared-props';
import { cn } from '@/lib/utils';
import type {
    ApprovalCardData,
    ApprovalDecisionsPayload,
    ChatStatus,
} from '@/types/chat';
import { type UIMessage } from '@ai-sdk/react';
import {
    CircleAlert,
    CircleDashed,
    CircleStop,
    Clock,
    Paperclip,
    RefreshCw,
    RotateCcw,
} from 'lucide-react';
import { memo, type ReactNode, useId, useMemo } from 'react';
import { useTranslation } from 'react-i18next';

type Part = UIMessage['parts'][number];
type FilePart = Extract<Part, { type: 'file' }>;
type Speaker = 'user' | 'assistant';
type DecideApproval = (
    decisions: ApprovalDecisionsPayload,
) => Promise<'resumed' | 'recorded'>;

export const ACARA_AVATAR_URL =
    'https://pub-plate-assets.acara.app/images/altani-waving-hello-320.webp';

const HANGING_LABEL =
    '@min-[56rem]:absolute @min-[56rem]:top-0 @min-[56rem]:right-full @min-[56rem]:mr-6 @min-[56rem]:mb-0 @min-[56rem]:w-32 @min-[56rem]:justify-end';

const SPIN = 'motion-safe:animate-spin motion-safe:[animation-duration:2.4s]';

interface ChatMessagesProps {
    messages: UIMessage[];
    status: ChatStatus;
    retentionHours: number;
    stoppedMessageIds: ReadonlySet<string>;
    isSubmitting?: boolean;
    onApprovalDecision?: DecideApproval;
}

function SpeakerAvatar({ speaker }: { speaker: Speaker }) {
    const { currentUser } = useSharedProps();
    const getInitials = useInitials();

    if (speaker === 'assistant') {
        return (
            <img
                src={ACARA_AVATAR_URL}
                alt=""
                className="size-5 shrink-0 rounded-full bg-muted object-cover"
            />
        );
    }

    return (
        <Avatar aria-hidden="true" className="size-5 shrink-0">
            {currentUser?.avatar && (
                <AvatarImage src={currentUser.avatar} alt="" />
            )}
            <AvatarFallback className="bg-muted text-[9px] font-semibold text-foreground/75">
                {getInitials(currentUser?.name ?? '')}
            </AvatarFallback>
        </Avatar>
    );
}

function Turn({
    speaker,
    animate = false,
    className,
    children,
}: {
    speaker: Speaker;
    animate?: boolean;
    className?: string;
    children: ReactNode;
}) {
    const { t } = useTranslation('common');
    const labelId = useId();

    return (
        <article
            aria-labelledby={labelId}
            className={cn(
                'relative',
                animate &&
                    'duration-300 motion-safe:animate-in motion-safe:fade-in',
                className,
            )}
        >
            <h2
                id={labelId}
                className={cn(
                    'mb-1 flex h-7 items-center gap-2 text-[13px]',
                    HANGING_LABEL,
                    speaker === 'assistant'
                        ? 'font-semibold text-foreground'
                        : 'font-medium text-muted-foreground',
                )}
            >
                <SpeakerAvatar speaker={speaker} />
                {speaker === 'assistant' ? t('chat.assistant') : t('chat.you')}
            </h2>
            {children}
        </article>
    );
}

function Attachments({ files }: { files: FilePart[] }) {
    const { t } = useTranslation('common');

    return (
        <ul className="flex flex-wrap gap-2">
            {files.map((file, index) => (
                <li key={`${file.url.slice(-24)}-${index}`}>
                    {file.mediaType?.startsWith('image/') ? (
                        <img
                            src={file.url}
                            alt={file.filename ?? t('chat.attachment.photo')}
                            className="h-36 max-w-full rounded-lg border border-border object-cover sm:h-44"
                        />
                    ) : (
                        <span className="inline-flex items-center gap-1.5 rounded-md border border-border px-2 py-1 text-[13px] text-muted-foreground">
                            <Paperclip
                                className="size-3.5 shrink-0"
                                aria-hidden="true"
                            />
                            {file.filename ?? t('chat.attachment.file')}
                        </span>
                    )}
                </li>
            ))}
        </ul>
    );
}

function filesOf(parts: Part[]): FilePart[] {
    return parts.filter((part): part is FilePart => part.type === 'file');
}

function UserMessage({
    message,
    className,
}: {
    message: UIMessage;
    className?: string;
}) {
    const parts = message.parts ?? [];
    const text = parts
        .map((part) => (part.type === 'text' ? part.text : ''))
        .join('')
        .trim();
    const files = filesOf(parts);

    return (
        <Turn speaker="user" animate className={className}>
            <div className="flex flex-col gap-3">
                {text && (
                    <p className="text-base leading-7 font-medium [overflow-wrap:anywhere] whitespace-pre-wrap text-foreground">
                        {text}
                    </p>
                )}
                {files.length > 0 && <Attachments files={files} />}
            </div>
        </Turn>
    );
}

type ContentBlock =
    | { kind: 'text'; key: string; text: string }
    | { kind: 'file'; key: string; file: FilePart }
    | { kind: 'approval'; key: string; approval: ApprovalCardData };

function partitionAssistantParts(message: UIMessage, live: boolean) {
    const parts = message.parts ?? [];
    const approvals: ApprovalCardData[] = [];
    const sources: SourceLink[] = [];
    const blocks: ContentBlock[] = [];

    parts.forEach((part, index) => {
        const approval = extractApprovalPayload(part);

        if (approval) {
            approvals.push(approval);
            blocks.push({
                kind: 'approval',
                key: `approval-${approval.toolCallId}`,
                approval,
            });
        } else if (part.type === 'text' && part.text.trim() !== '') {
            blocks.push({
                kind: 'text',
                key: `text-${index}`,
                text: part.text,
            });
        } else if (part.type === 'file') {
            blocks.push({ kind: 'file', key: `file-${index}`, file: part });
        } else if (part.type === 'source-url') {
            sources.push({ url: part.url, title: part.title ?? part.url });
        }
    });

    const activity = buildActivity(parts, approvals, live);

    return {
        activity,
        blocks,
        sources,
        hasContent:
            activity.length > 0 || blocks.length > 0 || sources.length > 0,
    };
}

function hasRenderableContent(message: UIMessage): boolean {
    return (message.parts ?? []).some((part) => {
        if (part.type === 'text') {
            return part.text.trim().length > 0;
        }

        return (
            part.type === 'file' ||
            part.type === 'data-reasoning' ||
            part.type === 'data-tool-call' ||
            part.type === 'data-provider-tool' ||
            part.type === 'data-approval' ||
            part.type === 'source-url'
        );
    });
}

function AssistantMessage({
    message,
    isStreaming,
    onDecide,
    className,
}: {
    message: UIMessage;
    isStreaming: boolean;
    onDecide?: DecideApproval;
    className?: string;
}) {
    const { activity, blocks, sources, hasContent } = useMemo(
        () => partitionAssistantParts(message, isStreaming),
        [message, isStreaming],
    );

    if (!hasContent) {
        return null;
    }

    const streamingTextKey = isStreaming
        ? blocks.findLast((block) => block.kind === 'text')?.key
        : undefined;

    return (
        <Turn speaker="assistant" className={className}>
            <div className="flex flex-col gap-3">
                {activity.length > 0 && <ActivityLog items={activity} />}
                {blocks.map((block) => {
                    if (block.kind === 'text') {
                        return (
                            <AnswerMarkdown
                                key={block.key}
                                isStreaming={block.key === streamingTextKey}
                            >
                                {block.text}
                            </AnswerMarkdown>
                        );
                    }

                    if (block.kind === 'file') {
                        return (
                            <Attachments key={block.key} files={[block.file]} />
                        );
                    }

                    return (
                        <div key={block.key} className="py-1">
                            <ApprovalCard
                                approval={block.approval}
                                onDecide={onDecide}
                            />
                        </div>
                    );
                })}
                {sources.length > 0 && (
                    <div className="pt-1">
                        <SourcesSection sources={sources} />
                    </div>
                )}
            </div>
        </Turn>
    );
}

const Message = memo(function Message({
    message,
    isStreaming,
    onDecide,
    className,
}: {
    message: UIMessage;
    isStreaming: boolean;
    onDecide?: DecideApproval;
    className?: string;
}) {
    return message.role === 'user' ? (
        <UserMessage message={message} className={className} />
    ) : (
        <AssistantMessage
            message={message}
            isStreaming={isStreaming}
            onDecide={onDecide}
            className={className}
        />
    );
});

function WorkingIndicator({ className }: { className?: string }) {
    const { t } = useTranslation('common');

    return (
        <Turn speaker="assistant" animate className={className}>
            <p className="flex min-h-9 items-center gap-2 text-sm text-muted-foreground @min-[56rem]:min-h-7">
                <CircleDashed
                    className={cn('size-4 shrink-0 text-foreground/70', SPIN)}
                    strokeWidth={1.75}
                    aria-hidden="true"
                />
                {t('chat.activity.starting')}
            </p>
        </Turn>
    );
}

function StoppedNote() {
    const { t } = useTranslation('common');

    return (
        <p className="mt-4 flex items-center gap-2 text-[13px] text-muted-foreground">
            <CircleStop
                className="size-4 shrink-0"
                strokeWidth={1.75}
                aria-hidden="true"
            />
            {t('chat.stopped')}
        </p>
    );
}

function EmptyState({ retentionHours }: { retentionHours: number }) {
    const { t } = useTranslation('common');
    const { currentUser } = useSharedProps();
    const firstName = currentUser?.name?.trim().split(/\s+/)[0];

    return (
        <div className="flex flex-1 flex-col justify-center py-10 duration-500 motion-safe:animate-in motion-safe:fade-in">
            <img
                src={ACARA_AVATAR_URL}
                alt=""
                className="size-12 rounded-full bg-muted object-cover"
            />
            <h2 className="mt-6 max-w-[30ch] text-2xl leading-tight font-semibold tracking-tight text-balance text-foreground sm:text-[1.75rem]">
                {firstName
                    ? t('chat.empty.greeting', { name: firstName })
                    : t('chat.empty.greeting_anonymous')}
            </h2>
            <p className="mt-3 max-w-[34rem] text-base leading-7 text-muted-foreground">
                {t('chat.empty.body')}
            </p>
            <p className="mt-8 flex max-w-[34rem] items-start gap-2 text-[13px] leading-5 text-muted-foreground">
                <Clock
                    className="mt-0.5 size-3.5 shrink-0"
                    aria-hidden="true"
                />
                {t('chat.empty.retention', { hours: retentionHours })}
            </p>
        </div>
    );
}

export function ChatErrorNotice({
    error,
    status,
    onRetry,
}: {
    error?: Error;
    status: ChatStatus;
    onRetry?: () => void;
}) {
    const { t } = useTranslation('common');

    if (!error) {
        return null;
    }

    if (status === 'streaming') {
        return (
            <p
                role="status"
                className="mt-5 flex items-center gap-2 text-sm text-muted-foreground"
            >
                <RefreshCw
                    className={cn('size-4 shrink-0', SPIN)}
                    strokeWidth={1.75}
                    aria-hidden="true"
                />
                {error.message}
            </p>
        );
    }

    return (
        <div role="alert" className="mt-6 flex items-start gap-2.5">
            <CircleAlert
                className="mt-0.5 size-4 shrink-0 text-red-600 dark:text-red-400"
                strokeWidth={1.75}
                aria-hidden="true"
            />
            <div className="min-w-0 text-sm leading-6">
                <p className="font-medium text-foreground">
                    {t('chat.error.title')}
                </p>
                {error.message && (
                    <p className="text-muted-foreground">{error.message}</p>
                )}
                {onRetry && (
                    <button
                        type="button"
                        onClick={onRetry}
                        className="mt-3 inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-lg border border-border bg-background px-3 text-sm font-medium text-foreground transition-colors hover:bg-muted focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none dark:focus-visible:ring-emerald-400"
                    >
                        <RotateCcw
                            className="size-3.5 shrink-0"
                            aria-hidden="true"
                        />
                        {t('chat.error.retry')}
                    </button>
                )}
            </div>
        </div>
    );
}

function turnSpacing(messages: UIMessage[], index: number): string | undefined {
    if (index === 0) {
        return undefined;
    }

    return messages[index].role === 'user' ? 'mt-12' : 'mt-6';
}

export default function ChatMessages({
    messages,
    status,
    retentionHours,
    stoppedMessageIds,
    isSubmitting,
    onApprovalDecision,
}: ChatMessagesProps) {
    const { t } = useTranslation('common');

    if (messages.length === 0) {
        return <EmptyState retentionHours={retentionHours} />;
    }

    const lastIndex = messages.length - 1;
    const lastMessage = messages[lastIndex];
    const assistantIsRendering =
        lastMessage?.role === 'assistant' && hasRenderableContent(lastMessage);
    const showWorking =
        !assistantIsRendering && (isSubmitting || status === 'streaming');

    return (
        <section aria-label={t('chat.conversation')} className="flex flex-col">
            {messages.map((message, index) => (
                <ChatErrorBoundary key={message.id}>
                    <Message
                        message={message}
                        isStreaming={
                            status === 'streaming' &&
                            index === lastIndex &&
                            message.role === 'assistant'
                        }
                        onDecide={onApprovalDecision}
                        className={turnSpacing(messages, index)}
                    />
                    {stoppedMessageIds.has(message.id) && <StoppedNote />}
                </ChatErrorBoundary>
            ))}
            {showWorking && <WorkingIndicator className="mt-6" />}
        </section>
    );
}
