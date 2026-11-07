import {
    forgetSnapPhoto,
    recallSnapPhotoFile,
} from '@/lib/snap-to-track-photo';
import { router, usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';

type SnapPhotoHandoff = {
    url: string;
    request_id: string;
};

export function useSnapPhotoHandoff(): void {
    const handoff = usePage().flash?.snap_to_track_photo as
        SnapPhotoHandoff | undefined;
    const sentFor = useRef<string | null>(null);
    const url = handoff?.url ?? null;
    const requestId = handoff?.request_id ?? null;

    useEffect(() => {
        if (url === null || sentFor.current === url) {
            return;
        }

        const photo = recallSnapPhotoFile(requestId);

        if (photo === null) {
            return;
        }

        sentFor.current = url;
        router.post(
            url,
            { photo },
            {
                preserveScroll: true,
                onSuccess: () => forgetSnapPhoto(),
            },
        );
    }, [url, requestId]);
}
