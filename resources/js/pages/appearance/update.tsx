import { Head } from '@inertiajs/react';

import AppearanceTabs from '@/components/appearance-tabs';
import HeadingSmall from '@/components/heading-small';
import { type BreadcrumbItem } from '@/types';

import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { edit as editAppearance } from '@/routes/appearance';
import { useTranslation } from 'react-i18next';

const getBreadcrumbs = (t: (key: string) => string): BreadcrumbItem[] => [
    {
        title: t('appearance.title'),
        href: editAppearance().url,
    },
];

export default function Update() {
    const { t } = useTranslation('common');
    return (
        <AppLayout breadcrumbs={getBreadcrumbs(t)}>
            <Head title={t('appearance.title')} />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title={t('appearance.title')}
                        description={t('appearance.description')}
                    />
                    <AppearanceTabs />
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
