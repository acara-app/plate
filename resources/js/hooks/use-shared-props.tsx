import { User } from '@/types';
import { usePage } from '@inertiajs/react';

interface SharedProps {
    auth: {
        user: User;
        subscribed: boolean;
    };
    sidebarOpen: boolean;
}

export default function useSharedProps() {
    const page = usePage();

    const props = page.props as unknown as SharedProps;

    return {
        currentUser: props.auth.user,
        sidebarOpen: page.props.sidebarOpen as boolean,
    };
}
