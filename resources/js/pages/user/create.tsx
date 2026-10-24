import UserController from '@/actions/App/Http/Controllers/UserController';
import GoogleOAuthButton from '@/components/google-oauth-button';
import { login, privacy, terms } from '@/routes';
import { Form, Head } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { useTranslation } from 'react-i18next';

const trackSignupEmailBlur = () =>
    window.umami?.track('signup_field_blur_email');

export default function Register({
    snapIntent,
}: {
    snapIntent?: 'save' | 'upgrade' | 'preview' | 'scan' | null;
}) {
    const { t } = useTranslation('auth');
    const descriptions = {
        save: 'Create your free account to save this meal and correct its breakdown.',
        upgrade:
            'Create your account, verify your email, then review your selected plan before paying. Any meal you just scanned stays with you.',
        preview:
            'Create and verify your free account, then activate one photo scan per day for three days. No card or automatic subscription.',
        scan: 'Keep your meals together in your food log. Your remaining free scans follow your account.',
    };

    return (
        <AuthLayout
            title={
                snapIntent ? 'Continue with Snap to Track' : t('register.title')
            }
            description={snapIntent ? descriptions[snapIntent] : undefined}
            snap={!!snapIntent}
        >
            <Head title={t('register.page_title')} />
            <Form
                {...UserController.store.form()}
                resetOnSuccess={['password']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <GoogleOAuthButton />

                        <div className="relative">
                            <div className="absolute inset-0 flex items-center">
                                <span className="w-full border-t" />
                            </div>
                            <div className="relative flex justify-center text-xs uppercase">
                                <span className="bg-background px-2 text-muted-foreground">
                                    {t('register.or')}
                                </span>
                            </div>
                        </div>

                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="name">
                                    {t('register.name')}
                                </Label>
                                <Input
                                    id="name"
                                    className="min-h-11 text-base"
                                    type="text"
                                    required
                                    autoComplete="name"
                                    name="name"
                                    enterKeyHint="next"
                                    placeholder={t('register.name_placeholder')}
                                />
                                <InputError
                                    message={errors.name}
                                    className="mt-2"
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">
                                    {t('register.email')}
                                </Label>
                                <Input
                                    id="email"
                                    className="min-h-11 text-base"
                                    type="email"
                                    required
                                    autoComplete="email"
                                    inputMode="email"
                                    name="email"
                                    enterKeyHint="next"
                                    placeholder={t(
                                        'register.email_placeholder',
                                    )}
                                    onBlur={trackSignupEmailBlur}
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">
                                    {t('register.password')}
                                </Label>
                                <PasswordInput
                                    id="password"
                                    className="min-h-11 text-base"
                                    required
                                    autoComplete="new-password"
                                    name="password"
                                    enterKeyHint="send"
                                    placeholder={t(
                                        'register.password_placeholder',
                                    )}
                                />
                                <p className="text-xs text-muted-foreground">
                                    {t('register.password_hint')}
                                </p>
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-start gap-2">
                                    <Checkbox
                                        id="accepted_disclaimer"
                                        name="accepted_disclaimer"
                                        value="1"
                                        className="mt-0.5"
                                    />
                                    <Label
                                        htmlFor="accepted_disclaimer"
                                        className="text-xs leading-snug font-normal text-muted-foreground"
                                    >
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
                                                className="underline underline-offset-4 hover:text-foreground"
                                            >
                                                {t('register.terms_of_service')}
                                            </a>{' '}
                                            {t('register.and')}{' '}
                                            <a
                                                href={privacy.url()}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="underline underline-offset-4 hover:text-foreground"
                                            >
                                                {t('register.privacy_policy')}
                                            </a>
                                            .
                                        </span>
                                    </Label>
                                </div>
                                <InputError
                                    message={errors.accepted_disclaimer}
                                />
                            </div>

                            <Button
                                type="submit"
                                variant={snapIntent ? 'default' : 'outline'}
                                className={`mt-2 min-h-11 w-full ${snapIntent ? 'shadow-none before:hidden after:hidden hover:shadow-none' : ''}`}
                                data-test="register-user-button"
                                data-umami-event="signup_form_submit"
                                data-umami-event-method="email"
                            >
                                {processing && (
                                    <LoaderCircle className="h-4 w-4 animate-spin" />
                                )}
                                {t('register.submit')}
                            </Button>
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            {t('register.already_have_account')}{' '}
                            <TextLink href={login()}>
                                {t('register.log_in')}
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
