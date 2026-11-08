import UserPrivacyController from '@/actions/App/Http/Controllers/UserPrivacyController';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/react';
import { Sparkles } from 'lucide-react';
import { useTranslation } from 'react-i18next';

export function FoodRecognitionConsentCard() {
    const { t } = useTranslation('common');

    function answer(granted: boolean): void {
        router.patch(
            UserPrivacyController.update.url(),
            { improve_food_recognition: granted, source: 'meal_correction' },
            {
                preserveScroll: true,
                preserveState: true,
                only: ['askToImproveFoodRecognition'],
                optimistic: () => ({ askToImproveFoodRecognition: false }),
            },
        );
    }

    return (
        <div className="flex flex-col gap-3 rounded-md border border-primary/20 bg-primary/5 px-4 py-3 text-sm">
            <div className="flex items-start gap-2">
                <Sparkles className="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                <p className="text-muted-foreground">
                    {t('food_recognition_consent.text')}
                </p>
            </div>
            <div className="flex flex-wrap gap-2 pl-6">
                <Button size="sm" onClick={() => answer(true)}>
                    {t('food_recognition_consent.yes')}
                </Button>
                <Button size="sm" variant="ghost" onClick={() => answer(false)}>
                    {t('food_recognition_consent.no')}
                </Button>
            </div>
        </div>
    );
}
