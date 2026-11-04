const STORAGE_KEY = 'snap_to_track.photo';
const MAX_EDGE = 960;
const JPEG_QUALITY = 0.8;

interface StoredPhoto {
    requestId: string | null;
    dataUrl: string;
}

let pending: Promise<void> = Promise.resolve();

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

export function keepSnapPhoto(file: File | undefined): void {
    writePhoto(null);

    if (!file) {
        return;
    }

    pending = downscale(file)
        .then((dataUrl) => {
            writePhoto({ requestId: null, dataUrl });
        })
        .catch(() => {
            writePhoto(null);
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
    if (!requestId) {
        return null;
    }

    const photo = readPhoto();

    return photo?.requestId === requestId ? photo.dataUrl : null;
}
