import ListHealthEntryController from '@/actions/App/Http/Controllers/HealthEntry/ListHealthEntryController';
import ShowSnapToTrackController from '@/actions/App/Http/Controllers/SnapToTrack/ShowSnapToTrackController';
import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { LifecycleBanner } from '@/components/billing/lifecycle-banner';
import { BiometricsReviewBanner } from '@/components/biometrics-review-banner';
import { dayPartAt, type DayPart } from '@/components/chat/day-part';
import { DayPlate, DISPLAY_FONT } from '@/components/dashboard/day-plate';
import { HealthDataConsentCard } from '@/components/health-data-consent-card';
import { OnboardingBanner } from '@/components/onboarding-banner';
import { PrivacyUpdateNotice } from '@/components/privacy-update-notice';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useNow } from '@/hooks/use-now';
import useSharedProps from '@/hooks/use-shared-props';
import { cn, generateUUID } from '@/lib/utils';
import chat from '@/routes/chat';
import mealPlans from '@/routes/meal-plans';
import { Head, Link, useForm } from '@inertiajs/react';
import type { FileUIPart } from 'ai';
import {
    ActivityIcon,
    CalendarHeartIcon,
    Clock,
    MessageCircle,
    ScanLine,
    TriangleAlert,
    type LucideIcon,
} from 'lucide-react';
import { useCallback, useRef } from 'react';
import { useTranslation } from 'react-i18next';
import ChatInput, { type ChatInputHandle } from './chat/chat-input';

type ChatMessagePart = {
    type: string;
    text?: string;
    mediaType?: string;
    url?: string;
    filename?: string;
};

type ChatMessage = { role: string; parts: ChatMessagePart[] };

const CLOCK_TICK_MS = 20_000;

const TABLE: Record<DayPart, { cloth: string; fade: string }> = {
    morning: {
        cloth: 'bg-amber-50 dark:bg-[#1a1610]',
        fade: 'from-amber-50 dark:from-[#1a1610]',
    },
    midday: {
        cloth: 'bg-emerald-50 dark:bg-[#0f1813]',
        fade: 'from-emerald-50 dark:from-[#0f1813]',
    },
    evening: {
        cloth: 'bg-orange-50 dark:bg-[#1b1410]',
        fade: 'from-orange-50 dark:from-[#1b1410]',
    },
    night: {
        cloth: 'bg-indigo-50 dark:bg-[#111322]',
        fade: 'from-indigo-50 dark:from-[#111322]',
    },
};

const PRESSABLE =
    'touch-manipulation select-none [-webkit-tap-highlight-color:transparent] focus-visible:ring-2 focus-visible:ring-emerald-600 focus-visible:outline-none dark:focus-visible:ring-emerald-400';

const PAPER = 'bg-white/75 dark:bg-white/[0.06]';

const NOTICE = cn(
    'rounded-3xl border-0 px-4 py-3.5 text-[15px] leading-6',
    PAPER,
);

function Door({
    href,
    label,
    icon: Icon,
}: {
    href: string;
    label: string;
    icon: LucideIcon;
}) {
    return (
        <li className="flex">
            <Link
                href={href}
                prefetch
                className={cn(
                    'flex w-full flex-col items-start gap-2.5 rounded-[1.75rem] p-3.5 text-[15px] leading-5 font-bold text-foreground transition-[scale,background-color] duration-150 ease-out hover:bg-white active:scale-[0.97] @min-[40rem]:flex-row @min-[40rem]:items-center @min-[40rem]:gap-3 @min-[40rem]:p-4 dark:hover:bg-white/10',
                    DISPLAY_FONT,
                    PAPER,
                    PRESSABLE,
                )}
            >
                <span className="flex size-9 shrink-0 items-center justify-center rounded-full bg-foreground/[0.06] text-foreground/75 dark:bg-white/10">
                    <Icon
                        className="size-[18px]"
                        strokeWidth={1.9}
                        aria-hidden="true"
                    />
                </span>
                {label}
            </Link>
        </li>
    );
}

function SnapDoor({ className }: { className?: string }) {
    const { t } = useTranslation('common');

    return (
        <Link
            href={ShowSnapToTrackController().url}
            prefetch
            data-test="dashboard-snap-to-track"
            className={cn(
                'flex w-full items-center gap-3.5 rounded-[1.75rem] bg-foreground p-3 pr-5 text-background transition-[scale,opacity] duration-150 ease-out hover:opacity-90 active:scale-[0.98]',
                PRESSABLE,
                className,
            )}
        >
            <span className="flex size-12 shrink-0 items-center justify-center rounded-[1.15rem] bg-background/10 text-emerald-400 dark:text-emerald-600">
                <ScanLine
                    className="size-[22px]"
                    strokeWidth={2}
                    aria-hidden="true"
                />
            </span>
            <span className="min-w-0">
                <span
                    className={cn(
                        'block text-[17px] leading-6 font-bold',
                        DISPLAY_FONT,
                    )}
                >
                    {t('snap_to_track.launcher.title')}
                </span>
                <span className="block text-sm leading-5 text-balance text-background/65">
                    {t('snap_to_track.launcher.subtitle')}
                </span>
            </span>
        </Link>
    );
}

