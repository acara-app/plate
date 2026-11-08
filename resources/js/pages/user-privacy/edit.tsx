import UserPrivacyController from '@/actions/App/Http/Controllers/UserPrivacyController';
import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { privacy } from '@/routes';
import userPrivacy from '@/routes/user-privacy';
import { type BreadcrumbItem } from '@/types';
import { Form, Head } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

const getBreadcrumbs = (t: (key: string) => string): BreadcrumbItem[] => [
    {
        title: t('privacy_settings.title'),
        href: userPrivacy.edit().url,
    },
];

export default function Edit({
    improveFoodRecognition,
}: {
    improveFoodRecognition: boolean;
}) {
    const { t } = useTranslation('common');

    return (
        <AppLayout breadcrumbs={getBreadcrumbs(t)}>
            <Head title={t('privacy_settings.title')} />
            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title={t('privacy_settings.title')}
                        description={t('privacy_settings.description')}
                    />
                    <Form
                        {...UserPrivacyController.update.form()}
                        method="patch"
                        options={{ preserveScroll: true }}
                        className="space-y-6"
                    >
                        {({ processing }) => (
                            <>
                                <div className="flex items-center justify-between space-x-4">
                                    <div className="flex-1 space-y-1">
                                        <Label htmlFor="improve_food_recognition">
                                            {t(
                                                'privacy_settings.improve_food_recognition',
                                            )}
                                        </Label>
                                        <p className="text-sm text-muted-foreground">
                                            {t(
                                                'privacy_settings.improve_food_recognition_description',
                                            )}{' '}
                                            <a
                                                href={privacy.url()}
                                                className="underline underline-offset-4 hover:text-foreground"
                                            >
                                                {t(
                                                    'privacy_settings.policy_link',
                                                )}
                                            </a>
                                        </p>
                                    </div>
                                    <input
                                        type="hidden"
                                        name="improve_food_recognition"
                                        value="0"
                                    />
                                    <Switch
                                        id="improve_food_recognition"
                                        name="improve_food_recognition"
                                        value="1"
                                        defaultChecked={improveFoodRecognition}
                                    />
                                </div>
                                <div className="flex items-center gap-4">
                                    <Button type="submit" disabled={processing}>
                                        {t('privacy_settings.save')}
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
