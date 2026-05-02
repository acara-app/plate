import { usePage } from '@inertiajs/react';

import type { Entitlement } from '@/types/billing';

export default function useSharedProps() {
    const page = usePage();

    const props = page.props;

    return {
        currentUser: props.auth.user,
        sidebarOpen: page.props.sidebarOpen as boolean,
        enablePremiumUpgrades: page.props.enablePremiumUpgrades as boolean,
        locale: page.props.locale as string,
        availableLanguages: page.props.availableLanguages as Record<
            string,
            string
        >,
        entitlement: page.props.entitlement as Entitlement | null,
    };
}
