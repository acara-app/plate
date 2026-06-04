import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
    interface Window {
        Pusher: typeof Pusher;
        Echo: Echo<'reverb'>;
    }
}

window.Pusher = Pusher;

const reverbScheme = import.meta.env.VITE_REVERB_SCHEME as string | undefined;
const forceTLS = reverbScheme === 'https';
const reverbPort = Number(import.meta.env.VITE_REVERB_PORT);

const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY as string,
    wsHost: import.meta.env.VITE_REVERB_HOST as string,
    wsPort: reverbPort,
    wssPort: reverbPort,
    forceTLS,
    enabledTransports: ['wss'],
});

window.Echo = echo;

export type ConnectionState =
    | 'connecting'
    | 'connected'
    | 'disconnected'
    | 'unavailable'
    | 'failed';

type ConnectionCallback = (
    state: ConnectionState,
    previousState?: ConnectionState,
) => void;

const connectionCallbacks = new Set<ConnectionCallback>();

export function onConnectionStateChange(
    callback: ConnectionCallback,
): () => void {
    connectionCallbacks.add(callback);

    return () => {
        connectionCallbacks.delete(callback);
    };
}

export function getConnectionState(): ConnectionState {
    const connector = echo.connector as {
        pusher?: { connection?: { state?: string } };
    };

    return (
        (connector.pusher?.connection?.state as ConnectionState) ??
        'disconnected'
    );
}

export function reconnect(): void {
    const connector = echo.connector as {
        pusher?: {
            connect?: () => void;
            connection?: { state?: ConnectionState };
        };
    };

    const state = connector.pusher?.connection?.state;

    if (state === 'connected' || state === 'connecting') {
        return;
    }

    connector.pusher?.connect?.();
}

export function disconnect(): void {
    echo.disconnect();
}

const pusherConnector = echo.connector as { pusher?: Pusher };

pusherConnector.pusher?.connection.bind(
    'state_change',
    (states: { current: string; previous: string }) => {
        const current = states.current as ConnectionState;
        const previous = states.previous as ConnectionState;

        for (const callback of connectionCallbacks) {
            callback(current, previous);
        }
    },
);

export { echo };
