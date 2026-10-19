import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

export function useUnsavedChanges(isDirty: boolean) {
    const allowed = useRef(false);
    const [pendingUrl, setPendingUrl] = useState<string | null>(null);

    useEffect(() => {
        if (!isDirty) {
            return;
        }

        const warnBeforeUnload = (event: BeforeUnloadEvent) => {
            if (!allowed.current) {
                event.preventDefault();
                event.returnValue = '';
            }
        };

        window.addEventListener('beforeunload', warnBeforeUnload);
        const removeBeforeListener = router.on('before', (event) => {
            const { visit } = event.detail;

            if (
                allowed.current ||
                visit.method !== 'get' ||
                visit.url.pathname === window.location.pathname
            ) {
                return;
            }

            setPendingUrl(visit.url.href);

            return false;
        });

        return () => {
            window.removeEventListener('beforeunload', warnBeforeUnload);
            removeBeforeListener();
        };
    }, [isDirty]);

    return {
        pendingUrl,
        allowLeaving: () => {
            allowed.current = true;
        },
        keepEditing: () => setPendingUrl(null),
        discard: () => {
            if (pendingUrl !== null) {
                allowed.current = true;
                router.visit(pendingUrl);
            }
        },
    };
}
