import {
    Alpine,
    Livewire,
} from '../../vendor/livewire/livewire/dist/livewire.esm';
import {
    forgetSnapPhoto,
    keepSnapPhoto,
    prepareSnapPhoto,
    recallSnapPhoto,
    tagSnapPhoto,
} from './lib/snap-to-track-photo';

Alpine.magic('clipboard', () => {
    return (subject) => navigator.clipboard?.writeText(subject);
});

window.acaraTrack = (eventName, properties = {}) => {
    if (typeof window.umami?.track === 'function') {
        window.umami.track(eventName, properties);

        return;
    }

    if (document.readyState !== 'complete') {
        window.addEventListener(
            'load',
            () => window.umami?.track?.(eventName, properties),
            { once: true },
        );
    }
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

window.acaraKeepSnapPhoto = keepSnapPhoto;
window.acaraPrepareSnapPhoto = prepareSnapPhoto;
window.acaraTagSnapPhoto = tagSnapPhoto;
window.acaraRecallSnapPhoto = recallSnapPhoto;
window.acaraForgetSnapPhoto = forgetSnapPhoto;

Livewire.start();
