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
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { generateUUID } from '@/lib/utils';
import chat from '@/routes/chat';
import { BreadcrumbItem } from '@/types';
import { Head, InfiniteScroll, Link, router } from '@inertiajs/react';
import { MessageSquare, Plus, Trash2 } from 'lucide-react';
import { useTranslation } from 'react-i18next';

interface Conversation {
    id: string;
    title: string;
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

export default function ConversationsIndex({ conversations }: Props) {
    const { t } = useTranslation('common');

    const deleteConversation = (conversationId: string): void => {
        router.delete(chat.destroy(conversationId), {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={getBreadcrumbs(t)}>
            <Head title={t('conversations.title')} />
            <AdminPageWrap variant="lg">
                <div className="space-y-6">
                    <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">
                                {t('conversations.title')}
                            </h1>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {conversations.total} conversation
                                {conversations.total !== 1 ? 's' : ''}
                            </p>
                        </div>
                        <Button asChild>
                            <Link href={chat.create(generateUUID()).url}>
                                <Plus className="mr-2 size-4" />
                                {t('conversations.new_chat')}
                            </Link>
                        </Button>
                    </div>

                    {conversations.data.length === 0 ? (
                        <div className="flex flex-col items-center gap-4 rounded-2xl border border-dashed border-border bg-muted/30 py-16 text-center">
                            <div className="flex size-14 items-center justify-center rounded-full bg-muted">
                                <MessageSquare className="size-6 text-muted-foreground" />
                            </div>
                            <div>
                                <p className="font-medium text-foreground">
                                    No conversations yet
                                </p>
                                <p className="mt-1 text-sm text-muted-foreground">
                                    Start a new chat with your AI health coach
                                </p>
                            </div>
                            <Button variant="outline" asChild>
                                <Link href={chat.create(generateUUID()).url}>
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
                            <div className="rounded-2xl border border-border/60 bg-card shadow-sm">
                                <ul className="divide-y divide-border/60">
                                    {conversations.data.map(
                                        (conversation) => (
                                            <li key={conversation.id}>
                                                <div className="group relative flex items-center gap-3 px-4 py-4 transition-all duration-200 hover:bg-muted/50 sm:px-5">
                                                    <Link
                                                        href={
                                                            chat.create(
                                                                conversation.id,
                                                            ).url
                                                        }
                                                        className="flex min-w-0 flex-1 items-center justify-between gap-4"
                                                    >
                                                        <div className="flex min-w-0 items-center gap-3">
                                                            <div className="flex size-9 shrink-0 items-center justify-center rounded-full bg-linear-to-br from-emerald-400 to-teal-500 text-white shadow-sm">
                                                                <MessageSquare className="size-4" />
                                                            </div>
                                                            <div className="min-w-0">
                                                                <p className="truncate font-medium text-foreground transition-colors group-hover:text-primary">
                                                                    {conversation.title ||
                                                                        t(
                                                                            'conversations.untitled',
                                                                        )}
                                                                </p>
                                                                <p className="mt-0.5 text-xs text-muted-foreground">
                                                                    {formatRelativeTime(
                                                                        conversation.updated_at,
                                                                        t,
                                                                    )}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </Link>
                                                    <AlertDialog>
                                                        <AlertDialogTrigger
                                                            asChild
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                className="size-8 text-muted-foreground opacity-0 transition-all duration-200 hover:bg-destructive/10 hover:text-destructive group-hover:opacity-100 sm:size-8"
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
                                            </li>
                                        ),
                                    )}
                                </ul>
                            </div>
                        </InfiniteScroll>
                    )}
                </div>
            </AdminPageWrap>
        </AppLayout>
    );
}
