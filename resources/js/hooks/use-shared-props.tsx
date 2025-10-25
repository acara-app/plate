import { usePage } from '@inertiajs/react';

interface SharedProps {
    auth: {
        user: {
            id: number;
            name: string;
            email: string;
            is_onboarded: boolean;
            has_meal_plan: boolean;
        };
    };
}

export default function useSharedProps() {
    const page = usePage();

    const props = page.props as unknown as SharedProps;

    return {
        currentUser: props.auth.user,
    };
}
