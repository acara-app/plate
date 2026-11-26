import UserController from '@/actions/App/Http/Controllers/UserController';
import {
    AUTH_ENTER,
    AUTH_FIELD,
    AUTH_HEADLINE,
    AUTH_ICON_BUTTON,
    AUTH_LEAD,
    AUTH_PRESSABLE,
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
import { carriedEmail, carryEmail } from '@/lib/auth-email-handoff';
import { cn } from '@/lib/utils';
import { login, privacy, terms } from '@/routes';
import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Pencil } from 'lucide-react';
import { useLayoutEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';

const PASSWORD_MIN = 12;

const CONSENTS = [
    {
        key: 'accepted_disclaimer',
        required: true,
        title: 'register.disclaimer_title',
    },
    {
        key: 'health_data_consent',
        required: true,
        title: 'register.health_data_consent_title',
    },
    {
        key: 'improve_food_recognition',
        required: false,
        title: 'register.improve_food_recognition_title',
    },
] as const;

const SNAP_DESCRIPTIONS = {
    save: 'Create your free account to save this meal and correct its breakdown.',
    upgrade:
        'Create your account, then pay for your selected plan securely with Stripe. You can verify your email afterwards.',
    preview:
        'Create and verify your free account, then activate one photo scan per day for three days. No card or automatic subscription.',
    scan: 'Keep your meals together in your food log. Your remaining free scans follow your account.',
};

const LEGAL_LINK =
    'underline underline-offset-4 [-webkit-tap-highlight-color:transparent] hover:text-foreground';

function isEmail(value: string): boolean {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim());
}

