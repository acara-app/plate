import type { Auth } from '@/types/auth';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            locale: string;
            translations: Record<string, unknown>;
            enablePremiumUpgrades: boolean;

            [key: string]: unknown;
        };
    }
}
