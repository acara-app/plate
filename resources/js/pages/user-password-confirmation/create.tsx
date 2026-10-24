import InputError from '@/components/input-error';
import PasskeyVerify from '@/components/passkey-verify';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { logout } from '@/routes';
import { store } from '@/routes/password/confirm';
import { Form, Head, Link } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

export default function Create({
    hasPassword,
    canConfirmWithPasskey,
}: {
    hasPassword: boolean;
    canConfirmWithPasskey: boolean;
}) {
    const { t } = useTranslation('auth');
    const [verifyingPasskey, setVerifyingPasskey] = useState(false);
    return (
        <AuthLayout
            title={t('passkeys.confirm_title')}
            description={t('passkeys.confirm_description')}
        >
            <Head title={t('password_confirmation.page_title')} />

            {!hasPassword && !canConfirmWithPasskey && (
                <div className="space-y-4">
                    <p className="text-sm text-muted-foreground">
                        {t('passkeys.password_required')}
                    </p>
                    <Button asChild className="w-full">
                        <Link href={logout()} as="button">
                            {t('passkeys.sign_out')}
                        </Link>
                    </Button>
                </div>
            )}

            <Form {...store.form()} resetOnSuccess={['password']}>
                {({ processing, errors }) => (
                    <div className="space-y-6">
                        {canConfirmWithPasskey && (
                            <PasskeyVerify
                                confirmation
                                disabled={processing}
                                onProcessingChange={setVerifyingPasskey}
                            />
                        )}
                        {hasPassword && (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="password">
                                        {t(
                                            'password_confirmation.password_label',
                                        )}
                                    </Label>
                                    <Input
                                        id="password"
                                        type="password"
                                        name="password"
                                        placeholder={t(
                                            'password_confirmation.password_placeholder',
                                        )}
                                        autoComplete="current-password"
                                        autoFocus
                                    />

                                    <InputError message={errors.password} />
                                </div>

                                <div className="flex items-center">
                                    <Button
                                        className="w-full"
                                        disabled={
                                            processing || verifyingPasskey
                                        }
                                        data-test="confirm-password-button"
                                    >
                                        {processing && (
                                            <LoaderCircle className="h-4 w-4 animate-spin" />
                                        )}
                                        {t(
                                            'password_confirmation.confirm_button',
                                        )}
                                    </Button>
                                </div>
                            </>
                        )}
                    </div>
                )}
            </Form>
        </AuthLayout>
    );
}
