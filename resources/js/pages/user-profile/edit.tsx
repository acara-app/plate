import UserProfileController from '@/actions/App/Http/Controllers/UserProfileController';
import { send } from '@/routes/verification';
import { type BreadcrumbItem } from '@/types';
import { PREFERRED_LANGUAGE_OPTIONS } from '@/types/preferred-language';
import { Transition } from '@headlessui/react';
import { Form, Head, Link } from '@inertiajs/react';

import DeleteUser from '@/components/delete-user';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import useSharedProps from '@/hooks/use-shared-props';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import userProfile from '@/routes/user-profile';
import { useTranslation } from 'react-i18next';

const getBreadcrumbs = (t: (key: string) => string): BreadcrumbItem[] => [
    {
        title: t('user_profile.title'),
        href: userProfile.edit().url,
    },
];

export default function Edit({ status }: { status?: string }) {
    const { currentUser, preferredLanguage } = useSharedProps();
    const { t } = useTranslation('common');

    return (
        <AppLayout breadcrumbs={getBreadcrumbs(t)}>
            <Head title={t('user_profile.title')} />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title={t('user_profile.heading')}
                        description={t('user_profile.description')}
                    />

                    <Form
                        {...UserProfileController.update.form()}
                        options={{
                            preserveScroll: true,
                        }}
                        className="space-y-6"
                    >
                        {({ processing, recentlySuccessful, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="name">
                                        {t('user_profile.name_label')}
                                    </Label>

                                    <Input
                                        id="name"
                                        className="mt-1 block w-full"
                                        defaultValue={currentUser.name}
                                        name="name"
                                        required
                                        autoComplete="name"
                                        placeholder={t(
                                            'user_profile.name_placeholder',
                                        )}
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={errors.name}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="email">
                                        {t('user_profile.email_label')}
                                    </Label>

                                    <Input
                                        id="email"
                                        type="email"
                                        className="mt-1 block w-full"
                                        defaultValue={currentUser.email}
                                        name="email"
                                        required
                                        autoComplete="username"
                                        placeholder={t(
                                            'user_profile.email_placeholder',
                                        )}
                                    />

                                    <InputError
                                        className="mt-2"
                                        message={errors.email}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="preferred_language">
                                        {t('user_profile.language_label')}
                                    </Label>

                                    <Select
                                        name="preferred_language"
                                        defaultValue={preferredLanguage}
                                    >
                                        <SelectTrigger id="preferred_language">
                                            <SelectValue
                                                placeholder={t(
                                                    'user_profile.language_placeholder',
                                                )}
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {PREFERRED_LANGUAGE_OPTIONS.map(
                                                (option) => (
                                                    <SelectItem
                                                        key={option.value}
                                                        value={option.value}
                                                    >
                                                        {option.label}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>

                                    <InputError
                                        className="mt-2"
                                        message={errors.preferred_language}
                                    />
                                </div>

                                {currentUser.email_verified_at === null && (
                                    <div>
                                        <p className="-mt-4 text-sm text-muted-foreground">
                                            {t('user_profile.email_unverified')}{' '}
                                            <Link
                                                href={send()}
                                                as="button"
                                                className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                            >
                                                {t(
                                                    'user_profile.resend_verification',
                                                )}
                                            </Link>
                                        </p>

                                        {status ===
                                            'verification-link-sent' && (
                                            <div className="mt-2 text-sm font-medium text-green-600">
                                                {t(
                                                    'user_profile.verification_sent',
                                                )}
                                            </div>
                                        )}
                                    </div>
                                )}

                                <div className="flex items-center gap-4">
                                    <Button
                                        disabled={processing}
                                        data-test="update-profile-button"
                                    >
                                        {t('user_profile.save_button')}
                                    </Button>

                                    <Transition
                                        show={recentlySuccessful}
                                        enter="transition ease-in-out"
                                        enterFrom="opacity-0"
                                        leave="transition ease-in-out"
                                        leaveTo="opacity-0"
                                    >
                                        <p className="text-sm text-neutral-600">
                                            {t('user_profile.saved')}
                                        </p>
                                    </Transition>
                                </div>
                            </>
                        )}
                    </Form>
                </div>

                <DeleteUser />
            </SettingsLayout>
        </AppLayout>
    );
}
