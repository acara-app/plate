import { OnboardingBanner } from '@/components/onboarding-banner';
import useSharedProps from '@/hooks/use-shared-props';
import AppLayout from '@/layouts/app-layout';
import { generateUUID } from '@/lib/utils';
import { dashboard } from '@/routes';
import chat from '@/routes/chat';
import { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import type { FileUIPart } from 'ai';
import { Activity, Apple, Droplets, HeartPulse, Sparkles } from 'lucide-react';
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
                <div className="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-8 px-4 py-6 sm:px-6 lg:px-8">
                    {!currentUser?.is_onboarded && <OnboardingBanner />}

                    <section className="flex flex-1 items-center justify-center py-6">
                        <div className="w-full max-w-3xl space-y-7">
                            <div className="text-center">
                                <div className="mx-auto mb-5 flex size-16 items-center justify-center overflow-hidden rounded-full border border-primary/20 bg-primary/10 shadow-sm">
                                    <img
                                        src="https://pub-plate-assets.acara.app/images/altani-waving-hello-320.webp"
                                        alt="Altani"
                                        className="h-full w-full object-cover"
                                    />
                                </div>
                                <p className="mb-3 inline-flex items-center gap-2 rounded-full border border-primary/15 bg-primary/5 px-3 py-1 text-sm font-medium text-primary">
                                    <Sparkles className="size-4" />
                                    {t('dashboard_ai.eyebrow')}
                                </p>
                                <h1 className="text-3xl font-semibold tracking-normal text-foreground sm:text-4xl">
                                    {t('dashboard_ai.heading')}
                                </h1>
                                <p className="mx-auto mt-3 max-w-2xl text-base leading-7 text-muted-foreground">
                                    {t('dashboard_ai.description')}
                                </p>
                            </div>

                            <div className="space-y-2">
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
                                            className="group flex min-h-20 items-start gap-3 rounded-lg border border-border bg-background px-4 py-3 text-left text-sm leading-6 transition-colors hover:border-primary/40 hover:bg-primary/5 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                        >
                                            <span className="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-md bg-muted text-muted-foreground transition-colors group-hover:bg-primary/10 group-hover:text-primary">
                                                <PromptIcon className="size-4" />
                                            </span>
                                            <span className="text-foreground">
                                                {promptText}
                                            </span>
                                        </button>
                                    );
                                })}
                            </div>

                            <p className="text-center text-xs leading-6 text-muted-foreground sm:text-sm">
                                {t('dashboard_ai.disclaimer')}
                            </p>
                        </div>
                    </section>
                </div>
            </main>
        </AppLayout>
    );
}
