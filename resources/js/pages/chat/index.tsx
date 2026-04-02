import AdminPageWrap from '@/components/sections/admin-page-wrap';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { generateUUID } from '@/lib/utils';
import { dashboard } from '@/routes';
import chat from '@/routes/chat';
import { BreadcrumbItem } from '@/types';
import { Head, InfiniteScroll, Link } from '@inertiajs/react';
import { MessageSquare, Plus } from 'lucide-react';
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
                            <p className="text-muted-foreground">
                                {t('conversations.description')}
                            </p>
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
                                                    <Link
                                                        href={
                                                            chat.create(
                                                                conversation.id,
                                                            ).url
                                                        }
                                                        className="group flex items-center justify-between px-5 py-4 transition-colors hover:bg-muted/50"
                                                    >
                                                        <div className="flex items-center gap-3">
                                                            <MessageSquare className="size-4 shrink-0 text-muted-foreground" />
                                                            <span className="font-medium group-hover:text-primary">
                                                                {conversation.title ||
                                                                    t(
                                                                        'conversations.untitled',
                                                                    )}
                                                            </span>
                                                        </div>
                                                        <span className="shrink-0 text-xs text-muted-foreground">
                                                            {
                                                                conversation.updated_at
                                                            }
                                                        </span>
                                                    </Link>
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
