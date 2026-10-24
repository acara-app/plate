// Components
import UserEmailVerificationNotificationController from '@/actions/App/Http/Controllers/UserEmailVerificationNotificationController';
import { logout } from '@/routes';
import { Form, Head } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';
import { useTranslation } from 'react-i18next';

export default function VerifyEmail({
    status,
    snapIntent,
}: {
    status?: string;
    snapIntent?: string | null;
}) {
    const { t } = useTranslation('auth');

    return (
        <AuthLayout
            title={t('email_verification.title')}
            description={
                snapIntent
                    ? 'Follow the link in your email, then continue with Snap to Track. Your meal and selected next step are saved for you.'
                    : t('email_verification.description')
            }
            snap={!!snapIntent}
        >
            <Head title={t('email_verification.page_title')} />

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
                        <Button
                            disabled={processing}
                            variant="secondary"
                            className="min-h-11"
                        >
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
