import {
    Alpine,
    Livewire,
} from '../../vendor/livewire/livewire/dist/livewire.esm';

Alpine.magic('clipboard', () => {
    return (subject) => navigator.clipboard.writeText(subject);
});

Livewire.start();
