import AnalyzeSnapToTrackPhotoController from '@/actions/App/Http/Controllers/SnapToTrack/AnalyzeSnapToTrackPhotoController';
import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AiAllowanceNotice } from '@/components/billing/ai-allowance';
import { LifecycleBanner } from '@/components/billing/lifecycle-banner';
import { BiometricsReviewBanner } from '@/components/biometrics-review-banner';
import { currentDayPart, DAYLIGHT } from '@/components/chat/day-part';
import { GLASS } from '@/components/glass';
import {
    PhotoAllowanceCard,
    PhotoAllowanceMenu,
    type PhotoAllowance,
} from '@/components/photo-allowance';
import { MealScanner } from '@/components/snap-to-track/meal-scanner';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { cn } from '@/lib/utils';
import { type AiAllowance } from '@/types';
import { Form, Head } from '@inertiajs/react';
import type { FormComponentRef } from '@inertiajs/core';
import { useEffect, useRef, useState, type DragEvent } from 'react';
import { useTranslation } from 'react-i18next';

interface SnapToTrackIndexProps {
    photoAllowance?: PhotoAllowance;
    analysisRequestId: string;
    aiAllowance: AiAllowance | null;
}

const STAGE =
    '@container-size flex min-h-[26rem] flex-1 items-center justify-center';

const SCANNER_SIZE = 'w-[min(100cqw,calc(100cqh-8rem))] max-w-[27rem]';

const ENTER =
    'fill-mode-both duration-500 ease-out motion-safe:animate-in motion-safe:fade-in motion-safe:slide-in-from-bottom-3';

function firstImage(files: FileList | undefined | null): File | null {
    return (
        Array.from(files ?? []).find((file) =>
            file.type.startsWith('image/'),
        ) ?? null
    );
}

function carriesFiles(event: DragEvent<HTMLElement>): boolean {
    return event.dataTransfer.types.includes('Files');
}

