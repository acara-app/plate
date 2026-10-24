import PasskeyFeedback from '@/components/passkey-feedback';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { confirm, confirmOptions, login, loginOptions } from '@/routes/passkey';
import { router } from '@inertiajs/react';
import type { VerifyResponse } from '@laravel/passkeys';
import { usePasskeyVerify } from '@laravel/passkeys/react';
import { KeyRound, LoaderCircle } from 'lucide-react';
import { useTranslation } from 'react-i18next';

type Props = {
    confirmation?: boolean;
    remember?: boolean;
    disabled?: boolean;
    onProcessingChange?: (processing: boolean) => void;
};

export default function PasskeyVerify({
    confirmation = false,
    remember = false,
    disabled = false,
    onProcessingChange,
}: Props) {
    const { t } = useTranslation('auth');
    const redirect = (response: VerifyResponse) =>
        router.visit(response.redirect ?? dashboard.url());
    const { verify, isLoading, errorInstance, isSupported } = usePasskeyVerify({
        remember,
        routes: {
            options: confirmation ? confirmOptions.url() : loginOptions.url(),
            submit: confirmation ? confirm.url() : login.url(),
        },
        onSuccess: redirect,
    });
    const { errorInstance: autofillErrorInstance } = usePasskeyVerify({
        autofill: !confirmation,
        remember,
        routes: { options: loginOptions.url(), submit: login.url() },
        onSuccess: redirect,
    });

    if (!isSupported) {
        return null;
    }

    const handleVerify = async () => {
        onProcessingChange?.(true);
        try {
            await verify();
        } finally {
            onProcessingChange?.(false);
        }
    };

    return (
        <div className="grid gap-2">
            <Button
                type="button"
                variant="outline"
                className="w-full"
                onClick={handleVerify}
                disabled={disabled || isLoading}
                data-test={confirmation ? 'confirm-passkey' : 'login-passkey'}
            >
                {isLoading ? (
                    <LoaderCircle className="animate-spin" />
                ) : (
                    <KeyRound />
                )}
                {t(
                    `passkeys.${isLoading ? 'verifying' : confirmation ? 'confirm' : 'login'}`,
                )}
            </Button>
            <PasskeyFeedback error={errorInstance ?? autofillErrorInstance} />
        </div>
    );
}
