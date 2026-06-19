import { OnboardingBanner } from '@/components/onboarding-banner';
import useSharedProps from '@/hooks/use-shared-props';
import AppLayout from '@/layouts/app-layout';
import { generateUUID } from '@/lib/utils';
import { dashboard } from '@/routes';
import chat from '@/routes/chat';
import { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import type { FileUIPart } from 'ai';
import { ShieldCheck } from 'lucide-react';
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
                        <div className="w-full max-w-3xl space-y-6">
                            <div className="flex items-center gap-3">
                                <div className="flex size-10 shrink-0 items-center justify-center rounded-full bg-linear-to-br from-emerald-400 to-teal-500 text-white shadow-md shadow-emerald-500/20">
                                    <img
                                        src="https://pub-plate-assets.acara.app/images/altani-waving-hello-320.webp"
                                        alt="Altani"
                                        className="size-10 rounded-full object-cover"
                                    />
                                </div>
                                <div>
                                    <h1 className="text-xl font-semibold tracking-tight text-foreground">
                                        {t('dashboard_ai.heading')}
                                    </h1>
                                    <p className="text-sm text-muted-foreground">
                                        {t('dashboard_ai.description')}
                                    </p>
                                </div>
                            </div>

                            <div className="mx-auto flex max-w-xl items-start gap-2.5 rounded-xl border border-border/60 bg-muted/30 px-4 py-3">
                                <ShieldCheck className="mt-0.5 size-4 shrink-0 text-emerald-500" />
                                <p className="text-xs leading-6 text-muted-foreground sm:text-sm">
                                    {t('dashboard_ai.privacy_notice')}
                                </p>
                            </div>

                            <div className="space-y-3">
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

                            <div className="relative pt-2">
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
