import ListHealthEntryController from '@/actions/App/Http/Controllers/HealthEntry/ListHealthEntryController';
import ShowSnapToTrackController from '@/actions/App/Http/Controllers/SnapToTrack/ShowSnapToTrackController';
import healthSync from '@/routes/health-sync';
import { router } from '@inertiajs/react';
import type { StateModel, StateStore } from '@json-render/react';
import { createContext, useContext } from 'react';

export interface VisualSession {
    busy: boolean;
    sendMessage: (text: string) => void;
    store: (visualId: string, initialState: StateModel) => StateStore;
}

export const VisualSessionContext = createContext<VisualSession | null>(null);

export function useVisualSession(): VisualSession | null {
    return useContext(VisualSessionContext);
}

const DESTINATIONS: Record<string, () => void> = {
    snap: () => router.visit(ShowSnapToTrackController().url),
    log: () => router.visit(ListHealthEntryController().url),
    sync: () => window.location.assign(healthSync.setup().url),
};

export function canSend(session: VisualSession | null): boolean {
    return session !== null && !session.busy;
}

function safeLink(value: unknown): string | null {
    if (typeof value !== 'string') {
        return null;
    }

    try {
        const url = new URL(value);

        return url.protocol === 'https:' &&
            url.hostname !== '' &&
            url.username === '' &&
            url.password === ''
            ? url.href
            : null;
    } catch {
        return null;
    }
}

export function runVisualAction(
    session: VisualSession | null,
    name: string,
    params: Record<string, unknown> = {},
): void {
    if (name === 'sendMessage') {
        const text = params.text;

        if (canSend(session) && typeof text === 'string' && text.trim()) {
            session?.sendMessage(text);
        }

        return;
    }

    if (name === 'openDestination') {
        const destination = params.destination;

        if (
            typeof destination === 'string' &&
            Object.hasOwn(DESTINATIONS, destination)
        ) {
            DESTINATIONS[destination]();
        }

        return;
    }

    if (name === 'openLink') {
        const url = safeLink(params.url);

        if (url) {
            window.open(url, '_blank', 'noopener,noreferrer');
        }
    }
}
