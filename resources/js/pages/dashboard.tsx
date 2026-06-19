import { OnboardingBanner } from '@/components/onboarding-banner';
import useSharedProps from '@/hooks/use-shared-props';
import AppLayout from '@/layouts/app-layout';
import { generateUUID } from '@/lib/utils';
import { dashboard } from '@/routes';
import chat from '@/routes/chat';
import { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import type { FileUIPart } from 'ai';
import { useTranslation } from 'react-i18next';
import ChatInput from './chat/chat-input';

const getBreadcrumbs = (t: (key: string) => string): BreadcrumbItem[] => [
    {
        title: t('dashboard_ai.breadcrumb'),
        href: dashboard().url,
    },
];

type ChatMessagePart = {
    type: string;
    text?: string;
    mediaType?: string;
    url?: string;
    filename?: string;
};

type ChatMessage = { role: string; parts: ChatMessagePart[] };

export default function Dashboard() {
    const { t } = useTranslation('common');
    const breadcrumbs = getBreadcrumbs(t);
    const { currentUser } = useSharedProps();

    const form = useForm<{ messages: ChatMessage[] }>({ messages: [] });

    const submitError = (form.errors as Record<string, string | undefined>)
        .message;

    function startChat(text: string, files?: FileUIPart[]): void {
        const parts: ChatMessagePart[] = [
            ...(text.trim() ? [{ type: 'text', text }] : []),
            ...(files ?? []),
        ];

        if (parts.length === 0) {
            return;
        }

        const conversationId = generateUUID();

        form.transform(() => ({
            messages: [{ role: 'user', parts }],
        }));
        form.post(chat.store(conversationId).url);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('dashboard')} />
            <main className="flex min-h-[calc(100svh-8rem)] flex-1 flex-col overflow-x-hidden bg-background">
                <div className="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
                    {!currentUser?.is_onboarded && <OnboardingBanner />}

                    <section className="flex flex-1 items-center justify-center py-4">
                        <div className="flex w-full max-w-2xl flex-col items-center space-y-6">
                            <div className="group relative">
                                <div className="absolute -inset-4 rounded-full bg-linear-to-br from-emerald-100 to-teal-100 opacity-50 blur-xl dark:from-emerald-900/20 dark:to-teal-900/20" />
                                <div className="relative overflow-hidden rounded-full ring-4 ring-emerald-100/50 dark:ring-emerald-800/20">
                                    <img
                                        src="https://pub-plate-assets.acara.app/images/altani-waving-hello-320.webp"
                                        alt="Altani"
                                        className="size-20 object-cover transition-transform duration-500 group-hover:scale-105"
                                    />
                                </div>
                            </div>

                            <p className="max-w-md text-center text-sm leading-relaxed text-muted-foreground sm:text-base">
                                {t('dashboard_ai.description')}
                            </p>

                            <div className="w-full space-y-3">
                                <ChatInput
                                    onSubmit={startChat}
                                    disabled={form.processing}
                                    placeholder={t('dashboard_ai.placeholder')}
                                    className="px-0 sm:px-0"
                                />
                                {submitError && (
                                    <p className="px-2 text-center text-sm text-destructive">
                                        {submitError}
                                    </p>
                                )}
                            </div>

                            <div className="relative w-full pt-2">
                                <div className="absolute inset-x-0 top-0 h-px bg-linear-to-r from-transparent via-border to-transparent" />
                                <p className="text-center text-xs leading-6 text-muted-foreground sm:text-sm">
                                    {t('dashboard_ai.disclaimer')}
                                </p>
                            </div>
                        </div>
                    </section>
                </div>
            </main>
        </AppLayout>
    );
}
