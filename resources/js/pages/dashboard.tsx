import { OnboardingBanner } from '@/components/onboarding-banner';
import useSharedProps from '@/hooks/use-shared-props';
import AppLayout from '@/layouts/app-layout';
import { generateUUID } from '@/lib/utils';
import { dashboard } from '@/routes';
import chat from '@/routes/chat';
import { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import type { FileUIPart } from 'ai';
import { Activity, Apple, Droplets, HeartPulse } from 'lucide-react';
import { useRef } from 'react';
import { useTranslation } from 'react-i18next';
import ChatInput, { type ChatInputHandle } from './chat/chat-input';

const prompts = [
    { key: 'dashboard_ai.prompts.glucose', icon: Droplets },
    { key: 'dashboard_ai.prompts.meal', icon: Apple },
    { key: 'dashboard_ai.prompts.energy', icon: HeartPulse },
    { key: 'dashboard_ai.prompts.sync', icon: Activity },
] as const;

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
    const chatInputRef = useRef<ChatInputHandle>(null);

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

    function selectPrompt(promptText: string): void {
        chatInputRef.current?.setMessage(promptText);
        chatInputRef.current?.focus();
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

                            <div className="space-y-3">
                                <ChatInput
                                    ref={chatInputRef}
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

                            <div className="grid gap-3 sm:grid-cols-2">
                                {prompts.map(({ key, icon: PromptIcon }) => {
                                    const promptText = t(key);

                                    return (
                                        <button
                                            key={key}
                                            type="button"
                                            onClick={() =>
                                                selectPrompt(promptText)
                                            }
                                            className="group relative flex min-h-[88px] items-start gap-3 rounded-xl border border-border/60 bg-card p-4 text-left text-sm leading-6 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/30 hover:bg-primary/5 hover:shadow-md active:translate-y-0 active:shadow-sm"
                                        >
                                            <span className="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg bg-linear-to-br from-emerald-400 to-teal-500 text-white shadow-sm transition-transform duration-200 group-hover:scale-110">
                                                <PromptIcon className="size-4" />
                                            </span>
                                            <span className="flex-1 text-foreground">
                                                {promptText}
                                            </span>
                                        </button>
                                    );
                                })}
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
