import {
    Alpine,
    Livewire,
} from '../../vendor/livewire/livewire/dist/livewire.esm';

Alpine.magic('clipboard', () => {
    return (subject) => navigator.clipboard?.writeText(subject);
});

window.acaraTrack = (eventName, properties = {}) => {
    if (typeof window.umami?.track !== 'function') {
        return;
    }

    window.umami.track(eventName, properties);
};

const snapImpressions = new Set();
window.acaraTrackOnce = (eventName, properties, key) => {
    const identity = `${eventName}:${key}`;
    if (snapImpressions.has(identity)) {
        return;
    }
    snapImpressions.add(identity);
    window.acaraTrack(eventName, properties);
};

Livewire.start();
