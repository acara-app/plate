import { configureEcho, echo, echoIsConfigured } from '@laravel/echo-react';

const reverbScheme = import.meta.env.VITE_REVERB_SCHEME as string | undefined;
const reverbPort = Number(import.meta.env.VITE_REVERB_PORT);

configureEcho({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY as string,
    wsHost: import.meta.env.VITE_REVERB_HOST as string,
    wsPort: reverbPort,
    wssPort: reverbPort,
    forceTLS: reverbScheme === 'https',
    enabledTransports: ['wss'],
});

export function reconnect(): void {
    const connector = echo<'reverb'>().connector as {
        pusher?: {
            connect?: () => void;
            connection?: { state?: string };
        };
    };

    const state = connector.pusher?.connection?.state;

    if (state === 'connected' || state === 'connecting') {
        return;
    }

    connector.pusher?.connect?.();
}

export { echo, echoIsConfigured };
