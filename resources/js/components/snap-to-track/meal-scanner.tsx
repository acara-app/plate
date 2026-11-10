import { CameraCaptureDialog } from '@/components/snap-to-track/camera-capture-dialog';
import { cn } from '@/lib/utils';
import {
    Camera,
    ImageDown,
    ImageOff,
    ImagePlus,
    RotateCcw,
    ScanLine,
    TriangleAlert,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';

interface MealScannerProps {
    photo: File | null;
    onChange: (photo: File) => void;
    processing: boolean;
    uploadPercentage: number | null;
    dropping?: boolean;
    paused?: boolean;
    error?: string;
    className?: string;
}

const FRAME =
    'relative isolate flex aspect-square w-full items-center justify-center overflow-hidden rounded-[1.25rem] bg-neutral-900 text-white dark:bg-black/50';

const CORNERS = [
    'top-0 left-0 rounded-tl-2xl border-t-4 border-l-4',
    'top-0 right-0 rounded-tr-2xl border-t-4 border-r-4',
    'bottom-0 left-0 rounded-bl-2xl border-b-4 border-l-4',
    'right-0 bottom-0 rounded-br-2xl border-r-4 border-b-4',
];

const PRESSABLE =
    'cursor-pointer touch-manipulation outline-none select-none [-webkit-tap-highlight-color:transparent] focus-visible:ring-2 focus-visible:ring-white/80 disabled:pointer-events-none disabled:opacity-50';

const DOCK_PILL = cn(
    'inline-flex h-11 min-w-0 items-center justify-center gap-1.5 rounded-full bg-white/10 px-3 text-sm font-medium text-white transition-[scale,background-color] duration-150 ease-out hover:bg-white/[0.16] active:scale-[0.97] motion-reduce:transition-none',
    PRESSABLE,
);

const DOCK_LABEL = 'text-xs font-medium text-white/75';

const ANALYSIS_STAGES = ['spotting', 'portions', 'macros'] as const;

const SCAN_SWEEP_MS = 1800;

function Brackets({ className }: { className?: string }) {
    return (
        <span
            aria-hidden="true"
            className={cn(
                'pointer-events-none absolute inset-4 transition-[inset] duration-300 ease-out motion-reduce:transition-none',
                className,
            )}
        >
            {CORNERS.map((corner) => (
                <span
                    key={corner}
                    className={cn('absolute size-10 border-primary', corner)}
                />
            ))}
        </span>
    );
}

function Plate() {
    return (
        <span
            aria-hidden="true"
            className="pointer-events-none absolute inset-0 transition-transform duration-500 ease-out group-hover/frame:scale-[1.03] motion-reduce:transition-none"
        >
            <span className="absolute inset-[14%] rounded-full bg-linear-to-br from-white/[0.17] to-white/[0.03] shadow-[0_24px_48px_rgba(0,0,0,0.55)]" />
            <span className="absolute inset-[25%] rounded-full bg-linear-to-tl from-white/[0.1] to-black/25 inset-shadow-[0_2px_10px_rgba(0,0,0,0.45)]" />
        </span>
    );
}

function ScanBeam() {
    const [atBottom, setAtBottom] = useState(false);

    useEffect(() => {
        const start = window.setTimeout(() => setAtBottom(true), 50);
        const sweep = window.setInterval(
            () => setAtBottom((current) => !current),
            SCAN_SWEEP_MS,
        );

        return () => {
            window.clearTimeout(start);
            window.clearInterval(sweep);
        };
    }, []);

    return (
        <span
            aria-hidden="true"
            className={cn(
                'pointer-events-none absolute inset-0 transition-transform duration-[1800ms] ease-in-out motion-reduce:hidden',
                atBottom ? 'translate-y-[calc(100%-2px)]' : 'translate-y-0',
            )}
        >
            <span className="block h-0.5 w-full bg-primary shadow-[0_0_18px_4px_rgba(16,185,129,0.55)]" />
        </span>
    );
}

function AnalysisStatus({
    uploadPercentage,
}: {
    uploadPercentage: number | null;
}) {
    const { t } = useTranslation('common');
    const uploading = uploadPercentage !== null && uploadPercentage < 100;
    const [startedAt, setStartedAt] = useState(() => Date.now());
    const [now, setNow] = useState(() => Date.now());

    useEffect(() => {
        if (uploading) {
            return;
        }

        const uploadedAt = Date.now();
        setStartedAt(uploadedAt);
        setNow(uploadedAt);
    }, [uploading]);

    useEffect(() => {
        const ticker = window.setInterval(() => setNow(Date.now()), 500);

        return () => window.clearInterval(ticker);
    }, []);

    const seconds = (now - startedAt) / 1000;
    const stage: number = uploading
        ? -1
        : seconds < 3
          ? 0
          : seconds < 7
            ? 1
            : 2;
    const stageName = ANALYSIS_STAGES[stage] ?? 'sending';
    const pace = seconds < 15 ? 'usual' : seconds < 45 ? 'slow' : 'keep_open';

    return (
        <div className="flex h-full flex-col items-center justify-center gap-2.5 px-2 text-center">
            <div role="status" aria-live="polite">
                <p className="text-lg leading-6 font-semibold tracking-tight">
                    {t('snap_to_track.index.analyzing')}
                </p>
                <p
                    key={stage}
                    className="mt-0.5 text-sm leading-5 text-white/70 duration-300 motion-safe:animate-in motion-safe:fade-in motion-safe:slide-in-from-bottom-1"
                >
                    {t(`snap_to_track.analysis.${stageName}`)}
                    {uploading && (
                        <span aria-hidden="true" className="tabular-nums">
                            {' '}
                            {uploadPercentage}%
                        </span>
                    )}
                </p>
            </div>
            <div aria-hidden="true" className="flex gap-1.5">
                {ANALYSIS_STAGES.map((name, index) => (
                    <span
                        key={name}
                        className={cn(
                            'h-1 w-9 rounded-full transition-colors duration-500 motion-reduce:transition-none',
                            index > stage && 'bg-white/15',
                            index < stage && 'bg-primary',
                            index === stage &&
                                'bg-primary motion-safe:animate-pulse',
                        )}
                    />
                ))}
            </div>
            <p className="text-xs leading-4 text-balance text-white/50">
                {t(`snap_to_track.analysis.${pace}`)}
            </p>
        </div>
    );
}

export function MealScanner({
    photo,
    onChange,
    processing,
    uploadPercentage,
    dropping = false,
    paused = false,
    error,
    className,
}: MealScannerProps) {
    const { t } = useTranslation('common');
    const inputRef = useRef<HTMLInputElement>(null);
    const shutterRef = useRef<HTMLButtonElement>(null);
    const analyzeRef = useRef<HTMLButtonElement>(null);
    const cameraTriggerRef = useRef<HTMLButtonElement | null>(null);
    const [cameraOpen, setCameraOpen] = useState(false);
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);
    const [previewFailed, setPreviewFailed] = useState(false);

    useEffect(() => {
        if (!photo) {
            setPreviewUrl(null);
            return;
        }

        const url = URL.createObjectURL(photo);
        setPreviewUrl(url);
        setPreviewFailed(false);

        return () => URL.revokeObjectURL(url);
    }, [photo]);

    function selectPhoto(file: File): void {
        onChange(file);
        setCameraOpen(false);
    }

    function choosePhoto(): void {
        inputRef.current?.click();
    }

    function openCamera(trigger: HTMLButtonElement): void {
        cameraTriggerRef.current = trigger;
        setCameraOpen(true);
    }

    function restoreCameraFocus(): void {
        const trigger = cameraTriggerRef.current;

        (trigger?.isConnected
            ? trigger
            : (analyzeRef.current ?? shutterRef.current)
        )?.focus();
    }

    const showsPhoto = photo !== null && previewUrl !== null;
    const showsError = !!error && !processing;

    return (
        <div
            role="group"
            aria-label={t('snap_to_track.index.upload_label')}
            inert={paused}
            className={cn(
                'flex flex-col rounded-[2rem] bg-neutral-950 p-3 text-white shadow-[0_36px_70px_-36px_rgb(0_0_0/0.6)] ring-1 ring-white/10 dark:bg-neutral-900',
                paused && '*:opacity-35 *:saturate-0',
                className,
            )}
        >
            <input
                ref={inputRef}
                id="snap-photo"
                type="file"
                accept="image/*"
                aria-label={t('snap_to_track.index.upload_label')}
                aria-invalid={!!error}
                aria-describedby={error ? 'snap-photo-error' : undefined}
                disabled={processing}
                className="sr-only"
                tabIndex={-1}
                onChange={(event) => {
                    const file = event.currentTarget.files?.[0];
                    if (file) {
                        selectPhoto(file);
                    }
                    event.currentTarget.value = '';
                }}
            />

            <div className="relative">
                {showsPhoto ? (
                    <div data-test="snap-photo-preview" className={FRAME}>
                        {previewFailed ? (
                            <p className="flex max-w-64 flex-col items-center gap-3 px-6 text-center text-sm leading-snug text-white/70">
                                <ImageOff
                                    className="size-7"
                                    aria-hidden="true"
                                />
                                {t('snap_to_track.index.preview_unavailable')}
                            </p>
                        ) : (
                            <>
                                <img
                                    src={previewUrl}
                                    alt=""
                                    aria-hidden="true"
                                    className="absolute inset-0 -z-10 size-full scale-125 object-cover opacity-45 blur-2xl"
                                />
                                <img
                                    src={previewUrl}
                                    alt={t('snap_to_track.index.preview_alt')}
                                    className="size-full object-contain duration-300 motion-safe:animate-in motion-safe:fade-in motion-safe:zoom-in-95"
                                    onError={() => setPreviewFailed(true)}
                                />
                            </>
                        )}
                        {processing && (
                            <>
                                <Brackets className="duration-500 motion-safe:animate-in motion-safe:fade-in motion-safe:zoom-in-110" />
                                <ScanBeam />
                            </>
                        )}
                        {!processing && !showsError && !previewFailed && (
                            <p className="pointer-events-none absolute inset-x-3 bottom-3 flex justify-center">
                                <span className="rounded-full bg-black/55 px-3.5 py-1.5 text-center text-xs font-medium text-white/90 backdrop-blur-md">
                                    {t('snap_to_track.index.preview_hint')}
                                </span>
                            </p>
                        )}
                    </div>
                ) : (
                    <button
                        type="button"
                        aria-label={t('snap_to_track.camera.title')}
                        aria-describedby="snap-camera-hint"
                        aria-haspopup="dialog"
                        disabled={processing}
                        onClick={(event) => openCamera(event.currentTarget)}
                        className={cn(
                            FRAME,
                            PRESSABLE,
                            'group/frame transition-[scale,box-shadow] duration-200 ease-out active:scale-[0.985] motion-reduce:transition-none',
                            dropping && 'ring-2 ring-primary',
                        )}
                    >
                        <Plate />
                        <Brackets
                            className={cn(
                                'duration-700 motion-safe:animate-in motion-safe:fade-in motion-safe:zoom-in-110',
                                dropping && 'inset-2',
                            )}
                        />
                        <span className="relative flex flex-col items-center gap-2 text-center">
                            {dropping ? (
                                <ImageDown
                                    className="size-7 text-primary"
                                    aria-hidden="true"
                                />
                            ) : (
                                <Camera className="size-7" aria-hidden="true" />
                            )}
                            <span
                                id="snap-camera-hint"
                                className="max-w-40 text-[15px] leading-tight font-semibold text-balance"
                            >
                                {t(
                                    dropping
                                        ? 'snap_to_track.index.drop_release'
                                        : 'snap_to_track.camera.hint',
                                )}
                            </span>
                        </span>
                        {!dropping && !showsError && (
                            <span className="absolute inset-x-8 bottom-5 hidden text-center text-xs text-balance text-white/55 pointer-fine:block">
                                {t('snap_to_track.index.drop_hint')}
                            </span>
                        )}
                    </button>
                )}

                {showsError && (
                    <p
                        id="snap-photo-error"
                        role="alert"
                        className="absolute inset-x-3 bottom-3 flex items-start gap-2.5 rounded-2xl bg-red-950/85 px-3.5 py-3 text-sm leading-snug text-red-50 ring-1 ring-red-400/30 backdrop-blur-md duration-300 motion-safe:animate-in motion-safe:fade-in motion-safe:slide-in-from-bottom-2"
                    >
                        <TriangleAlert
                            className="mt-0.5 size-4 shrink-0 text-red-300"
                            aria-hidden="true"
                        />
                        {error}
                    </p>
                )}
            </div>

            <div className="@container/dock h-32">
                {processing ? (
                    <AnalysisStatus uploadPercentage={uploadPercentage} />
                ) : showsPhoto ? (
                    <div className="flex h-full flex-col justify-end gap-2 pt-3">
                        <div className="grid grid-cols-[auto_minmax(0,1fr)] gap-2">
                            <button
                                type="button"
                                aria-haspopup="dialog"
                                className={DOCK_PILL}
                                onClick={(event) =>
                                    openCamera(event.currentTarget)
                                }
                            >
                                <RotateCcw
                                    className="size-4 shrink-0 @max-[19rem]/dock:hidden"
                                    aria-hidden="true"
                                />
                                <span className="truncate">
                                    {t('snap_to_track.index.retake')}
                                </span>
                            </button>
                            <button
                                type="button"
                                className={DOCK_PILL}
                                onClick={choosePhoto}
                            >
                                <ImagePlus
                                    className="size-4 shrink-0 @max-[19rem]/dock:hidden"
                                    aria-hidden="true"
                                />
                                <span className="truncate">
                                    {t('snap_to_track.index.replace')}
                                </span>
                            </button>
                        </div>
                        <button
                            ref={analyzeRef}
                            type="submit"
                            className={cn(
                                'inline-flex h-14 items-center justify-center gap-2.5 rounded-full bg-primary px-6 text-base font-semibold text-white transition-[scale,filter] duration-150 ease-out hover:brightness-110 active:scale-[0.98] motion-reduce:transition-none',
                                PRESSABLE,
                            )}
                        >
                            <ScanLine className="size-5" aria-hidden="true" />
                            {t('snap_to_track.index.analyze')}
                        </button>
                    </div>
                ) : (
                    <div className="grid h-full grid-cols-3 items-start justify-items-center pt-3">
                        <button
                            type="button"
                            onClick={choosePhoto}
                            className={cn(
                                'group/dock flex flex-col items-center gap-1.5 rounded-2xl',
                                PRESSABLE,
                            )}
                        >
                            <span className="flex h-20 items-center">
                                <span className="flex size-14 items-center justify-center rounded-full bg-white/10 transition-[scale,background-color] duration-150 ease-out group-hover/dock:bg-white/[0.16] group-active/dock:scale-90 motion-reduce:transition-none">
                                    <ImagePlus
                                        className="size-6"
                                        aria-hidden="true"
                                    />
                                </span>
                            </span>
                            <span className={DOCK_LABEL}>
                                {t('snap_to_track.index.choose_photo')}
                            </span>
                        </button>
                        <button
                            ref={shutterRef}
                            type="button"
                            aria-haspopup="dialog"
                            onClick={(event) => openCamera(event.currentTarget)}
                            className={cn(
                                'group/dock flex flex-col items-center gap-1.5 rounded-2xl',
                                PRESSABLE,
                            )}
                        >
                            <span className="flex size-20 rounded-full border-4 border-white p-1 transition-[scale] duration-150 ease-out group-active/dock:scale-95 motion-reduce:transition-none">
                                <span className="size-full rounded-full bg-primary transition-[filter] duration-150 group-hover/dock:brightness-110" />
                            </span>
                            <span className={DOCK_LABEL}>
                                {t('snap_to_track.index.take_photo')}
                            </span>
                        </button>
                    </div>
                )}
            </div>

            <CameraCaptureDialog
                open={cameraOpen && !processing}
                onOpenChange={setCameraOpen}
                onCloseAutoFocus={restoreCameraFocus}
                onCapture={selectPhoto}
                onChoosePhoto={choosePhoto}
            />
        </div>
    );
}
