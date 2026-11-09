import {
    canOpenMealCamera,
    canUseTorch,
    captureFramedPhoto,
    mealCameraError,
    openMealCamera,
    setTorch,
    stopMealCamera,
    type MealCameraError,
} from './meal-camera';

type ScannerStatus = 'loading' | 'ready' | 'paused' | 'error';

interface ScannerMagics {
    $refs: Record<string, HTMLElement | undefined>;
    $dispatch: (event: string, detail?: unknown) => void;
    $nextTick: (callback?: () => void) => Promise<void>;
}

interface SnapScanner {
    open: boolean;
    status: ScannerStatus;
    error: MealCameraError | null;
    capturing: boolean;
    torchAvailable: boolean;
    torchOn: boolean;
    init(): void;
    destroy(): void;
    show(): void;
    close(): void;
    release(): void;
    start(): Promise<void>;
    pause(): void;
    playing(): void;
    toggleTorch(): Promise<void>;
    capture(): Promise<void>;
    pick(input: HTMLInputElement): void;
}

export function snapScanner(): SnapScanner &
    ThisType<SnapScanner & ScannerMagics> {
    let stream: MediaStream | null = null;
    let request = 0;
    let removeListeners = (): void => {};

    return {
        open: false,
        status: 'loading',
        error: null,
        capturing: false,
        torchAvailable: false,
        torchOn: false,

        init() {
            const handleVisibility = (): void => {
                if (!this.open) {
                    return;
                }
                if (document.hidden) {
                    this.pause();
                } else {
                    void this.start();
                }
            };
            const handlePageHide = (): void => {
                if (this.open) {
                    this.pause();
                }
            };
            const handlePageShow = (event: PageTransitionEvent): void => {
                if (this.open && event.persisted && !document.hidden) {
                    void this.start();
                }
            };

            document.addEventListener('visibilitychange', handleVisibility);
            window.addEventListener('pagehide', handlePageHide);
            window.addEventListener('pageshow', handlePageShow);
            removeListeners = () => {
                document.removeEventListener(
                    'visibilitychange',
                    handleVisibility,
                );
                window.removeEventListener('pagehide', handlePageHide);
                window.removeEventListener('pageshow', handlePageShow);
            };
        },

        destroy() {
            this.release();
            removeListeners();
        },

        show() {
            if (this.open) {
                return;
            }
            this.open = true;
            void this.$nextTick(() => void this.start());
        },

        close() {
            this.release();
            this.open = false;
            this.status = 'loading';
            this.error = null;
            this.capturing = false;
        },

        release() {
            request += 1;
            stopMealCamera(stream);
            stream = null;
            this.torchAvailable = false;
            this.torchOn = false;
            const video = this.$refs.video as HTMLVideoElement | undefined;
            if (video) {
                video.srcObject = null;
            }
        },

        async start() {
            this.release();
            const current = request;
            this.status = 'loading';
            this.error = null;
            this.capturing = false;

            if (!canOpenMealCamera()) {
                this.error = 'unsupported';
                this.status = 'error';
                return;
            }

            try {
                const opened = await openMealCamera();
                const video = this.$refs.video as HTMLVideoElement | undefined;

                if (
                    !this.open ||
                    current !== request ||
                    document.hidden ||
                    !video
                ) {
                    stopMealCamera(opened);
                    return;
                }

                stream = opened;
                video.srcObject = opened;
                opened.getVideoTracks().forEach((track) => {
                    track.addEventListener(
                        'ended',
                        () => {
                            if (stream === opened) {
                                this.release();
                                this.error = 'unavailable';
                                this.status = 'error';
                            }
                        },
                        { once: true },
                    );
                });
                await video.play();

                if (stream === opened) {
                    this.torchAvailable = canUseTorch(
                        opened.getVideoTracks()[0],
                    );
                }
            } catch (exception) {
                if (current !== request) {
                    return;
                }
                this.release();
                this.error = mealCameraError(exception);
                this.status = 'error';
            }
        },

        pause() {
            this.release();
            this.status = 'paused';
            this.capturing = false;
        },

        playing() {
            const video = this.$refs.video as HTMLVideoElement | undefined;
            if (stream && video?.videoWidth && video.videoHeight) {
                this.status = 'ready';
            }
        },

        async toggleTorch() {
            const track = stream?.getVideoTracks()[0];
            if (!track) {
                return;
            }

            const next = !this.torchOn;
            try {
                await setTorch(track, next);
                this.torchOn = next;
            } catch {
                this.torchAvailable = false;
                this.torchOn = false;
            }
        },

        async capture() {
            const video = this.$refs.video as HTMLVideoElement | undefined;
            const current = stream;
            if (
                this.status !== 'ready' ||
                this.capturing ||
                !video ||
                !current
            ) {
                return;
            }

            this.capturing = true;
            try {
                const photo = await captureFramedPhoto(video);
                if (stream !== current) {
                    return;
                }
                this.close();
                this.$dispatch('snap-photo-picked', { photo });
            } catch {
                if (stream !== current) {
                    return;
                }
                this.release();
                this.capturing = false;
                this.error = 'capture_failed';
                this.status = 'error';
            }
        },

        pick(input) {
            const photo = input.files?.[0];
            input.value = '';
            if (!photo) {
                return;
            }
            this.close();
            this.$dispatch('snap-photo-picked', { photo });
        },
    };
}
