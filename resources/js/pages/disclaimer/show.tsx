import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';
import { Form, Head } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

export default function DisclaimerShow() {
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
                                provide wellness guidance, not medical
                                advice.
                            </p>
                            <ul className="list-inside list-disc space-y-1.5 text-xs">
                                <li>
                                    Altani provides general wellness
                                    information, not professional medical
                                    advice
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
