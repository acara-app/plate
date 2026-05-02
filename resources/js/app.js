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

Livewire.start();
