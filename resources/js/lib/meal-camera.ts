const JPEG_QUALITY = 0.9;

export type MealCameraError =
    'permission_denied' | 'unavailable' | 'unsupported' | 'capture_failed';

export type MealCameraStatus = 'loading' | 'ready' | 'paused' | 'error';

export interface MealCameraState {
    status: MealCameraStatus;
    error: MealCameraError | null;
    capturing: boolean;
    torchAvailable: boolean;
    torchOn: boolean;
}

export interface MealCameraSession {
    open(): void;
    start(): Promise<void>;
    playing(): void;
    toggleTorch(): Promise<void>;
    capture(): Promise<File | null>;
    destroy(): void;
}

type TorchCapabilities = MediaTrackCapabilities & { torch?: boolean };
type TorchConstraintSet = MediaTrackConstraintSet & { torch?: boolean };

export const initialMealCameraState: MealCameraState = {
    status: 'loading',
    error: null,
    capturing: false,
    torchAvailable: false,
    torchOn: false,
};

function stopStream(stream: MediaStream | null): void {
    stream?.getTracks().forEach((track) => track.stop());
}

function cameraError(exception: unknown): MealCameraError {
    return exception instanceof DOMException &&
        ['NotAllowedError', 'SecurityError'].includes(exception.name)
        ? 'permission_denied'
        : 'unavailable';
}

function canUseTorch(track: MediaStreamTrack | undefined): boolean {
    const capabilities = track?.getCapabilities?.() as
        TorchCapabilities | undefined;

    return capabilities?.torch === true;
}

async function captureSquare(video: HTMLVideoElement): Promise<File> {
    const { videoWidth, videoHeight } = video;
    const size = Math.min(videoWidth, videoHeight);
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d');

    if (!size || !context) {
        throw new Error('capture_failed');
    }

    canvas.width = size;
    canvas.height = size;
    context.drawImage(
        video,
        (videoWidth - size) / 2,
        (videoHeight - size) / 2,
        size,
        size,
        0,
        0,
        size,
        size,
    );

    const blob = await new Promise<Blob | null>((resolve) =>
        canvas.toBlob(resolve, 'image/jpeg', JPEG_QUALITY),
    );

    if (!blob) {
        throw new Error('capture_failed');
    }

    return new File([blob], `meal-${Date.now()}.jpg`, { type: 'image/jpeg' });
}

export function createMealCameraSession(
    video: HTMLVideoElement,
    onChange: (state: MealCameraState) => void,
): MealCameraSession {
    const state: MealCameraState = { ...initialMealCameraState };
    let stream: MediaStream | null = null;
    let request = 0;
    let destroyed = false;
    let torchPending = false;

    function update(changes: Partial<MealCameraState>): void {
        Object.assign(state, changes);
        onChange({ ...state });
    }

    function currentTrack(): MediaStreamTrack | undefined {
        return stream?.getVideoTracks()[0];
    }

    function release(): void {
        request += 1;
        stopStream(stream);
        stream = null;
        video.srcObject = null;
        if (state.torchAvailable || state.torchOn) {
            update({ torchAvailable: false, torchOn: false });
        }
    }

    async function start(): Promise<void> {
        release();
        const current = request;
        update({ status: 'loading', error: null, capturing: false });

        if (!navigator.mediaDevices?.getUserMedia) {
            update({ status: 'error', error: 'unsupported' });
            return;
        }

        try {
            const opened = await navigator.mediaDevices.getUserMedia({
                audio: false,
                video: {
                    facingMode: { ideal: 'environment' },
                    width: { ideal: 1920 },
                    height: { ideal: 1440 },
                },
            });

            if (destroyed || current !== request || document.hidden) {
                stopStream(opened);
                return;
            }

            stream = opened;
            video.srcObject = opened;
            opened.getVideoTracks().forEach((track) => {
                track.addEventListener(
                    'ended',
                    () => {
                        if (!destroyed && stream === opened) {
                            release();
                            update({ status: 'error', error: 'unavailable' });
                        }
                    },
                    { once: true },
                );
            });
            await video.play();

            if (!destroyed && stream === opened) {
                update({ torchAvailable: canUseTorch(currentTrack()) });
            }
        } catch (exception) {
            if (destroyed || current !== request) {
                return;
            }
            release();
            update({ status: 'error', error: cameraError(exception) });
        }
    }

    function pause(): void {
        release();
        update({ status: 'paused', capturing: false });
    }

    function open(): void {
        if (document.hidden) {
            pause();
        } else {
            void start();
        }
    }

    function handlePageShow(event: PageTransitionEvent): void {
        if (event.persisted && !document.hidden) {
            void start();
        }
    }

    document.addEventListener('visibilitychange', open);
    window.addEventListener('pagehide', pause);
    window.addEventListener('pageshow', handlePageShow);

    return {
        open,
        start,

        playing() {
            if (stream && video.videoWidth && video.videoHeight) {
                update({ status: 'ready' });
            }
        },

        async toggleTorch() {
            const track = currentTrack();
            if (!track || torchPending) {
                return;
            }

            torchPending = true;
            const next = !state.torchOn;
            const torch: TorchConstraintSet = { torch: next };
            try {
                await track.applyConstraints({ advanced: [torch] });
                if (currentTrack() === track) {
                    update({ torchOn: next });
                }
            } catch {
                if (currentTrack() === track) {
                    update({ torchAvailable: false, torchOn: false });
                }
            } finally {
                torchPending = false;
            }
        },

        async capture() {
            const current = stream;
            if (state.status !== 'ready' || state.capturing || !current) {
                return null;
            }

            update({ capturing: true });
            try {
                const photo = await captureSquare(video);

                return stream === current ? photo : null;
            } catch {
                if (stream === current) {
                    release();
                    update({
                        status: 'error',
                        error: 'capture_failed',
                        capturing: false,
                    });
                }

                return null;
            }
        },

        destroy() {
            destroyed = true;
            release();
            document.removeEventListener('visibilitychange', open);
            window.removeEventListener('pagehide', pause);
            window.removeEventListener('pageshow', handlePageShow);
        },
    };
}
