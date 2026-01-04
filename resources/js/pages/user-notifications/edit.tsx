import UserNotificationsController from '@/actions/App/Http/Controllers/UserNotificationsController';
import { type BreadcrumbItem } from '@/types';
import { Transition } from '@headlessui/react';
import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';

import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import userNotifications from '@/routes/user-notifications';
import { useTranslation } from 'react-i18next';

// Breadcrumbs will be set dynamically using translation
const getBreadcrumbs = (t: (key: string) => string): BreadcrumbItem[] => [
    {
        title: t('notifications.title'),
        href: userNotifications.edit().url,
    },
];

interface NotificationSettings {
    glucose_notifications_enabled: boolean;
    glucose_notification_low_threshold: number | null;
    glucose_notification_high_threshold: number | null;
}

interface EditProps {
    notificationSettings: NotificationSettings;
    defaultThresholds: {
        low: number;
        high: number;
    };
}

export default function Edit({
    notificationSettings,
    defaultThresholds,
}: EditProps) {
    const { t } = useTranslation('common');
    const breadcrumbs = getBreadcrumbs(t);

    const [notificationsEnabled, setNotificationsEnabled] = useState(
        notificationSettings.glucose_notifications_enabled,
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('notifications.title')} />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title={t('notifications.preferences')}
                        description={t('notifications.preferences_description')}
                    />

                    <Form
                        {...UserNotificationsController.update.form()}
                        method="patch"
                        options={{
                            preserveScroll: true,
                        }}
                        className="space-y-6"
                    >
                        {({ processing, recentlySuccessful, errors }) => (
                            <>
                                <div className="flex items-center justify-between space-x-4">
                                    <div className="flex-1 space-y-1">
                                        <Label htmlFor="glucoseNotificationsEnabled">
                                            {t(
                                                'notifications.glucose_notifications',
                                            )}
                                        </Label>
                                        <p className="text-sm text-muted-foreground">
                                            {t(
                                                'notifications.glucose_notifications_description',
                                            )}
                                        </p>
                                    </div>
                                    <input
                                        type="hidden"
                                        name="glucoseNotificationsEnabled"
                                        value="0"
                                    />
                                    <Switch
                                        id="glucoseNotificationsEnabled"
                                        name="glucoseNotificationsEnabled"
                                        value="1"
                                        defaultChecked={
                                            notificationSettings.glucose_notifications_enabled
                                        }
                                        onCheckedChange={(checked) =>
                                            setNotificationsEnabled(checked)
                                        }
                                    />
                                </div>
                                <InputError
                                    message={errors.glucoseNotificationsEnabled}
                                />

                                {notificationsEnabled && (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="glucoseNotificationLowThreshold">
                                                {t(
                                                    'notifications.low_threshold',
                                                )}
                                            </Label>
                                            <Input
                                                id="glucoseNotificationLowThreshold"
                                                name="glucoseNotificationLowThreshold"
                                                type="number"
                                                min={40}
                                                max={150}
                                                placeholder={t(
                                                    'notifications.default_placeholder',
                                                    {
                                                        value: defaultThresholds.low,
                                                    },
                                                )}
                                                defaultValue={
                                                    notificationSettings.glucose_notification_low_threshold ??
                                                    defaultThresholds.low ??
                                                    ''
                                                }
                                            />
                                            <p className="text-sm text-muted-foreground">
                                                {t(
                                                    'notifications.low_threshold_description',
                                                    {
                                                        default:
                                                            defaultThresholds.low,
                                                    },
                                                )}
                                            </p>
                                            <InputError
                                                message={
                                                    errors.glucoseNotificationLowThreshold
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="glucoseNotificationHighThreshold">
                                                {t(
                                                    'notifications.high_threshold',
                                                )}
                                            </Label>
                                            <Input
                                                id="glucoseNotificationHighThreshold"
                                                name="glucoseNotificationHighThreshold"
                                                type="number"
                                                min={100}
                                                max={400}
                                                placeholder={t(
                                                    'notifications.default_placeholder',
                                                    {
                                                        value: defaultThresholds.high,
                                                    },
                                                )}
                                                defaultValue={
                                                    notificationSettings.glucose_notification_high_threshold ??
                                                    defaultThresholds.high ??
                                                    ''
                                                }
                                            />
                                            <p className="text-sm text-muted-foreground">
                                                {t(
                                                    'notifications.high_threshold_description',
                                                    {
                                                        default:
                                                            defaultThresholds.high,
                                                    },
                                                )}
                                            </p>
                                            <InputError
                                                message={
                                                    errors.glucoseNotificationHighThreshold
                                                }
                                            />
                                        </div>{' '}
                                    </>
                                )}
                                <div className="flex items-center gap-4">
                                    <Button type="submit" disabled={processing}>
                                        {t('notifications.save_preferences')}
                                    </Button>

                                    <Transition
                                        show={recentlySuccessful}
                                        enter="transition ease-in-out"
                                        enterFrom="opacity-0"
                                        leave="transition ease-in-out"
                                        leaveTo="opacity-0"
                                    >
                                        <p className="text-sm text-muted-foreground">
                                            {t('saved')}.
                                        </p>
                                    </Transition>
                                </div>
                            </>
                        )}
                    </Form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
