import HeadingSmall from '@/components/heading-small';
import PasskeyItem from '@/components/passkey-item';
import PasskeyRegister from '@/components/passkey-register';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { edit } from '@/routes/passkeys';
import { type BreadcrumbItem, type Passkey } from '@/types';
import { Head } from '@inertiajs/react';
import { KeyRound } from 'lucide-react';
import { useTranslation } from 'react-i18next';

export default function PasskeySettings({ passkeys }: { passkeys: Passkey[] }) {
    const { t } = useTranslation('auth');
    const breadcrumbs: BreadcrumbItem[] = [
        { title: t('passkeys.title'), href: edit().url },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('passkeys.title')} />
            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title={t('passkeys.title')}
                        description={t('passkeys.description')}
                    />
                    {passkeys.length ? (
                        <ul className="divide-y rounded-lg border">
                            {passkeys.map((passkey) => (
                                <PasskeyItem
                                    key={passkey.id}
                                    passkey={passkey}
                                />
                            ))}
                        </ul>
                    ) : (
                        <div className="space-y-2 rounded-lg border p-8 text-center">
                            <KeyRound className="mx-auto size-8 text-muted-foreground" />
                            <p className="font-medium">{t('passkeys.empty')}</p>
                            <p className="text-sm text-muted-foreground">
                                {t('passkeys.empty_description')}
                            </p>
                        </div>
                    )}
                    <PasskeyRegister />
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
