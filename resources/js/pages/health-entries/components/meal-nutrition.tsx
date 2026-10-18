import type { HealthEntry } from '@/types/diabetes';
import { useTranslation } from 'react-i18next';

type Nutrition = Pick<
    HealthEntry,
    'calories' | 'protein_grams' | 'carbs_grams' | 'fat_grams'
>;

export default function MealNutrition({ entry }: { entry: Nutrition }) {
    const { t, i18n } = useTranslation('common');
    const fields = [
        ['calories', entry.calories],
        ['protein', entry.protein_grams],
        ['carbs', entry.carbs_grams],
        ['fat', entry.fat_grams],
    ] as const;

    return (
        <dl className="flex flex-wrap gap-x-6 gap-y-2 tabular-nums">
            {fields.map(
                ([key, value]) =>
                    value !== null && (
                        <div key={key} className="flex flex-col gap-0.5">
                            <dt className="text-xs text-muted-foreground">
                                {t(`snap_to_track.review.${key}`)}
                            </dt>
                            <dd className="text-sm font-medium">
                                {new Intl.NumberFormat(i18n.resolvedLanguage, {
                                    maximumFractionDigits: 1,
                                }).format(value)}
                                {key === 'calories' && (
                                    <span className="ml-1 text-xs font-normal text-muted-foreground">
                                        {' '}
                                        kcal
                                    </span>
                                )}
                            </dd>
                        </div>
                    ),
            )}
        </dl>
    );
}
