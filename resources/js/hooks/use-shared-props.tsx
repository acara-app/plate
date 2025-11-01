import { usePage } from '@inertiajs/react';

interface User {
    id: number;
    name: string;
    email: string;
    is_onboarded: boolean;
    has_meal_plan: boolean;
}

interface SharedProps {
    auth: {
        user: User | null;
    };
}

export default function useSharedProps() {
    const page = usePage();

    const props = page.props as unknown as SharedProps;

    return {
        currentUser: props.auth.user,
    };
}
