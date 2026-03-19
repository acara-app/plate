import HouseholdController from '@/actions/App/Http/Controllers/HouseholdController';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import household from '@/routes/household';
import { type BreadcrumbItem } from '@/types';
import { Transition } from '@headlessui/react';
import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

const MAX_LENGTH = 2000;

const getBreadcrumbs = (t: (key: string) => string): BreadcrumbItem[] => [
    {
        title: t('household.title'),
        href: household.edit().url,
    },
];

export default function Edit({
    householdContext,
}: {
    householdContext: string | null;
}) {
    const { t } = useTranslation('common');
    const [charCount, setCharCount] = useState(
        householdContext?.length ?? 0,
    );

    return (
        <AppLayout breadcrumbs={getBreadcrumbs(t)}>
            <Head title={t('household.title')} />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall
                        title={t('household.heading')}
                        description={t('household.description')}
                    />

                    <Form
                        {...HouseholdController.update.form()}
                        options={{
                            preserveScroll: true,
                        }}
                        className="space-y-6"
                    >
                        {({ processing, recentlySuccessful, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="household_context">
                                        {t('household.label')}
                                    </Label>

                                    <Textarea
                                        id="household_context"
                                        name="household_context"
                                        className="mt-1 min-h-[160px]"
                                        defaultValue={
                                            householdContext ?? ''
                                        }
                                        maxLength={MAX_LENGTH}
                                        placeholder={t(
                                            'household.placeholder',
                                        )}
                                        onChange={(e) =>
                                            setCharCount(
                                                e.target.value.length,
                                            )
                                        }
                                    />

                                    <div className="flex items-center justify-between">
                                        <InputError
                                            message={
                                                errors.household_context
                                            }
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            {charCount}/{MAX_LENGTH}
                                        </p>
                                    </div>
                                </div>

                                <div className="flex items-center gap-4">
                                    <Button disabled={processing}>
                                        {t('household.save_button')}
                                    </Button>

                                    <Transition
                                        show={recentlySuccessful}
                                        enter="transition ease-in-out"
                                        enterFrom="opacity-0"
                                        leave="transition ease-in-out"
                                        leaveTo="opacity-0"
                                    >
                                        <p className="text-sm text-neutral-600">
                                            {t('household.saved')}
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
