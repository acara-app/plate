import AdminPageWrap from '@/components/sections/admin-page-wrap';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import AppLayout from '@/layouts/app-layout';
import { cn, generateUUID } from '@/lib/utils';
import chat from '@/routes/chat';
import { BreadcrumbItem } from '@/types';
import { Head, InfiniteScroll, Link, router } from '@inertiajs/react';
import { MessageSquare, Pin, Plus, Trash2 } from 'lucide-react';
import { useTranslation } from 'react-i18next';

interface Conversation {
    id: string;
    title: string;
    pinned_at: string | null;
    created_at: string;
    updated_at: string;
}

interface Props {
    conversations: {
        data: Conversation[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    temporaryRetentionHours: number;
}

const getBreadcrumbs = (t: (key: string) => string): BreadcrumbItem[] => [
    {
        title: t('conversations.title'),
        href: chat.index().url,
    },
];

function formatRelativeTime(
    dateString: string,
    t: (key: string, options?: Record<string, unknown>) => string,
): string {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();

    if (Number.isNaN(diffMs)) {
        return '';
    }

    const diffSecs = Math.floor(diffMs / 1000);
    const diffMins = Math.floor(diffSecs / 60);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffSecs < 60) {
        return t('conversations.just_now');
    }
    if (diffMins < 60) {
        return t('conversations.minutes_ago', { count: diffMins });
    }
    if (diffHours < 24) {
        return t('conversations.hours_ago', { count: diffHours });
    }
    if (diffDays < 7) {
        return t('conversations.days_ago', { count: diffDays });
    }
    return date.toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
    });
}

