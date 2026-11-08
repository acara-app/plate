import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { privacy, terms } from '@/routes';
import { Form, Head } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { useTranslation } from 'react-i18next';

export default function DisclaimerShow() {
    const { t } = useTranslation('auth');

    return (
        <AuthLayout
            title="Medical Disclaimer"
            description="Please review and accept before continuing"
        >
            <Head title="Medical Disclaimer" />
            <Form
                action="/disclaimer"
                method="post"
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/50 dark:text-amber-200">
                            <p className="mb-3 font-medium">
                                Acara Plate and its AI health coach Altani
                                provide wellness guidance, not medical advice.
                            </p>
                            <ul className="list-inside list-disc space-y-1.5 text-xs">
                                <li>
                                    Altani provides general wellness
                                    information, not professional medical advice
                                </li>
                                <li>
                                    It is not a substitute for professional
                                    medical advice, diagnosis, or treatment
                                </li>
                                <li>
                                    Always consult your doctor before making
                                    changes to medication or treatment
                                </li>
                                <li>
                                    In case of a medical emergency, call your
                                    local emergency services immediately
                                </li>
                            </ul>
                        </div>

                        <div className="grid gap-2">
                            <div className="flex items-start gap-2">
                                <Checkbox
                                    id="health_data_consent"
                                    name="health_data_consent"
                                    value="1"
                                    className="mt-0.5"
                                />
                                <Label
                                    htmlFor="health_data_consent"
                                    className="text-xs leading-snug font-normal text-muted-foreground"
                                >
                                    {t('register.health_data_consent')}
                                </Label>
                            </div>
                            <InputError message={errors.health_data_consent} />
                        </div>

                        <p className="text-xs leading-snug text-muted-foreground">
                            {t('register.continue_terms_prefix')}{' '}
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
                        </p>

                        <input type="hidden" name="accepted" value="1" />
                        <InputError message={errors.accepted} />

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={processing}
                        >
                            {processing && (
                                <LoaderCircle className="h-4 w-4 animate-spin" />
                            )}
                            I understand and accept
                        </Button>
                    </>
                )}
            </Form>
        </AuthLayout>
    );
}
