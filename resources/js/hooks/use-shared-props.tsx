import { usePage } from '@inertiajs/react';

export default function useSharedProps() {
    const page = usePage();

    const props = page.props;

    return {
        currentUser: props.auth.user,
        sidebarOpen: page.props.sidebarOpen as boolean,
        enablePremiumUpgrades: page.props.enablePremiumUpgrades as boolean,
    };
}
