import AppLayout from '@/layouts/app-layout';
import mealPlans from '@/routes/meal-plans';
import { type BreadcrumbItem } from '@/types';
import { Form, Head } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { useTranslation } from 'react-i18next';

const getBreadcrumbs = (t: (key: string) => string): BreadcrumbItem[] => [
    {
        title: t('meal_plans.title'),
        href: mealPlans.index().url,
    },
    {
        title: 'Create',
        href: mealPlans.create().url,
    },
];

export default function CreateMealPlan() {
    const { t } = useTranslation('common');

    return (
        <AppLayout breadcrumbs={getBreadcrumbs(t)}>
            <Head title="Create Your Meal Plan" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4 md:p-6">
                <div className="mx-auto w-full max-w-2xl rounded-lg border bg-card p-6 shadow-sm">
                    <Form
                        {...mealPlans.store.form()}
                        disableWhileProcessing
                        className="space-y-6"
                    >
                        {({ processing }) => (
                            <>
                                <div className="space-y-4">
                                    <div>
                                        <label
                                            htmlFor="prompt"
                                            className="mb-2 block text-sm font-medium"
                                        >
                                            {t(
                                                'meal_plans.create.prompt_label',
                                            )}
                                        </label>
                                        <Textarea
                                            id="prompt"
                                            name="prompt"
                                            placeholder={t(
                                                'meal_plans.create.prompt_placeholder',
                                            )}
                                            rows={4}
                                            className="resize-none"
                                        />
                                        <p className="mt-2 text-xs text-muted-foreground">
                                            {t('meal_plans.create.prompt_hint')}
                                        </p>
                                    </div>

                                    <div className="rounded-lg border border-primary/20 bg-primary/5 p-4">
                                        <p className="text-sm">
                                            {t('meal_plans.create.note')}
                                        </p>
                                    </div>
                                </div>

                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full"
                                >
                                    {processing && (
                                        <LoaderCircle className="h-4 w-4 animate-spin" />
                                    )}
                                    {t('meal_plans.create.button')}
                                </Button>
                            </>
                        )}
                    </Form>
                </div>
            </div>
        </AppLayout>
    );
}
