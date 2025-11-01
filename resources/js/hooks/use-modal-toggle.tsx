import { Nullable } from '@/types/index';
import { useState } from 'react';

export default function useModalToggle() {
    const [isOpen, setIsOpen] = useState(false);

    function open() {
        setIsOpen(true);
    }

    function close() {
        setIsOpen(false);
    }

    return {
        isOpen,
        open,
        close,
        props: {
            isOpen,
            onClose: close,
        },
    };
}

/**
 * Useful for tracking modal state related to an entity
 * e.g. editing a user and knowing which user to show in a modal
 */
export function useModalValueToggle<T>() {
    const [state, setState] = useState<Nullable<T>>(null);
    const isOpen = Boolean(state);

    function open(nextValue: T) {
        setState(nextValue);
    }

    function close() {
        setState(null);
    }

    return {
        isOpen,
        open,
        close,
        state,
        // convenience for spreading
        props: {
            isOpen,
            onClose: close,
        },
    };
}
