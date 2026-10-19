import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import { CameraOff, ImagePlus, LoaderCircle, RotateCcw, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';

type CameraStatus = 'loading' | 'ready' | 'paused' | 'error';
type CameraError =
    'permission_denied' | 'unavailable' | 'unsupported' | 'capture_failed';

interface CameraCaptureDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onCapture: (photo: File) => void;
    onChoosePhoto: () => void;
    onCloseAutoFocus: () => void;
}

function stopStream(stream: MediaStream | null): void {
    stream?.getTracks().forEach((track) => track.stop());
}

function CameraView({
    open,
    onCapture,
    onChoosePhoto,
}: Pick<CameraCaptureDialogProps, 'open' | 'onCapture' | 'onChoosePhoto'>) {
    const { t } = useTranslation('common');
    const videoRef = useRef<HTMLVideoElement>(null);
    const streamRef = useRef<MediaStream | null>(null);
    const [status, setStatus] = useState<CameraStatus>('loading');
    const [error, setError] = useState<CameraError | null>(null);
    const [attempt, setAttempt] = useState(0);
    const [capturing, setCapturing] = useState(false);

    useEffect(() => {
        if (!open) {
            return;
        }

        let disposed = false;
        let request = 0;
        const video = videoRef.current;

        function releaseCamera(): void {
            request += 1;
            stopStream(streamRef.current);
            streamRef.current = null;
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

            if (!navigator.mediaDevices?.getUserMedia) {
                setError('unsupported');
                setStatus('error');
                return;
            }

            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    audio: false,
                    video: {
                        facingMode: { ideal: 'environment' },
                        width: { ideal: 1920 },
                        height: { ideal: 1440 },
                    },
                });

                if (
                    disposed ||
                    currentRequest !== request ||
                    document.hidden ||
                    !video
                ) {
                    stopStream(stream);
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
            } catch (exception) {
                if (disposed || currentRequest !== request) {
                    return;
                }

                releaseCamera();
                setError(
                    exception instanceof DOMException &&
                        ['NotAllowedError', 'SecurityError'].includes(
                            exception.name,
                        )
                        ? 'permission_denied'
                        : 'unavailable',
                );
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

    function capturePhoto(): void {
        const video = videoRef.current;
        const stream = streamRef.current;
        if (
            status !== 'ready' ||
            capturing ||
            !video ||
            !stream ||
            !video.videoWidth ||
            !video.videoHeight ||
            !video.clientWidth ||
            !video.clientHeight
        ) {
            return;
        }

        setCapturing(true);
        const scale = Math.max(
            video.clientWidth / video.videoWidth,
            video.clientHeight / video.videoHeight,
        );
        const sourceWidth = video.clientWidth / scale;
        const sourceHeight = video.clientHeight / scale;
        const canvas = document.createElement('canvas');
        canvas.width = Math.round(sourceWidth);
        canvas.height = Math.round(sourceHeight);

        function captureFailed(): void {
            if (streamRef.current !== stream) {
                return;
            }
            stopStream(stream);
            streamRef.current = null;
            setCapturing(false);
            setError('capture_failed');
            setStatus('error');
        }

        try {
            const context = canvas.getContext('2d');
            if (!context) {
                captureFailed();
                return;
            }
            context.drawImage(
                video,
                (video.videoWidth - sourceWidth) / 2,
                (video.videoHeight - sourceHeight) / 2,
                sourceWidth,
                sourceHeight,
                0,
                0,
                canvas.width,
                canvas.height,
            );
            canvas.toBlob(
                (blob) => {
                    if (streamRef.current !== stream) {
                        return;
                    }
                    if (!blob) {
                        captureFailed();
                        return;
                    }
                    onCapture(
                        new File([blob], `meal-${Date.now()}.jpg`, {
                            type: 'image/jpeg',
                        }),
                    );
                },
                'image/jpeg',
                0.9,
            );
        } catch {
            captureFailed();
        }
    }

    return (
        <div className="relative min-h-0 flex-1 bg-background text-foreground">
            <video
                ref={videoRef}
                autoPlay
                playsInline
                muted
                aria-label={t('snap_to_track.camera.live_view')}
                className="absolute inset-0 size-full object-cover"
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
                    className="pointer-events-none absolute inset-x-8 top-1/4 bottom-[36%] sm:inset-x-12"
                >
                    <span className="absolute top-0 left-0 size-10 rounded-tl-lg border-t-2 border-l-2 border-foreground" />
                    <span className="absolute top-0 right-0 size-10 rounded-tr-lg border-t-2 border-r-2 border-foreground" />
                    <span className="absolute bottom-0 left-0 size-10 rounded-bl-lg border-b-2 border-l-2 border-foreground" />
                    <span className="absolute right-0 bottom-0 size-10 rounded-br-lg border-r-2 border-b-2 border-foreground" />
                </div>
            )}

            <div
                data-camera-header
                className="absolute inset-x-0 top-0 z-10 flex items-center gap-4 bg-linear-to-b from-background/90 to-transparent pt-[max(1.25rem,env(safe-area-inset-top))] pr-[max(1.25rem,env(safe-area-inset-right))] pb-12 pl-[max(1.25rem,env(safe-area-inset-left))]"
            >
                <DialogClose asChild>
                    <Button
                        type="button"
                        variant="secondary"
                        size="icon-touch"
                        className="rounded-full"
                        aria-label={t('snap_to_track.camera.close')}
                    >
                        <X />
                    </Button>
                </DialogClose>
                <DialogTitle>{t('snap_to_track.camera.title')}</DialogTitle>
            </div>

            {status !== 'ready' && (
                <div
                    data-camera-status
                    className="pointer-events-none absolute inset-x-6 top-24 bottom-56 flex items-center justify-center overflow-y-auto"
                >
                    {error ? (
                        <Alert className="pointer-events-auto max-h-full overflow-y-auto">
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

            <div
                data-camera-controls
                className="absolute inset-x-0 bottom-0 z-10 flex flex-col gap-6 bg-linear-to-t from-background via-background/80 to-transparent pt-12 pr-[max(1.5rem,env(safe-area-inset-right))] pb-[max(2rem,env(safe-area-inset-bottom))] pl-[max(1.5rem,env(safe-area-inset-left))]"
            >
                <p
                    data-camera-hint
                    className="text-center text-sm text-foreground/80"
                >
                    {t('snap_to_track.camera.hint')}
                </p>
                <div className="flex items-center justify-between gap-6">
                    <span className="size-14 shrink-0" aria-hidden="true" />
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="size-24 shrink-0 touch-manipulation rounded-full border-4 border-foreground"
                        disabled={status !== 'ready' || capturing}
                        aria-label={t('snap_to_track.camera.capture')}
                        onClick={capturePhoto}
                    >
                        <span className="size-18 rounded-full bg-foreground" />
                    </Button>
                    <Button
                        type="button"
                        variant="secondary"
                        size="icon-touch"
                        className="rounded-full"
                        aria-label={t('snap_to_track.index.choose_photo')}
                        onClick={onChoosePhoto}
                    >
                        <ImagePlus />
                    </Button>
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
                className="camera-capture dark flex h-dvh w-full max-w-none flex-col gap-0 overflow-hidden rounded-none border-0 p-0 sm:max-w-none lg:h-[48rem] lg:max-h-[90dvh] lg:max-w-lg lg:rounded-3xl"
            >
                <DialogDescription className="sr-only">
                    {t('snap_to_track.camera.hint')}
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
