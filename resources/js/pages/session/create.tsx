import SessionController from '@/actions/App/Http/Controllers/Auth/SessionController';
import {
    AUTH_ENTER,
    AUTH_FIELD,
    AUTH_HEADLINE,
    AUTH_LEAD,
    AUTH_PRESSABLE,
    AUTH_SOFT_BUTTON,
    AUTH_TEXT_LINK,
    AuthBrand,
    AuthCheck,
    AuthDivider,
    AuthFieldError,
    AuthPasswordInput,
    AuthScreen,
    AuthSubmitButton,
} from '@/components/auth/auth-screen';
import GoogleOAuthButton from '@/components/google-oauth-button';
import PasskeyVerify from '@/components/passkey-verify';
import { carriedEmail, carryEmail } from '@/lib/auth-email-handoff';
import { cn } from '@/lib/utils';
import { register } from '@/routes';
import { request } from '@/routes/password';
import { Form, Link } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';

interface LoginProps {
    status?: string;
    error?: string;
    canResetPassword: boolean;
    canManagePasskeys: boolean;
    snapIntent?: string | null;
}

export default function Login({
    status,
    error,
    canResetPassword,
    canManagePasskeys,
    snapIntent,
}: LoginProps) {
    const { t } = useTranslation('auth');
    const [remember, setRemember] = useState(true);
    const [verifyingPasskey, setVerifyingPasskey] = useState(false);
    const email = useRef<HTMLInputElement>(null);

    useEffect(() => {
        if (window.matchMedia('(pointer: fine)').matches) {
            email.current?.focus({ preventScroll: true });
        }
    }, []);

    return (
        <AuthScreen title={t('login.page_title')}>
            <Form
                {...SessionController.store.form()}
                resetOnSuccess={['password']}
                className="flex flex-1 flex-col"
            >
                {({ processing, errors }) => (
                    <>
                        <header className="flex h-11 items-center">
                            <AuthBrand />
                        </header>

                        <div
                            className={cn(
                                'mt-9 flex flex-1 flex-col motion-safe:slide-in-from-bottom-3',
                                AUTH_ENTER,
                            )}
                        >
                            <h1 className={AUTH_HEADLINE}>
                                {snapIntent
                                    ? 'Continue with Snap to Track'
                                    : t('login.headline')}
                            </h1>
                            <p className={AUTH_LEAD}>
                                {snapIntent
                                    ? 'Sign in to continue with your meal, scan allowance, and selected next step.'
                                    : t('login.lead')}
                            </p>

                            {status && (
                                <p className="mt-6 rounded-2xl bg-emerald-600/10 px-4 py-3 text-sm font-medium text-emerald-800 dark:text-emerald-300">
                                    {status}
                                </p>
                            )}
                            {error && (
                                <p
                                    role="alert"
                                    className="mt-6 rounded-2xl bg-rose-600/10 px-4 py-3 text-sm font-medium text-rose-700 dark:text-rose-300"
                                >
                                    {error}
                                </p>
                            )}

                            <div className="mt-8 grid gap-3">
                                <GoogleOAuthButton />
                                {canManagePasskeys && (
                                    <div className={AUTH_SOFT_BUTTON}>
                                        <PasskeyVerify
                                            remember={remember}
                                            disabled={processing}
                                            onProcessingChange={
                                                setVerifyingPasskey
                                            }
                                        />
                                    </div>
                                )}
                            </div>

                            <AuthDivider>{t('login.or_password')}</AuthDivider>

                            <div className="grid gap-3">
                                <input
                                    ref={email}
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    inputMode="email"
                                    autoComplete={
                                        canManagePasskeys
                                            ? 'email webauthn'
                                            : 'email'
                                    }
                                    autoCapitalize="none"
                                    autoCorrect="off"
                                    spellCheck={false}
                                    enterKeyHint="next"
                                    defaultValue={carriedEmail()}
                                    aria-label={t('login.email')}
                                    aria-invalid={!!errors.email}
                                    placeholder={t('login.email_placeholder')}
                                    className={AUTH_FIELD}
                                />
                                <AuthPasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    autoComplete="current-password"
                                    enterKeyHint="go"
                                    aria-label={t('login.password')}
                                    aria-invalid={!!errors.password}
                                    placeholder={t(
                                        'login.password_placeholder',
                                    )}
                                />
                                <AuthFieldError
                                    message={errors.email ?? errors.password}
                                />
                                <div className="flex min-h-11 items-center justify-between gap-4 px-1">
                                    <label
                                        className={cn(
                                            'flex min-h-11 items-center gap-2.5 rounded-xl pr-2 text-[0.9375rem] font-medium text-foreground',
                                            AUTH_PRESSABLE,
                                        )}
                                    >
                                        <input
                                            id="remember"
                                            type="checkbox"
                                            name="remember"
                                            checked={remember}
                                            onChange={(event) =>
                                                setRemember(
                                                    event.target.checked,
                                                )
                                            }
                                            className="peer sr-only"
                                        />
                                        <AuthCheck checked={remember} />
                                        {t('login.remember_me')}
                                    </label>
                                    {canResetPassword && (
                                        <Link
                                            href={request()}
                                            className={cn(
                                                AUTH_TEXT_LINK,
                                                'text-[0.9375rem] font-medium',
                                            )}
                                        >
                                            {t('login.forgot_password')}
                                        </Link>
                                    )}
                                </div>
                                <AuthSubmitButton
                                    processing={processing}
                                    ready={!verifyingPasskey}
                                    data-test="login-button"
                                >
                                    {t('login.submit')}
                                </AuthSubmitButton>
                            </div>

                            <p className="mt-auto pt-10 text-center text-[0.9375rem] text-muted-foreground">
                                {t('login.no_account')}{' '}
                                <Link
                                    href={register()}
                                    onClick={() =>
                                        carryEmail(email.current?.value ?? '')
                                    }
                                    className={AUTH_TEXT_LINK}
                                >
                                    {t('login.sign_up')}
                                </Link>
                            </p>
                        </div>
                    </>
                )}
            </Form>
        </AuthScreen>
    );
}
