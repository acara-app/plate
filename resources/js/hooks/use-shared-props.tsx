import { usePage } from '@inertiajs/react';

interface SharedProps {
    auth: {
        user: {
            id: number;
            name: string;
            email: string;
            is_onboarded: boolean;
        };
    };
}

export default function useSharedProps() {
    const page = usePage();

    const props = page.props as unknown as SharedProps;

    return {
        user: props.auth.user,
    };
}
