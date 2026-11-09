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
    createMealCameraSession,
    initialMealCameraState,
    type MealCameraSession,
    type MealCameraState,
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
    const sessionRef = useRef<MealCameraSession | null>(null);
    const [camera, setCamera] = useState<MealCameraState>(
        initialMealCameraState,
    );
    const { status, error, capturing, torchAvailable, torchOn } = camera;

    useEffect(() => {
        const video = videoRef.current;
        if (!open || !video) {
            return;
        }

        const session = createMealCameraSession(video, setCamera);
        sessionRef.current = session;
        session.open();

        return () => {
            session.destroy();
            sessionRef.current = null;
        };
    }, [open]);

    async function capturePhoto(): Promise<void> {
        const photo = await sessionRef.current?.capture();
        if (photo) {
            onCapture(photo);
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
                        onClick={() => void sessionRef.current?.toggleTorch()}
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
                        onPlaying={() => sessionRef.current?.playing()}
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
                                            void sessionRef.current?.start()
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
