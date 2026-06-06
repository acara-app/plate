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
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { generateUUID } from '@/lib/utils';
import { dashboard } from '@/routes';
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
        title: t('home'),
        href: dashboard().url,
    },
    {
        title: t('conversations.title'),
        href: chat.index().url,
    },
];

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
                        </div>
                        <Button asChild>
                            <Link href={chat.create(generateUUID()).url}>
                                <Plus className="mr-2 size-4" />
                                {t('conversations.new_chat')}
                            </Link>
                        </Button>
                    </div>

                    <Card>
                        <CardContent className="p-0">
                            {conversations.data.length === 0 ? (
                                <div className="flex flex-col items-center gap-3 py-12 text-center text-muted-foreground">
                                    <MessageSquare className="size-10 opacity-40" />
                                    <p>{t('conversations.empty')}</p>
                                    <Button variant="outline" asChild>
                                        <Link
                                            href={
                                                chat.create(generateUUID()).url
                                            }
                                        >
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
                                    <ul className="divide-y">
                                        {conversations.data.map(
                                            (conversation) => (
                                                <li key={conversation.id}>
                                                    <div className="group flex items-center gap-3 px-5 py-4 transition-colors hover:bg-muted/50">
                                                        <Link
                                                            href={
                                                                chat.create(
                                                                    conversation.id,
                                                                ).url
                                                            }
                                                            className="flex min-w-0 flex-1 items-center justify-between gap-4"
                                                        >
                                                            <div className="flex min-w-0 items-center gap-3">
                                                                <MessageSquare className="size-4 shrink-0 text-muted-foreground" />
                                                                <span className="truncate font-medium group-hover:text-primary">
                                                                    {conversation.title ||
                                                                        t(
                                                                            'conversations.untitled',
                                                                        )}
                                                                </span>
                                                            </div>
                                                            <span className="shrink-0 text-xs text-muted-foreground">
                                                                {new Date(
                                                                    conversation.updated_at,
                                                                ).toLocaleString()}
                                                            </span>
                                                        </Link>
                                                        <AlertDialog>
                                                            <AlertDialogTrigger
                                                                asChild
                                                            >
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    className="size-8 text-muted-foreground opacity-70 group-hover:opacity-100 hover:bg-destructive/10 hover:text-destructive"
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
                                                                        className="bg-destructive text-white hover:bg-destructive/90 focus-visible:ring-destructive/20 dark:focus-visible:ring-destructive/40"
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
                                </InfiniteScroll>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </AdminPageWrap>
        </AppLayout>
    );
}