export default function Dashboard({
    showHealthDataConsent,
    showPrivacyUpdate,
}: {
    showHealthDataConsent: boolean;
    showPrivacyUpdate: boolean;
}) {
    const { t } = useTranslation('common');
    const { currentUser, needsBiometricsReview, locale } = useSharedProps();
    const now = useNow(CLOCK_TICK_MS);
    const dayPart = dayPartAt(now.getHours());
    const table = TABLE[dayPart];
    const firstName = currentUser?.name?.trim().split(/\s+/)[0];
    const composerRef = useRef<ChatInputHandle>(null);

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

    const draftQuestion = useCallback((prompt: string) => {
        composerRef.current?.setMessage(prompt);
        composerRef.current?.focus();
    }, []);

    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent
                variant="sidebar"
                className="h-dvh overflow-hidden md:peer-data-[variant=inset]:h-[calc(100dvh-(--spacing(4)))]"
            >
                <Head title={t('dashboard')}>
                    <link
                        rel="stylesheet"
                        href="https://fonts.bunny.net/css?family=sn-pro:600,700,800&display=swap"
                    />
                </Head>
                <section
                    className={cn(
                        '[container-type:size] relative min-h-0 flex-1 overflow-x-hidden overflow-y-auto overscroll-contain transition-colors duration-1000 motion-reduce:transition-none',
                        table.cloth,
                    )}
                >
                    <div
                        className={cn(
                            'mx-auto flex min-h-full w-full max-w-[72rem] flex-col px-4 @min-[40rem]:px-8',
                            '@min-[54rem]:grid @min-[54rem]:grid-cols-[minmax(0,1fr)_auto] @min-[54rem]:grid-rows-[auto_1fr_1fr_auto_auto] @min-[54rem]:gap-x-12 @min-[54rem]:px-12 @min-[54rem]:pt-8 @min-[54rem]:pb-8',
                        )}
                    >
                        <div className="flex min-h-[100cqh] flex-col pt-3 @min-[54rem]:contents [@container(min-height:56rem)]:min-h-0 [@container(min-height:56rem)]:grow">
                            <div
                                className={cn(
                                    'flex flex-col',
                                    '@min-[54rem]:col-span-2',
                                )}
                            >
                                <div className="flex items-center justify-between md:hidden">
                                    <SidebarTrigger
                                        className={cn(
                                            'size-12 rounded-full',
                                            PAPER,
                                        )}
                                    />
                                    <Link
                                        href={ShowSnapToTrackController().url}
                                        prefetch
                                        aria-label={t(
                                            'sidebar.nav.snap_to_track',
                                        )}
                                        data-test="dashboard-scan-shortcut"
                                        className={cn(
                                            'flex size-12 shrink-0 items-center justify-center rounded-full text-foreground/80 transition-[scale,background-color,color] duration-150 ease-out hover:bg-white hover:text-foreground active:scale-95 motion-reduce:transition-none dark:hover:bg-white/10',
                                            PAPER,
                                            PRESSABLE,
                                        )}
                                    >
                                        <ScanLine
                                            className="size-6"
                                            strokeWidth={1.75}
                                            aria-hidden="true"
                                        />
                                    </Link>
                                </div>
                                <div className="flex flex-col gap-2.5 empty:hidden max-md:mt-3">
                                    <LifecycleBanner />
                                    <BiometricsReviewBanner />
                                    {showHealthDataConsent && (
                                        <HealthDataConsentCard
                                            className={NOTICE}
                                        />
                                    )}
                                    {showPrivacyUpdate && (
                                        <PrivacyUpdateNotice
                                            className={NOTICE}
                                        />
                                    )}
                                    {!currentUser?.is_onboarded &&
                                        !needsBiometricsReview && (
                                            <OnboardingBanner
                                                className={NOTICE}
                                            />
                                        )}
                                </div>
                            </div>

                            <header
                                className={cn(
                                    'mt-4',
                                    '@min-[54rem]:col-start-1 @min-[54rem]:row-start-2 @min-[54rem]:mt-10 @min-[54rem]:self-end',
                                )}
                            >
                                <h1
                                    className={cn(
                                        'text-[2.125rem] leading-[1.04] font-extrabold tracking-[-0.02em] text-foreground @min-[40rem]:text-[2.75rem] @min-[68rem]:text-[3.75rem]',
                                        '@min-[54rem]:text-[3.125rem]',
                                        DISPLAY_FONT,
                                    )}
                                >
                                    {firstName
                                        ? t(
                                              `dashboard_ai.greeting.${dayPart}`,
                                              {
                                                  name: firstName,
                                              },
                                          )
                                        : t(
                                              `dashboard_ai.greeting_anonymous.${dayPart}`,
                                          )}
                                </h1>
                                <p
                                    className={cn(
                                        'mt-3 max-w-[32rem] text-[15px] leading-6 text-pretty text-muted-foreground',
                                        '@max-[54rem]:[@container(max-height:46rem)]:hidden',
                                        '@min-[54rem]:mt-4 @min-[54rem]:text-[17px] @min-[54rem]:leading-7',
                                    )}
                                >
                                    {t('dashboard_ai.description')}
                                </p>
                            </header>

                            <div
                                data-test="dashboard-composer"
                                className={cn(
                                    'sticky bottom-0 z-10 order-last -mx-4 px-4 pt-2 pb-[max(1rem,env(safe-area-inset-bottom))] @min-[40rem]:-mx-8 @min-[40rem]:px-8',
                                    table.cloth,
                                    '@min-[54rem]:static @min-[54rem]:order-none @min-[54rem]:col-start-1 @min-[54rem]:row-start-3 @min-[54rem]:mx-0 @min-[54rem]:mt-7 @min-[54rem]:max-w-[34rem] @min-[54rem]:self-start @min-[54rem]:bg-transparent @min-[54rem]:p-0 @min-[54rem]:dark:bg-transparent',
                                )}
                            >
                                <div
                                    aria-hidden="true"
                                    className={cn(
                                        'pointer-events-none absolute inset-x-0 -top-3 h-3 bg-linear-to-t to-transparent',
                                        table.fade,
                                        '@min-[54rem]:hidden',
                                    )}
                                />
                                <ChatInput
                                    ref={composerRef}
                                    onSubmit={startChat}
                                    disabled={form.processing}
                                    placeholder={t('dashboard_ai.placeholder')}
                                    surfaceClassName="bg-white shadow-[0_1px_2px_rgb(0_0_0/0.05),0_16px_32px_-16px_rgb(0_0_0/0.22)] dark:bg-white/[0.08] dark:shadow-none"
                                />
                                {submitError && (
                                    <p className="px-4 pt-2 text-sm text-destructive">
                                        {submitError}
                                    </p>
                                )}
                            </div>

                            <div
                                className={cn(
                                    'mt-4 flex shrink-0 grow basis-[16.5rem] items-center justify-center @max-[54rem]:[container-type:size]',
                                    '@min-[54rem]:col-start-2 @min-[54rem]:row-span-2 @min-[54rem]:row-start-2 @min-[54rem]:mt-0 @min-[54rem]:self-center @min-[54rem]:pt-10',
                                )}
                            >
                                <DayPlate
                                    now={now}
                                    dayPart={dayPart}
                                    locale={locale}
                                    onPick={draftQuestion}
                                    className="w-[min(100cqw,100cqh,19rem)] @min-[32rem]:w-[min(100cqh,22rem)] @min-[54rem]:w-[23rem] @min-[68rem]:w-[28rem]"
                                />
                            </div>

                            <SnapDoor className="relative mt-4 mb-3 @min-[54rem]:hidden" />
                        </div>

                        <nav
                            aria-label={t('sidebar.group_label')}
                            className="@min-[54rem]:col-span-2 @min-[54rem]:row-start-4 @min-[54rem]:mt-12"
                        >
                            <ul
                                className={cn(
                                    'grid grid-cols-3 gap-2.5',
                                    '@min-[54rem]:grid-cols-[minmax(0,1.7fr)_repeat(3,minmax(0,1fr))] @min-[54rem]:gap-3',
                                )}
                            >
                                <li className="hidden @min-[54rem]:flex">
                                    <SnapDoor />
                                </li>
                                <Door
                                    href={ListHealthEntryController().url}
                                    label={t('sidebar.nav.health_entries')}
                                    icon={ActivityIcon}
                                />
                                <Door
                                    href={mealPlans.index().url}
                                    label={t('sidebar.nav.meal_plans')}
                                    icon={CalendarHeartIcon}
                                />
                                <Door
                                    href={chat.index().url}
                                    label={t('sidebar.nav.chats')}
                                    icon={MessageCircle}
                                />
                            </ul>
                        </nav>

                        <div
                            className={cn(
                                'mt-5 flex flex-col gap-2.5 pb-[max(1.5rem,env(safe-area-inset-bottom))] text-[13px] leading-5 text-muted-foreground',
                                '@min-[54rem]:col-span-2 @min-[54rem]:row-start-5 @min-[54rem]:pb-0',
                            )}
                        >
                            <p className="hidden items-start gap-2 @max-[54rem]:[@container(max-height:46rem)]:flex">
                                <Clock
                                    className="mt-0.5 size-3.5 shrink-0"
                                    aria-hidden="true"
                                />
                                {t('dashboard_ai.description')}
                            </p>
                            <p className="flex items-start gap-2">
                                <TriangleAlert
                                    className="mt-0.5 size-3.5 shrink-0"
                                    aria-hidden="true"
                                />
                                {t('dashboard_ai.disclaimer')}
                            </p>
                        </div>
                    </div>
                </section>
            </AppContent>
        </AppShell>
    );
}
