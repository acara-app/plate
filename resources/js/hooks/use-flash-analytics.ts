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
            const analytics = flash?.analytics as
                | AnalyticsEvent
                | AnalyticsEvent[]
                | undefined;

            if (!analytics) {
                return;
            }

            const events = Array.isArray(analytics) ? analytics : [analytics];

            for (const analyticsEvent of events) {
                window.umami?.track(
                    analyticsEvent.name,
                    analyticsEvent.properties,
                );
            }
        });
    }, []);
}
