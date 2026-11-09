import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    canOpenMealCamera,
    canUseTorch,
    captureFramedPhoto,
    mealCameraError,
    openMealCamera,
    setTorch,
    stopMealCamera,
    type MealCameraError,
} from '@/lib/meal-camera';
import { cn } from '@/lib/utils';
import {
    CameraOff,
    ImagePlus,
    Lightbulb,
    LoaderCircle,
    RotateCcw,
    X,
    Zap,
    ZapOff,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';

type CameraStatus = 'loading' | 'ready' | 'paused' | 'error';

interface CameraCaptureDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onCapture: (photo: File) => void;
    onChoosePhoto: () => void;
    onCloseAutoFocus: () => void;
}

const ROUND_CONTROL =
    'rounded-full bg-white/10 text-white hover:bg-white/20 hover:text-white focus-visible:ring-white/50';

function CameraView({
    open,
    onCapture,
    onChoosePhoto,
}: Pick<CameraCaptureDialogProps, 'open' | 'onCapture' | 'onChoosePhoto'>) {
    const { t } = useTranslation('common');
    const videoRef = useRef<HTMLVideoElement>(null);
    const streamRef = useRef<MediaStream | null>(null);
    const [status, setStatus] = useState<CameraStatus>('loading');
    const [error, setError] = useState<MealCameraError | null>(null);
    const [attempt, setAttempt] = useState(0);
    const [capturing, setCapturing] = useState(false);
    const [torchAvailable, setTorchAvailable] = useState(false);
    const [torchOn, setTorchOn] = useState(false);

    useEffect(() => {
        if (!open) {
            return;
        }

        let disposed = false;
        let request = 0;
        const video = videoRef.current;

        function releaseCamera(): void {
            request += 1;
            stopMealCamera(streamRef.current);
            streamRef.current = null;
            setTorchAvailable(false);
            setTorchOn(false);
            if (video) {
                video.srcObject = null;
            }
        }

        async function startCamera(): Promise<void> {
            releaseCamera();
            const currentRequest = request;
            setStatus('loading');
            setError(null);
            setCapturing(false);

            if (!canOpenMealCamera()) {
                setError('unsupported');
                setStatus('error');
                return;
            }

            try {
                const stream = await openMealCamera();

                if (
                    disposed ||
                    currentRequest !== request ||
                    document.hidden ||
                    !video
                ) {
                    stopMealCamera(stream);
                    return;
                }

                streamRef.current = stream;
                video.srcObject = stream;
                stream.getVideoTracks().forEach((track) => {
                    track.addEventListener(
                        'ended',
                        () => {
                            if (!disposed && streamRef.current === stream) {
                                releaseCamera();
                                setError('unavailable');
                                setStatus('error');
                            }
                        },
                        { once: true },
                    );
                });
                await video.play();

                if (!disposed && streamRef.current === stream) {
                    setTorchAvailable(canUseTorch(stream.getVideoTracks()[0]));
                }
            } catch (exception) {
                if (disposed || currentRequest !== request) {
                    return;
                }

                releaseCamera();
                setError(mealCameraError(exception));
                setStatus('error');
            }
        }

        function pauseCamera(): void {
            releaseCamera();
            setStatus('paused');
            setCapturing(false);
        }

        function handleVisibility(): void {
            if (document.hidden) {
                pauseCamera();
            } else {
                void startCamera();
            }
        }

        function handlePageShow(event: PageTransitionEvent): void {
            if (event.persisted && !document.hidden) {
                void startCamera();
            }
        }

        handleVisibility();
        document.addEventListener('visibilitychange', handleVisibility);
        window.addEventListener('pagehide', pauseCamera);
        window.addEventListener('pageshow', handlePageShow);

        return () => {
            disposed = true;
            releaseCamera();
            document.removeEventListener('visibilitychange', handleVisibility);
            window.removeEventListener('pagehide', pauseCamera);
            window.removeEventListener('pageshow', handlePageShow);
        };
    }, [open, attempt]);

    async function toggleTorch(): Promise<void> {
        const track = streamRef.current?.getVideoTracks()[0];
        if (!track) {
            return;
        }

        const next = !torchOn;
        try {
            await setTorch(track, next);
            setTorchOn(next);
        } catch {
            setTorchAvailable(false);
            setTorchOn(false);
        }
    }

    async function capturePhoto(): Promise<void> {
        const video = videoRef.current;
        const stream = streamRef.current;
        if (status !== 'ready' || capturing || !video || !stream) {
            return;
        }

        setCapturing(true);
        try {
            const photo = await captureFramedPhoto(video);
            if (streamRef.current === stream) {
                onCapture(photo);
            }
        } catch {
            if (streamRef.current !== stream) {
                return;
            }
            stopMealCamera(stream);
            streamRef.current = null;
            setTorchAvailable(false);
            setTorchOn(false);
            setCapturing(false);
            setError('capture_failed');
            setStatus('error');
        }
    }

    return (
        <div
            data-camera-view
            className="grid min-h-0 flex-1 grid-rows-[auto_auto_minmax(0,1fr)_auto] bg-black text-white [@media(orientation:landscape)_and_(max-height:32rem)]:grid-cols-[minmax(0,1fr)_auto] [@media(orientation:landscape)_and_(max-height:32rem)]:grid-rows-[auto_minmax(0,1fr)]"
        >
            <div
                data-camera-header
                className="row-start-1 flex items-center justify-between gap-4 pt-[max(1.25rem,env(safe-area-inset-top))] pr-[max(1.25rem,env(safe-area-inset-right))] pb-4 pl-[max(1.25rem,env(safe-area-inset-left))] [@media(max-height:32rem)]:pt-[max(0.75rem,env(safe-area-inset-top))] [@media(max-height:32rem)]:pb-3 [@media(orientation:landscape)_and_(max-height:32rem)]:col-span-full"
            >
                <DialogClose asChild>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-touch"
                        className={ROUND_CONTROL}
                        aria-label={t('snap_to_track.camera.close')}
                    >
                        <X />
                    </Button>
                </DialogClose>
                <DialogTitle className="sr-only">
                    {t('snap_to_track.camera.title')}
                </DialogTitle>
                {torchAvailable && (
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-touch"
                        className={cn(
                            ROUND_CONTROL,
                            torchOn &&
                                'bg-white text-black hover:bg-white/90 hover:text-black',
                        )}
                        aria-label={t(
                            `snap_to_track.camera.${torchOn ? 'flash_off' : 'flash_on'}`,
                        )}
                        onClick={() => void toggleTorch()}
                    >
                        {torchOn ? <Zap /> : <ZapOff />}
                    </Button>
                )}
            </div>

            <p
                data-camera-tip
                className="row-start-2 mr-[max(1rem,env(safe-area-inset-right))] mb-4 ml-[max(1rem,env(safe-area-inset-left))] flex items-start gap-3 rounded-2xl bg-white/10 px-4 py-3 text-sm leading-snug text-white/90 [@media(max-height:32rem)]:hidden"
            >
                <Lightbulb
                    className="size-5 shrink-0 text-amber-300"
                    aria-hidden="true"
                />
                <span>{t('snap_to_track.camera.tip')}</span>
            </p>

            <div
                data-camera-stage
                className="@container-size relative row-start-3 mr-[max(1rem,env(safe-area-inset-right))] ml-[max(1rem,env(safe-area-inset-left))] flex min-h-0 min-w-0 items-center justify-center [@media(orientation:landscape)_and_(max-height:32rem)]:col-start-1 [@media(orientation:landscape)_and_(max-height:32rem)]:row-start-2 [@media(orientation:landscape)_and_(max-height:32rem)]:mb-[max(0.75rem,env(safe-area-inset-bottom))]"
            >
                <div
                    data-camera-frame
                    className="relative aspect-square w-[min(100cqw,100cqh)] shrink-0 overflow-hidden rounded-3xl bg-neutral-900"
                >
                    <video
                        ref={videoRef}
                        autoPlay
                        playsInline
                        muted
                        aria-label={t('snap_to_track.camera.live_view')}
                        className="absolute inset-0 size-full object-cover object-center"
                        onPlaying={() => {
                            if (
                                streamRef.current &&
                                videoRef.current?.videoWidth &&
                                videoRef.current?.videoHeight
                            ) {
                                setStatus('ready');
                            }
                        }}
                    />
                    {status === 'ready' && (
                        <div
                            aria-hidden="true"
                            data-camera-guide
                            className="pointer-events-none absolute inset-3"
                        >
                            <span className="absolute top-0 left-0 size-10 rounded-tl-2xl border-t-4 border-l-4 border-primary" />
                            <span className="absolute top-0 right-0 size-10 rounded-tr-2xl border-t-4 border-r-4 border-primary" />
                            <span className="absolute bottom-0 left-0 size-10 rounded-bl-2xl border-b-4 border-l-4 border-primary" />
                            <span className="absolute right-0 bottom-0 size-10 rounded-br-2xl border-r-4 border-b-4 border-primary" />
                        </div>
                    )}
                </div>

                {status !== 'ready' && (
                    <div
                        data-camera-status
                        className="pointer-events-none absolute inset-0 flex items-center justify-center overflow-y-auto p-4"
                    >
                        {error ? (
                            <Alert className="pointer-events-auto max-h-full max-w-sm overflow-y-auto">
                                <CameraOff />
                                <AlertTitle>
                                    {t('snap_to_track.camera.error_heading')}
                                </AlertTitle>
                                <AlertDescription>
                                    <p>{t(`snap_to_track.camera.${error}`)}</p>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="touch"
                                        className="mt-3 h-auto w-full whitespace-normal"
                                        onClick={() =>
                                            setAttempt((value) => value + 1)
                                        }
                                    >
                                        <RotateCcw data-icon="inline-start" />
                                        {t('snap_to_track.camera.retry')}
                                    </Button>
                                </AlertDescription>
                            </Alert>
                        ) : (
                            <div
                                role="status"
                                className="flex flex-col items-center gap-4 text-center"
                            >
                                <LoaderCircle
                                    className="size-7 animate-spin"
                                    aria-hidden="true"
                                />
                                <p>
                                    {t(
                                        `snap_to_track.camera.${status === 'paused' ? 'paused' : 'loading'}`,
                                    )}
                                </p>
                            </div>
                        )}
                    </div>
                )}
            </div>

            <div
                data-camera-controls
                className="row-start-4 flex min-h-0 flex-col justify-center pt-6 pr-[max(1.5rem,env(safe-area-inset-right))] pb-[max(1.5rem,env(safe-area-inset-bottom))] pl-[max(1.5rem,env(safe-area-inset-left))] [@media(max-height:32rem)]:pt-4 [@media(max-height:32rem)]:pb-[max(1rem,env(safe-area-inset-bottom))] [@media(orientation:landscape)_and_(max-height:32rem)]:col-start-2 [@media(orientation:landscape)_and_(max-height:32rem)]:row-start-2 [@media(orientation:landscape)_and_(max-height:32rem)]:pt-0 [@media(orientation:landscape)_and_(max-height:32rem)]:pl-0"
            >
                <div
                    data-camera-buttons
                    className="grid grid-cols-3 items-center justify-items-center gap-4 [@media(orientation:landscape)_and_(max-height:32rem)]:flex [@media(orientation:landscape)_and_(max-height:32rem)]:flex-col"
                >
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-touch"
                        className={ROUND_CONTROL}
                        aria-label={t('snap_to_track.index.choose_photo')}
                        onClick={onChoosePhoto}
                    >
                        <ImagePlus />
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="size-20 shrink-0 touch-manipulation rounded-full border-4 border-white p-1 transition-transform hover:bg-transparent focus-visible:ring-white/50 active:scale-95 motion-reduce:transition-none"
                        disabled={status !== 'ready' || capturing}
                        aria-label={t('snap_to_track.camera.capture')}
                        onClick={() => void capturePhoto()}
                    >
                        <span className="size-full rounded-full bg-primary" />
                    </Button>
                    <span
                        data-camera-spacer
                        className="size-14 shrink-0 [@media(orientation:landscape)_and_(max-height:32rem)]:hidden"
                        aria-hidden="true"
                    />
                </div>
            </div>
        </div>
    );
}

export function CameraCaptureDialog({
    open,
    onOpenChange,
    onCapture,
    onChoosePhoto,
    onCloseAutoFocus,
}: CameraCaptureDialogProps) {
    const { t } = useTranslation('common');

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent
                showCloseButton={false}
                onCloseAutoFocus={(event) => {
                    event.preventDefault();
                    onCloseAutoFocus();
                }}
                className="camera-capture dark flex h-dvh w-full max-w-none flex-col gap-0 overflow-hidden rounded-none border-0 bg-black p-0 sm:max-w-none lg:h-[48rem] lg:max-h-[90dvh] lg:max-w-lg lg:rounded-3xl"
            >
                <DialogDescription className="sr-only">
                    {t('snap_to_track.camera.tip')}
                </DialogDescription>
                <CameraView
                    open={open}
                    onCapture={onCapture}
                    onChoosePhoto={onChoosePhoto}
                />
            </DialogContent>
        </Dialog>
    );
}
