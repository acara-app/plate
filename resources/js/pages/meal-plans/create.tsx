import AppLayout from '@/layouts/app-layout';
import mealPlans from '@/routes/meal-plans';
import { type BreadcrumbItem } from '@/types';
import { Form, Head } from '@inertiajs/react';
import { ChevronsUpDown, LoaderCircle } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useTranslation } from 'react-i18next';

interface UserProfile {
    calculated_diet_type?: string;
}

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

interface Props {
    dietTypes: { [key: string]: string };
    userProfile: UserProfile | null;
}
export default function CreateMealPlan({ dietTypes, userProfile }: Props) {
    const { t } = useTranslation('common');
    const [showOptions, setShowOptions] = useState(false);

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
                                        <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />
                                    )}
                                    {t('meal_plans.create.button')}
                                </Button>

                                <div className="space-y-4">
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setShowOptions(!showOptions)
                                        }
                                        className="mb-2 flex items-center gap-1 text-sm font-medium text-primary hover:text-primary/80"
                                    >
                                        {t('meal_plans.create.advanced')}
                                        <ChevronsUpDown className="h-4 w-4" />
                                    </button>

                                    {showOptions && (
                                        <>
                                            <div>
                                                <label
                                                    htmlFor="diet_type"
                                                    className="mb-2 block text-sm font-medium"
                                                >
                                                    {t(
                                                        'meal_plans.create.diet_type_label',
                                                    )}
                                                </label>
                                                <Select
                                                    name="diet_type"
                                                    defaultValue={
                                                        userProfile?.calculated_diet_type
                                                    }
                                                >
                                                    <SelectTrigger id="diet_type">
                                                        <SelectValue
                                                            placeholder={t(
                                                                'meal_plans.create.diet_type_placeholder',
                                                            )}
                                                        />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {Object.entries(
                                                            dietTypes,
                                                        ).map(([key]) => (
                                                            <SelectItem
                                                                key={key}
                                                                value={key}
                                                            >
                                                                {dietTypes[key]}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                                <p className="mt-2 text-xs text-muted-foreground">
                                                    {t(
                                                        'meal_plans.create.diet_type_hint',
                                                    )}
                                                </p>
                                            </div>
                                        </>
                                    )}
                                </div>
                            </>
                        )}
                    </Form>
                </div>
            </div>
        </AppLayout>
    );
}