export default function ConversationsIndex({
    conversations,
    temporaryRetentionHours,
}: Props) {
    const { t } = useTranslation('common');

    const deleteConversation = (conversationId: string): void => {
        router.delete(chat.destroy(conversationId), {
            preserveScroll: true,
        });
    };

    const togglePin = (conversation: Conversation): void => {
        const action = conversation.pinned_at ? chat.unpin : chat.pin;

        router.patch(action(conversation.id).url, {}, { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={getBreadcrumbs(t)}>
            <Head title={t('conversations.title')} />
            <AdminPageWrap variant="lg">
                <div className="space-y-6">
                    <div className="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                        <div className="space-y-2">
                            <h1 className="text-3xl font-bold tracking-tight sm:text-4xl">
                                {t('conversations.title')}
                            </h1>
                            <div className="flex items-center gap-2">
                                <span className="inline-flex items-center gap-1.5 rounded-full border border-border/60 bg-muted/40 px-2.5 py-0.5 text-xs font-medium text-muted-foreground">
                                    <span className="size-1.5 rounded-full bg-emerald-500" />
                                    {conversations.total} conversation
                                    {conversations.total !== 1 ? 's' : ''}
                                </span>
                            </div>
                        </div>
                        <Button asChild>
                            <Link href={chat.create(generateUUID()).url}>
                                <Plus className="mr-2 size-4" />
                                {t('conversations.new_chat')}
                            </Link>
                        </Button>
                    </div>

                    {conversations.data.length === 0 ? (
                        <div className="flex flex-col items-center gap-5 rounded-3xl border border-dashed border-border bg-linear-to-b from-muted/30 to-muted/10 py-20 text-center">
                            <div className="flex size-16 items-center justify-center rounded-2xl bg-linear-to-br from-emerald-400 to-teal-500 text-white shadow-lg shadow-emerald-500/20">
                                <MessageSquare className="size-7" />
                            </div>
                            <div className="space-y-1.5">
                                <p className="text-lg font-semibold text-foreground">
                                    {t('conversations.empty')}
                                </p>
                                <p className="mx-auto max-w-xs text-sm text-muted-foreground">
                                    {t('conversations.empty_description')}
                                </p>
                            </div>
                            <Button variant="outline" asChild>
                                <Link href={chat.create(generateUUID()).url}>
                                    <Plus className="mr-2 size-4" />
                                    {t('conversations.start_first')}
                                </Link>
                            </Button>
                        </div>
                    ) : (
                        <InfiniteScroll
                            data="conversations"
                            preserveUrl
                            onlyNext
                        >
                            <div className="overflow-hidden rounded-2xl border border-border/60 bg-card shadow-sm">
                                <ul className="divide-y divide-border/60">
                                    {conversations.data.map((conversation) => {
                                        const pinned = Boolean(
                                            conversation.pinned_at,
                                        );

                                        return (
                                            <li key={conversation.id}>
                                                <div className="group relative flex items-center gap-3 px-4 py-4 transition-colors duration-200 hover:bg-muted/50 sm:px-5">
                                                    <Link
                                                        href={
                                                            chat.create(
                                                                conversation.id,
                                                            ).url
                                                        }
                                                        className="flex min-w-0 flex-1 items-center gap-3.5"
                                                    >
                                                        <div className="flex size-9 shrink-0 items-center justify-center rounded-full bg-linear-to-br from-emerald-400 to-teal-500 text-white shadow-sm">
                                                            <MessageSquare className="size-4" />
                                                        </div>
                                                        <div className="min-w-0 flex-1">
                                                            <p className="truncate font-medium text-foreground transition-colors group-hover:text-primary">
                                                                {conversation.title ||
                                                                    t(
                                                                        'conversations.untitled',
                                                                    )}
                                                            </p>
                                                            <div className="mt-1 flex items-center gap-2">
                                                                <p className="text-xs text-muted-foreground">
                                                                    {formatRelativeTime(
                                                                        conversation.updated_at,
                                                                        t,
                                                                    )}
                                                                </p>
                                                                {!pinned && (
                                                                    <Tooltip>
                                                                        <TooltipTrigger
                                                                            asChild
                                                                        >
                                                                            <span>
                                                                                <Badge
                                                                                    variant="secondary"
                                                                                    className="h-5 px-1.5 text-[10px] font-normal text-muted-foreground"
                                                                                >
                                                                                    {t(
                                                                                        'conversations.temporary_badge',
                                                                                    )}
                                                                                </Badge>
                                                                            </span>
                                                                        </TooltipTrigger>
                                                                        <TooltipContent>
                                                                            {t(
                                                                                'conversations.temporary_tooltip',
                                                                                {
                                                                                    hours: temporaryRetentionHours,
                                                                                },
                                                                            )}
                                                                        </TooltipContent>
                                                                    </Tooltip>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </Link>
                                                    <div className="flex shrink-0 items-center gap-1">
                                                        <Tooltip>
                                                            <TooltipTrigger
                                                                asChild
                                                            >
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    className={cn(
                                                                        'size-8 transition-colors',
                                                                        pinned
                                                                            ? 'text-primary hover:bg-primary/10 hover:text-primary'
                                                                            : 'text-muted-foreground/50 hover:bg-muted hover:text-foreground',
                                                                    )}
                                                                    aria-label={t(
                                                                        pinned
                                                                            ? 'conversations.unpin'
                                                                            : 'conversations.pin',
                                                                    )}
                                                                    onClick={() =>
                                                                        togglePin(
                                                                            conversation,
                                                                        )
                                                                    }
                                                                >
                                                                    <Pin
                                                                        className={cn(
                                                                            'size-4',
                                                                            pinned &&
                                                                                'fill-current',
                                                                        )}
                                                                    />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                {t(
                                                                    pinned
                                                                        ? 'conversations.unpin'
                                                                        : 'conversations.pin',
                                                                )}
                                                            </TooltipContent>
                                                        </Tooltip>
                                                        <AlertDialog>
                                                            <AlertDialogTrigger
                                                                asChild
                                                            >
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    className="size-8 text-muted-foreground opacity-0 transition-all duration-200 group-hover:opacity-100 hover:bg-destructive/10 hover:text-destructive"
                                                                    aria-label={t(
                                                                        'conversations.delete_label',
                                                                    )}
                                                                >
                                                                    <Trash2 className="size-4" />
                                                                </Button>
                                                            </AlertDialogTrigger>
                                                            <AlertDialogContent>
                                                                <AlertDialogHeader>
                                                                    <AlertDialogTitle>
                                                                        {t(
                                                                            'conversations.delete_title',
                                                                        )}
                                                                    </AlertDialogTitle>
                                                                    <AlertDialogDescription>
                                                                        {t(
                                                                            'conversations.delete_description',
                                                                        )}
                                                                    </AlertDialogDescription>
                                                                </AlertDialogHeader>
                                                                <AlertDialogFooter>
                                                                    <AlertDialogCancel>
                                                                        {t(
                                                                            'cancel',
                                                                        )}
                                                                    </AlertDialogCancel>
                                                                    <AlertDialogAction
                                                                        className="bg-destructive text-white hover:bg-destructive/90"
                                                                        onClick={() =>
                                                                            deleteConversation(
                                                                                conversation.id,
                                                                            )
                                                                        }
                                                                    >
                                                                        {t(
                                                                            'conversations.delete_confirm',
                                                                        )}
                                                                    </AlertDialogAction>
                                                                </AlertDialogFooter>
                                                            </AlertDialogContent>
                                                        </AlertDialog>
                                                    </div>
                                                </div>
                                            </li>
                                        );
                                    })}
                                </ul>
                            </div>
                        </InfiniteScroll>
                    )}
                </div>
            </AdminPageWrap>
        </AppLayout>
    );
}