export default function Register({
    snapIntent,
}: {
    snapIntent?: keyof typeof SNAP_DESCRIPTIONS | null;
}) {
    const { t } = useTranslation('auth');
    const form = useForm({
        email: carriedEmail(),
        password: '',
        accepted_disclaimer: false,
        health_data_consent: false,
        improve_food_recognition: false,
    });
    const [step, setStep] = useState(0);
    const [isBack, setIsBack] = useState(false);
    const [showProblem, setShowProblem] = useState(false);
    const input = useRef<HTMLInputElement>(null);
    const focusRequested = useRef(false);

    form.transform((data) => ({
        ...data,
        email: data.email.trim().toLowerCase(),
    }));

    useLayoutEffect(() => {
        if (
            focusRequested.current ||
            window.matchMedia('(pointer: fine)').matches
        ) {
            input.current?.focus({ preventScroll: true });
        }

        focusRequested.current = false;
    }, [step]);

    const length = form.data.password.length;
    const emailReady = isEmail(form.data.email);
    const passwordReady = length >= PASSWORD_MIN;
    const isMissingConsent = CONSENTS.some(
        (consent) => consent.required && !form.data[consent.key],
    );

    const go = (next: number) => {
        focusRequested.current = true;
        setIsBack(next < step);
        setShowProblem(false);
        setStep(next);
    };

    const advance = () => {
        if (step === 0) {
            return emailReady ? go(1) : setShowProblem(true);
        }

        if (step === 1) {
            return passwordReady ? go(2) : setShowProblem(true);
        }

        if (isMissingConsent) {
            return setShowProblem(true);
        }

        form.post(UserController.store.url(), {
            preserveScroll: true,
            onError: (errors) => go(errors.email ? 0 : errors.password ? 1 : 2),
        });
    };

    return (
        <AuthScreen title={t('register.page_title')}>
            <form
                noValidate
                onSubmit={(event) => {
                    event.preventDefault();
                    advance();
                }}
                className="flex flex-1 flex-col"
            >
                <header className="flex h-11 items-center gap-4">
                    {step === 0 ? (
                        <AuthBrand />
                    ) : (
                        <button
                            type="button"
                            onClick={() => go(step - 1)}
                            aria-label={t('register.back')}
                            className={AUTH_ICON_BUTTON}
                        >
                            <ArrowLeft className="size-5" aria-hidden="true" />
                        </button>
                    )}
                    <div
                        role="progressbar"
                        aria-label={t('register.progress')}
                        aria-valuemin={1}
                        aria-valuemax={3}
                        aria-valuenow={step + 1}
                        className="ml-auto flex w-28 gap-1.5"
                    >
                        {[0, 1, 2].map((index) => (
                            <span
                                key={index}
                                className="h-1.5 flex-1 overflow-hidden rounded-full bg-foreground/10"
                            >
                                <span
                                    className={cn(
                                        'block size-full origin-left rounded-full bg-foreground transition-transform duration-300 ease-[cubic-bezier(0.23,1,0.32,1)] motion-reduce:transition-none',
                                        index <= step
                                            ? 'scale-x-100'
                                            : 'scale-x-0',
                                    )}
                                />
                            </span>
                        ))}
                    </div>
                </header>

                <div
                    key={step}
                    className={cn(
                        'mt-9 flex flex-1 flex-col',
                        AUTH_ENTER,
                        isBack
                            ? 'motion-safe:slide-in-from-left-6'
                            : 'motion-safe:slide-in-from-right-6',
                    )}
                >
                    {step === 0 && (
                        <>
                            <h1 className={AUTH_HEADLINE}>
                                {snapIntent
                                    ? 'Continue with Snap to Track'
                                    : t('register.headline')}
                            </h1>
                            <p className={AUTH_LEAD}>
                                {snapIntent
                                    ? SNAP_DESCRIPTIONS[snapIntent]
                                    : t('register.lead')}
                            </p>
                            <div className="mt-8">
                                <GoogleOAuthButton />
                            </div>
                            <AuthDivider>{t('register.or_email')}</AuthDivider>
                            <div className="grid gap-3">
                                <input
                                    ref={input}
                                    id="email"
                                    type="email"
                                    name="email"
                                    inputMode="email"
                                    autoComplete="email"
                                    autoCapitalize="none"
                                    autoCorrect="off"
                                    spellCheck={false}
                                    enterKeyHint="next"
                                    aria-label={t('register.email')}
                                    placeholder={t(
                                        'register.email_placeholder',
                                    )}
                                    value={form.data.email}
                                    onChange={(event) => {
                                        form.setData(
                                            'email',
                                            event.target.value,
                                        );
                                        form.clearErrors('email');
                                        setShowProblem(false);
                                    }}
                                    aria-invalid={
                                        !!form.errors.email ||
                                        (showProblem && !emailReady)
                                    }
                                    className={AUTH_FIELD}
                                />
                                <AuthFieldError
                                    message={
                                        form.errors.email ??
                                        (showProblem &&
                                            !emailReady &&
                                            t('register.email_invalid'))
                                    }
                                />
                                {form.errors.email && (
                                    <Link
                                        href={login()}
                                        onClick={() =>
                                            carryEmail(form.data.email)
                                        }
                                        className={cn(
                                            AUTH_TEXT_LINK,
                                            'justify-self-start',
                                        )}
                                    >
                                        {t('register.log_in_instead')}
                                    </Link>
                                )}
                                <AuthSubmitButton ready={emailReady}>
                                    {t('register.continue')}
                                </AuthSubmitButton>
                            </div>
                            <p className="mt-auto pt-10 text-center text-[0.9375rem] text-muted-foreground">
                                {t('register.already_have_account')}{' '}
                                <Link
                                    href={login()}
                                    onClick={() => carryEmail(form.data.email)}
                                    className={AUTH_TEXT_LINK}
                                >
                                    {t('register.log_in')}
                                </Link>
                            </p>
                        </>
                    )}

                    {step === 1 && (
                        <>
                            <button
                                type="button"
                                onClick={() => go(0)}
                                className={cn(
                                    'mb-5 flex h-9 max-w-full items-center gap-2 self-start rounded-full bg-foreground/[0.055] pr-3.5 pl-4 text-sm font-medium text-foreground transition-[scale] duration-150 ease-out active:scale-[0.97] dark:bg-white/[0.08]',
                                    AUTH_PRESSABLE,
                                )}
                            >
                                <span className="truncate">
                                    {form.data.email.trim()}
                                </span>
                                <Pencil
                                    className="size-3.5 shrink-0 text-muted-foreground"
                                    aria-hidden="true"
                                />
                                <span className="sr-only">
                                    {t('register.change_email')}
                                </span>
                            </button>
                            <h1 className={AUTH_HEADLINE}>
                                {t('register.password_headline')}
                            </h1>
                            <p className={AUTH_LEAD}>
                                {t('register.password_lead')}
                            </p>
                            <input
                                type="email"
                                autoComplete="username"
                                value={form.data.email}
                                readOnly
                                tabIndex={-1}
                                aria-hidden="true"
                                className="sr-only"
                            />
                            <div className="mt-8 grid gap-3">
                                <AuthPasswordInput
                                    ref={input}
                                    id="password"
                                    name="password"
                                    autoComplete="new-password"
                                    enterKeyHint="next"
                                    aria-label={t('register.password')}
                                    placeholder={t(
                                        'register.password_placeholder',
                                    )}
                                    value={form.data.password}
                                    onChange={(event) => {
                                        form.setData(
                                            'password',
                                            event.target.value,
                                        );
                                        form.clearErrors('password');
                                        setShowProblem(false);
                                    }}
                                    aria-invalid={
                                        !!form.errors.password ||
                                        (showProblem && !passwordReady)
                                    }
                                />
                                <div
                                    aria-hidden="true"
                                    className="flex gap-1 px-1"
                                >
                                    {Array.from(
                                        { length: PASSWORD_MIN },
                                        (_, index) => (
                                            <span
                                                key={index}
                                                className={cn(
                                                    'h-1.5 flex-1 rounded-full transition-colors duration-150 ease-out',
                                                    index >= length
                                                        ? 'bg-foreground/10'
                                                        : passwordReady
                                                          ? 'bg-emerald-500'
                                                          : 'bg-foreground',
                                                )}
                                            />
                                        ),
                                    )}
                                </div>
                                <p className="sr-only" aria-live="polite">
                                    {passwordReady
                                        ? t('register.password_ready')
                                        : t('register.password_progress', {
                                              length,
                                              minimum: PASSWORD_MIN,
                                          })}
                                </p>
                                <AuthFieldError
                                    message={
                                        form.errors.password ??
                                        (showProblem &&
                                            !passwordReady &&
                                            t('register.password_remaining', {
                                                remaining:
                                                    PASSWORD_MIN - length,
                                            }))
                                    }
                                />
                                <AuthSubmitButton ready={passwordReady}>
                                    {t('register.continue')}
                                </AuthSubmitButton>
                            </div>
                        </>
                    )}

                    {step === 2 && (
                        <>
                            <h1 className={AUTH_HEADLINE}>
                                {t('register.agreements_headline')}
                            </h1>
                            <p className={AUTH_LEAD}>
                                {t('register.agreements_lead')}
                            </p>
                            <div className="mt-7 grid gap-2.5">
                                {CONSENTS.map((consent) => (
                                    <label
                                        key={consent.key}
                                        className={cn(
                                            'flex items-start gap-4 rounded-3xl bg-foreground/[0.045] p-4 transition-[background-color,scale,box-shadow] duration-150 ease-out active:scale-[0.985] has-checked:bg-emerald-500/[0.12] dark:bg-white/[0.06] dark:has-checked:bg-emerald-400/[0.14]',
                                            ((showProblem &&
                                                consent.required &&
                                                !form.data[consent.key]) ||
                                                form.errors[consent.key]) &&
                                                'ring-2 ring-rose-500/70',
                                            AUTH_PRESSABLE,
                                        )}
                                    >
                                        <span className="min-w-0 flex-1">
                                            <span className="block text-[0.9375rem] font-bold text-foreground">
                                                {t(consent.title)}
                                            </span>
                                            <span className="mt-1 block text-[0.8125rem] leading-snug text-muted-foreground">
                                                {consent.key ===
                                                'accepted_disclaimer' ? (
                                                    <>
                                                        <span className="block">
                                                            {t(
                                                                'register.disclaimer_acceptance',
                                                            )}
                                                        </span>
                                                        <span className="mt-1 block">
                                                            {t(
                                                                'register.disclaimer_terms_prefix',
                                                            )}{' '}
                                                            <a
                                                                href={terms.url()}
                                                                target="_blank"
                                                                rel="noopener noreferrer"
                                                                className={
                                                                    LEGAL_LINK
                                                                }
                                                            >
                                                                {t(
                                                                    'register.terms_of_service',
                                                                )}
                                                            </a>{' '}
                                                            {t('register.and')}{' '}
                                                            <a
                                                                href={privacy.url()}
                                                                target="_blank"
                                                                rel="noopener noreferrer"
                                                                className={
                                                                    LEGAL_LINK
                                                                }
                                                            >
                                                                {t(
                                                                    'register.privacy_policy',
                                                                )}
                                                            </a>
                                                            .
                                                        </span>
                                                    </>
                                                ) : (
                                                    t(`register.${consent.key}`)
                                                )}
                                            </span>
                                        </span>
                                        <input
                                            id={consent.key}
                                            type="checkbox"
                                            name={consent.key}
                                            checked={form.data[consent.key]}
                                            onChange={(event) => {
                                                form.setData(
                                                    consent.key,
                                                    event.target.checked,
                                                );
                                                form.clearErrors(consent.key);
                                            }}
                                            className="peer sr-only"
                                        />
                                        <AuthCheck
                                            checked={form.data[consent.key]}
                                            className="size-7"
                                        />
                                    </label>
                                ))}
                            </div>
                            <div className="mt-auto grid gap-3 pt-8">
                                <AuthFieldError
                                    className="text-center"
                                    message={
                                        form.errors.accepted_disclaimer ??
                                        form.errors.health_data_consent ??
                                        (showProblem &&
                                            isMissingConsent &&
                                            t('register.agreements_missing'))
                                    }
                                />
                                <AuthSubmitButton
                                    processing={form.processing}
                                    ready={!isMissingConsent}
                                    data-test="register-user-button"
                                    data-umami-event="signup_form_submit"
                                    data-umami-event-method="email"
                                >
                                    {t('register.submit')}
                                </AuthSubmitButton>
                            </div>
                        </>
                    )}
                </div>
            </form>
        </AuthScreen>
    );
}
