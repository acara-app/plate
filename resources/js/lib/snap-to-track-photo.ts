const STORAGE_KEY = 'snap_to_track.photo';
const MAX_EDGE = 960;
const JPEG_QUALITY = 0.8;
const KEEP_FOR_MS = 60 * 60 * 1000;

interface StoredPhoto {
    requestId: string | null;
    dataUrl: string;
    storedAt: number;
}

let pending: Promise<void> = Promise.resolve();
let generation = 0;

function readPhoto(): StoredPhoto | null {
    try {
        const stored = window.sessionStorage.getItem(STORAGE_KEY);

        return stored ? (JSON.parse(stored) as StoredPhoto) : null;
    } catch {
        return null;
    }
}

function writePhoto(photo: StoredPhoto | null): boolean {
    try {
        if (photo === null) {
            window.sessionStorage.removeItem(STORAGE_KEY);
        } else {
            window.sessionStorage.setItem(STORAGE_KEY, JSON.stringify(photo));
        }

        return true;
    } catch {
        return false;
    }
}

async function downscale(file: File): Promise<string> {
    const bitmap = await createImageBitmap(file);
    const scale = Math.min(1, MAX_EDGE / Math.max(bitmap.width, bitmap.height));
    const canvas = document.createElement('canvas');
    canvas.width = Math.round(bitmap.width * scale);
    canvas.height = Math.round(bitmap.height * scale);
    canvas
        .getContext('2d')
        ?.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
    bitmap.close();

    return canvas.toDataURL('image/jpeg', JPEG_QUALITY);
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

export function recallSnapPhoto(requestId: string | null): string | null {
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

    return requestId && photo.requestId === requestId ? photo.dataUrl : null;
}
