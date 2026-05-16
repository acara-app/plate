import { router } from '@inertiajs/react';
import { useEffect } from 'react';

type AnalyticsEvent = {
    name: string;
    properties?: Record<string, boolean | number | string | null>;
};

export function useFlashAnalytics(): void {
    useEffect(() => {
        return router.on('flash', (event) => {
            const flash = (event as CustomEvent).detail?.flash;
            const analytics = flash?.analytics as AnalyticsEvent | undefined;

            if (!analytics) {
                return;
            }

            window.umami?.track(analytics.name, analytics.properties);
        });
    }, []);
}
