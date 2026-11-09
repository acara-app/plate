const JPEG_QUALITY = 0.9;

export type MealCameraError =
    'permission_denied' | 'unavailable' | 'unsupported' | 'capture_failed';

type TorchCapabilities = MediaTrackCapabilities & { torch?: boolean };
type TorchConstraintSet = MediaTrackConstraintSet & { torch?: boolean };

interface FrameCrop {
    x: number;
    y: number;
    size: number;
}

export function canOpenMealCamera(): boolean {
    return !!navigator.mediaDevices?.getUserMedia;
}

export function openMealCamera(): Promise<MediaStream> {
    return navigator.mediaDevices.getUserMedia({
        audio: false,
        video: {
            facingMode: { ideal: 'environment' },
            width: { ideal: 1920 },
            height: { ideal: 1440 },
        },
    });
}

export function stopMealCamera(stream: MediaStream | null): void {
    stream?.getTracks().forEach((track) => track.stop());
}

export function mealCameraError(exception: unknown): MealCameraError {
    return exception instanceof DOMException &&
        ['NotAllowedError', 'SecurityError'].includes(exception.name)
        ? 'permission_denied'
        : 'unavailable';
}

export function canUseTorch(track: MediaStreamTrack | undefined): boolean {
    const capabilities = track?.getCapabilities?.() as
        TorchCapabilities | undefined;

    return capabilities?.torch === true;
}

export async function setTorch(
    track: MediaStreamTrack,
    on: boolean,
): Promise<void> {
    const torch: TorchConstraintSet = { torch: on };

    await track.applyConstraints({ advanced: [torch] });
}

export function frameCrop(
    video: HTMLVideoElement,
    frame: Element = video,
): FrameCrop | null {
    const { videoWidth, videoHeight } = video;
    const view = video.getBoundingClientRect();
    const box = frame.getBoundingClientRect();

    if (
        !videoWidth ||
        !videoHeight ||
        !view.width ||
        !view.height ||
        !box.width ||
        !box.height
    ) {
        return null;
    }

    const scale = Math.max(view.width / videoWidth, view.height / videoHeight);
    const offsetX = (view.width - videoWidth * scale) / 2;
    const offsetY = (view.height - videoHeight * scale) / 2;
    const size = Math.floor(
        Math.min(
            Math.min(box.width, box.height) / scale,
            videoWidth,
            videoHeight,
        ),
    );
    const x = (box.left - view.left - offsetX) / scale;
    const y = (box.top - view.top - offsetY) / scale;

    return {
        x: Math.round(Math.min(Math.max(x, 0), videoWidth - size)),
        y: Math.round(Math.min(Math.max(y, 0), videoHeight - size)),
        size,
    };
}

export async function captureFramedPhoto(
    video: HTMLVideoElement,
    frame: Element = video,
): Promise<File> {
    const crop = frameCrop(video, frame);
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d');

    if (!crop || !context) {
        throw new Error('capture_failed');
    }

    canvas.width = crop.size;
    canvas.height = crop.size;
    context.drawImage(
        video,
        crop.x,
        crop.y,
        crop.size,
        crop.size,
        0,
        0,
        crop.size,
        crop.size,
    );

    const blob = await new Promise<Blob | null>((resolve) =>
        canvas.toBlob(resolve, 'image/jpeg', JPEG_QUALITY),
    );

    if (!blob) {
        throw new Error('capture_failed');
    }

    return new File([blob], `meal-${Date.now()}.jpg`, { type: 'image/jpeg' });
}
