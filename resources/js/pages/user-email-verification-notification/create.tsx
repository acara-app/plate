// Components
import UserEmailVerificationNotificationController from '@/actions/App/Http/Controllers/UserEmailVerificationNotificationController';
import { logout } from '@/routes';
import { Form, Head } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';
import { useTranslation } from 'react-i18next';

export default function VerifyEmail({ status }: { status?: string }) {
    const { t } = useTranslation('auth');

    return (
        <AuthLayout
            title={t('email_verification.title')}
            description={t('email_verification.description')}
        >
            <Head title={t('email_verification.title')} />

            {status === 'verification-link-sent' && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    {t('email_verification.verification_sent')}
                </div>
            )}

            <Form
                {...UserEmailVerificationNotificationController.store.form()}
                className="space-y-6 text-center"
            >
                {({ processing }) => (
                    <>
                        <Button disabled={processing} variant="secondary">
                            {processing && (
                                <LoaderCircle className="h-4 w-4 animate-spin" />
                            )}
                            {t('email_verification.resend')}
                        </Button>

                        <TextLink
                            href={logout()}
                            className="mx-auto block text-sm"
                        >
                            {t('email_verification.logout')}
                        </TextLink>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
