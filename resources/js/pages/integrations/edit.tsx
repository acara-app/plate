import HeadingSmall from '@/components/heading-small';
import { ChatPlatformCard } from '@/components/integrations/chat-platform-card';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { edit as integrationsEdit } from '@/routes/integrations';
import { type BreadcrumbItem } from '@/types';
import type { ChatPlatform, ChatPlatformIntegration } from '@/types/messaging';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

interface Props {
    platforms: ChatPlatformIntegration[];
    linking_platform?: ChatPlatform;
    linking_token?: string;
    token_expires_at?: string;
    [key: string]: unknown;
}

export default function Edit() {
    const { t } = useTranslation('common');
    const { platforms, linking_platform, linking_token, token_expires_at } =
        usePage<Props>().props;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t('integrations.title'),
            href: integrationsEdit.url(),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('integrations.title')} />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title={t('integrations.title')}
                        description={t('integrations.description')}
                    />

                    {platforms.map((integration) => (
                        <ChatPlatformCard
                            key={integration.platform}
                            integration={integration}
                            pendingToken={
                                linking_platform === integration.platform
                                    ? (linking_token ?? null)
                                    : null
                            }
                            pendingTokenExpiresAt={
                                linking_platform === integration.platform
                                    ? (token_expires_at ?? null)
                                    : null
                            }
                        />
                    ))}
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
