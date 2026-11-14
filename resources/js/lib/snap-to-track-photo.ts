const STORAGE_KEY = 'snap_to_track.photo';
const MAX_EDGE = 960;
const JPEG_QUALITY = 0.8;
const KEEP_FOR_MS = 60 * 60 * 1000;
const UPLOADABLE_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
const UPLOAD_MAX_EDGE = 2048;
const UPLOAD_JPEG_QUALITY = 0.9;

interface StoredPhoto {
    requestId: string | null;
    dataUrl: string;
    storedAt: number;
}

let pending: Promise<void> = Promise.resolve();
let generation = 0;

function readPhoto(): StoredPhoto | null {
    try {
        const stored = window.localStorage.getItem(STORAGE_KEY);

        return stored ? (JSON.parse(stored) as StoredPhoto) : null;
    } catch {
        return null;
    }
}

function writePhoto(photo: StoredPhoto | null): boolean {
    try {
        if (photo === null) {
            window.localStorage.removeItem(STORAGE_KEY);
        } else {
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify(photo));
        }

        return true;
    } catch {
        return false;
    }
}

function decodePhoto(file: File): Promise<ImageBitmap> {
    return createImageBitmap(file, { imageOrientation: 'from-image' });
}

function drawScaled(bitmap: ImageBitmap, maxEdge: number): HTMLCanvasElement {
    const scale = Math.min(1, maxEdge / Math.max(bitmap.width, bitmap.height));
    const canvas = document.createElement('canvas');
    canvas.width = Math.round(bitmap.width * scale);
    canvas.height = Math.round(bitmap.height * scale);
    const context = canvas.getContext('2d');

    if (context !== null) {
        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, canvas.width, canvas.height);
        context.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
    }

    return canvas;
}

async function downscale(file: File): Promise<string> {
    const bitmap = await decodePhoto(file);

    try {
        return drawScaled(bitmap, MAX_EDGE).toDataURL(
            'image/jpeg',
            JPEG_QUALITY,
        );
    } finally {
        bitmap.close();
    }
}

function toJpegBlob(canvas: HTMLCanvasElement): Promise<Blob | null> {
    return new Promise((resolve) => {
        canvas.toBlob(resolve, 'image/jpeg', UPLOAD_JPEG_QUALITY);
    });
}

export async function prepareSnapPhoto(file: File): Promise<File> {
    try {
        const bitmap = await decodePhoto(file);

        try {
            if (
                UPLOADABLE_TYPES.includes(file.type) &&
                Math.max(bitmap.width, bitmap.height) <= UPLOAD_MAX_EDGE
            ) {
                return file;
            }

            const jpeg = await toJpegBlob(drawScaled(bitmap, UPLOAD_MAX_EDGE));

            if (jpeg === null) {
                return file;
            }

            return new File(
                [jpeg],
                `${file.name.replace(/\.[^.]*$/, '')}.jpg`,
                { type: 'image/jpeg' },
            );
        } finally {
            bitmap.close();
        }
    } catch {
        return file;
    }
}

export function forgetSnapPhoto(): void {
    generation++;
    writePhoto(null);
}

export function keepSnapPhoto(file: File | undefined): void {
    forgetSnapPhoto();

    if (!file) {
        return;
    }

    const current = generation;

    pending = downscale(file)
        .then((dataUrl) => {
            if (current === generation) {
                writePhoto({ requestId: null, dataUrl, storedAt: Date.now() });
            }
        })
        .catch(() => {
            if (current === generation) {
                writePhoto(null);
            }
        });
}

export function tagSnapPhoto(requestId: string | null | undefined): void {
    pending = pending.then(() => {
        const photo = readPhoto();

        if (photo && requestId) {
            writePhoto({ ...photo, requestId });
        }
    });
}

function readUnexpiredPhoto(): StoredPhoto | null {
    const photo = readPhoto();

    if (photo === null) {
        return null;
    }

    if (
        typeof photo.storedAt !== 'number' ||
        Date.now() - photo.storedAt > KEEP_FOR_MS
    ) {
        writePhoto(null);

        return null;
    }

    return photo;
}

export function forgetExpiredSnapPhoto(): void {
    readUnexpiredPhoto();
}

export function recallSnapPhoto(requestId: string | null): string | null {
    const photo = readUnexpiredPhoto();

    if (photo === null) {
        return null;
    }

    return requestId && photo.requestId === requestId ? photo.dataUrl : null;
}

export function recallSnapPhotoFile(requestId: string | null): File | null {
    const dataUrl = recallSnapPhoto(requestId);
    const base64 = dataUrl?.split(',')[1];

    if (!base64) {
        return null;
    }

    try {
        const binary = window.atob(base64);
        const bytes = Uint8Array.from(binary, (character) =>
            character.charCodeAt(0),
        );

        return new File([bytes], 'meal.jpg', { type: 'image/jpeg' });
    } catch {
        return null;
    }
}