export default function SnapToTrackIndex({
    aiAllowance,
    photoAllowance,
    analysisRequestId,
}: SnapToTrackIndexProps) {
    const { t } = useTranslation('common');
    const [photo, setPhoto] = useState<File | null>(null);
    const [dropping, setDropping] = useState(false);
    const [dayPart] = useState(currentDayPart);
    const formRef = useRef<FormComponentRef>(null);
    const dragDepthRef = useRef(0);

    const scansUsedUp = !!(photoAllowance?.enabled && photoAllowance.exhausted);
    const paused = !!aiAllowance?.exhausted;
    const canScan = !scansUsedUp && !paused;
    const showsAllowanceCard =
        scansUsedUp || !!(photoAllowance?.enabled && photoAllowance.preview);

    function selectPhoto(file: File): void {
        setPhoto(file);
        formRef.current?.clearErrors('photo');
    }

    function acceptsPhoto(): boolean {
        return canScan && !formRef.current?.processing;
    }

    function handleDragEnter(event: DragEvent<HTMLElement>): void {
        if (!acceptsPhoto() || !carriesFiles(event)) {
            return;
        }

        dragDepthRef.current += 1;
        setDropping(true);
    }

    function handleDragOver(event: DragEvent<HTMLElement>): void {
        if (acceptsPhoto() && carriesFiles(event)) {
            event.preventDefault();
        }
    }

    function handleDragLeave(): void {
        dragDepthRef.current = Math.max(0, dragDepthRef.current - 1);

        if (dragDepthRef.current === 0) {
            setDropping(false);
        }
    }

    function handleDrop(event: DragEvent<HTMLElement>): void {
        dragDepthRef.current = 0;
        setDropping(false);

        if (!acceptsPhoto() || !carriesFiles(event)) {
            return;
        }

        event.preventDefault();

        const file = firstImage(event.dataTransfer.files);

        if (file) {
            selectPhoto(file);
        }
    }

    useEffect(() => {
        if (!canScan) {
            return;
        }

        function handlePaste(event: ClipboardEvent): void {
            const file = firstImage(event.clipboardData?.files);

            if (file && !formRef.current?.processing) {
                event.preventDefault();
                setPhoto(file);
                formRef.current?.clearErrors('photo');
            }
        }

        window.addEventListener('paste', handlePaste);

        return () => window.removeEventListener('paste', handlePaste);
    }, [canScan]);

    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent
                variant="sidebar"
                className="h-dvh overflow-hidden overflow-x-hidden md:peer-data-[variant=inset]:h-[calc(100dvh-(--spacing(4)))]"
            >
                <LifecycleBanner className="mx-4 mt-4" />
                <BiometricsReviewBanner className="mx-4 mt-4" />
                <Head title={t('snap_to_track.title')} />
                <section
                    className="@container relative flex min-h-0 flex-1 flex-col overflow-hidden"
                    onDragEnter={handleDragEnter}
                    onDragOver={handleDragOver}
                    onDragLeave={handleDragLeave}
                    onDrop={handleDrop}
                >
                    <div
                        aria-hidden="true"
                        className={cn(
                            'pointer-events-none absolute inset-x-0 top-0 h-[36rem] bg-radial-[at_28%_-8%] to-transparent to-70%',
                            DAYLIGHT[dayPart],
                        )}
                    />
                    <header className="pointer-events-none absolute inset-x-0 top-0 z-20 flex items-start justify-between gap-2 px-3 pt-3">
                        <SidebarTrigger
                            className={cn(
                                'pointer-events-auto size-12 rounded-full md:hidden',
                                GLASS,
                            )}
                        />
                        <PhotoAllowanceMenu
                            allowance={photoAllowance}
                            className={cn(
                                'pointer-events-auto ml-auto min-w-0',
                                GLASS,
                            )}
                        />
                    </header>

                    <div className="relative min-h-0 flex-1 overflow-y-auto overscroll-contain">
                        <div className="mx-auto grid min-h-[min(100%,48rem)] w-full max-w-[30rem] grid-cols-1 grid-rows-[auto_1fr] gap-4 px-3 pt-[4.25rem] pb-[max(0.75rem,env(safe-area-inset-bottom))] @min-[56rem]:min-h-full @min-[56rem]:max-w-[62rem] @min-[56rem]:grid-cols-[minmax(0,1fr)_27rem] @min-[56rem]:grid-rows-[1fr_auto_1fr] @min-[56rem]:gap-x-16 @min-[56rem]:gap-y-0 @min-[56rem]:px-10 @min-[56rem]:pt-[4.5rem] @min-[56rem]:pb-10">
                            <div className="px-1 @min-[56rem]:col-start-1 @min-[56rem]:row-start-2 @min-[56rem]:px-0">
                                <h1 className="sr-only">
                                    {t('snap_to_track.title')}
                                </h1>
                                <h2
                                    className={cn(
                                        'text-[1.75rem] leading-[1.15] font-bold tracking-tight text-balance @min-[56rem]:text-5xl @min-[56rem]:leading-[1.05]',
                                        ENTER,
                                    )}
                                >
                                    {t(
                                        `snap_to_track.index.greeting.${dayPart}`,
                                    )}
                                </h2>
                                <p
                                    className={cn(
                                        'mt-1 text-[15px] leading-snug text-muted-foreground [animation-delay:80ms] @min-[56rem]:hidden',
                                        ENTER,
                                    )}
                                >
                                    {t('snap_to_track.launcher.subtitle')}
                                </p>
                                <p
                                    className={cn(
                                        'mt-5 hidden max-w-[26rem] text-lg leading-relaxed text-pretty text-muted-foreground [animation-delay:80ms] @min-[56rem]:block',
                                        ENTER,
                                    )}
                                >
                                    {t('snap_to_track.index.description')}
                                </p>
                            </div>

                            <div
                                className={cn(
                                    'flex min-h-0 flex-col gap-4 [animation-delay:140ms] @min-[56rem]:col-start-2 @min-[56rem]:row-span-3 @min-[56rem]:row-start-1',
                                    ENTER,
                                )}
                            >
                                {aiAllowance && (
                                    <AiAllowanceNotice
                                        allowance={aiAllowance}
                                    />
                                )}

                                {showsAllowanceCard && (
                                    <PhotoAllowanceCard
                                        allowance={photoAllowance}
                                        variant="scanner"
                                        className={cn(
                                            scansUsedUp &&
                                                '@min-[56rem]:my-auto',
                                        )}
                                    />
                                )}

                                {paused && !scansUsedUp && (
                                    <div className="flex min-h-0 flex-1 flex-col">
                                        <div className={STAGE}>
                                            <MealScanner
                                                photo={null}
                                                onChange={selectPhoto}
                                                processing={false}
                                                uploadPercentage={null}
                                                paused
                                                className={SCANNER_SIZE}
                                            />
                                        </div>
                                    </div>
                                )}

                                {canScan && (
                                    <Form
                                        {...AnalyzeSnapToTrackPhotoController.form()}
                                        ref={formRef}
                                        transform={(data) => ({
                                            ...data,
                                            photo,
                                        })}
                                        className="flex min-h-0 flex-1 flex-col"
                                    >
                                        {({ processing, progress, errors }) => (
                                            <>
                                                <input
                                                    type="hidden"
                                                    name="analysis_request_id"
                                                    value={analysisRequestId}
                                                />
                                                <div className={STAGE}>
                                                    <MealScanner
                                                        photo={photo}
                                                        onChange={selectPhoto}
                                                        processing={processing}
                                                        uploadPercentage={
                                                            progress?.percentage ??
                                                            null
                                                        }
                                                        dropping={dropping}
                                                        error={errors.photo}
                                                        className={SCANNER_SIZE}
                                                    />
                                                </div>
                                            </>
                                        )}
                                    </Form>
                                )}
                            </div>
                        </div>
                    </div>
                </section>
            </AppContent>
        </AppShell>
    );
}
