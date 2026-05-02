import { useHttp } from '@inertiajs/react';
import { useCallback } from 'react';

import telemetry from '@/routes/telemetry';

export type ClientPaywallEvent =
    | 'paywall_shown'
    | 'paywall_dismissed'
    | 'upgrade_clicked';

export interface PaywallEventPayload {
    trigger?: 'cap' | 'feature';
    feature?: string;
    limit_type?: 'rolling' | 'weekly' | 'monthly';
    tier_target?: 'basic' | 'plus';
    surface?: string;
    time_open_ms?: number;
}

interface PaywallTelemetryFormData {
    event: ClientPaywallEvent | '';
    payload: PaywallEventPayload;
}

export function usePaywallTelemetry() {
    const { transform, submit } = useHttp<PaywallTelemetryFormData>(
        telemetry.paywall.record(),
        {
            event: '',
            payload: {},
        },
    );

    const emit = useCallback(
        (event: ClientPaywallEvent, payload?: PaywallEventPayload) => {
            transform(() => ({ event, payload: payload ?? {} }));

            void submit({
                onError: () => {
                    // Telemetry must never block UX. Silently swallow validation errors.
                },
                onHttpException: () => true,
                onNetworkError: () => true,
            });
        },
        [transform, submit],
    );

    return { emit };
}
