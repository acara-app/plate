import ShowSnapToTrackController from '@/actions/App/Http/Controllers/SnapToTrack/ShowSnapToTrackController';
import StoreSnapToTrackMealController from '@/actions/App/Http/Controllers/SnapToTrack/StoreSnapToTrackMealController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { formatLocalDatetime } from '@/lib/format-local-datetime';
import { type BreadcrumbItem } from '@/types';
import { Form, Head, Link } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

type AnalysisItem = {
    name: string;
    calories: number;
    protein: number;
    carbs: number;
    fat: number;
    portion: string;
    provenance: string;
};

type Analysis = {
    items: AnalysisItem[];
    total_calories: number;
    total_protein: number;
    total_carbs: number;
    total_fat: number;
    confidence: number;
};

type ReviewItem = {
    name: string;
    portion: string;
    calories: string;
    protein: string;
    carbs: string;
    fat: string;
    provenance: string;
};

type MacroField = 'calories' | 'protein' | 'carbs' | 'fat';

interface SnapToTrackReviewProps {
    state: 'restored' | 'unavailable';
    reason: string | null;
    analysis: Analysis | null;
    draftToken: string | null;
}

const numericValue = (value: string): number => {
    const parsed = Number.parseFloat(value);

    return Number.isFinite(parsed) && parsed >= 0 ? parsed : 0;
};

const emptyItem = (): ReviewItem => ({
    name: '',
    portion: '',
    calories: '',
    protein: '',
    carbs: '',
    fat: '',
    provenance: 'user',
});

export default function SnapToTrackReview({
    state,
    reason,
    analysis,
    draftToken,
}: SnapToTrackReviewProps) {
    const { t } = useTranslation('common');

    const [items, setItems] = useState<ReviewItem[]>(() =>
        (analysis?.items ?? []).map((item) => ({
            name: item.name,
            portion: item.portion ?? '',
            calories: String(item.calories),
            protein: String(item.protein),
            carbs: String(item.carbs),
            fat: String(item.fat),
            provenance: ['model', 'reference'].includes(item.provenance)
                ? item.provenance
                : 'user',
        })),
    );

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: t('snap_to_track.title'),
            href: ShowSnapToTrackController().url,
        },
        {
            title: t('snap_to_track.review.heading'),
            href: '#',
        },
    ];

    const updateItem = (
        index: number,
        field: keyof ReviewItem,
        value: string,
    ) => {
        setItems((current) =>
            current.map((item, itemIndex) =>
                itemIndex === index
                    ? { ...item, [field]: value, provenance: 'user' }
                    : item,
            ),
        );
    };

    const removeItem = (index: number) => {
        setItems((current) =>
            current.filter((_, itemIndex) => itemIndex !== index),
        );
    };

    const totals = items.reduce(
        (accumulator, item) => ({
            calories: accumulator.calories + numericValue(item.calories),
            protein: accumulator.protein + numericValue(item.protein),
            carbs: accumulator.carbs + numericValue(item.carbs),
            fat: accumulator.fat + numericValue(item.fat),
        }),
        { calories: 0, protein: 0, carbs: 0, fat: 0 },
    );

    if (state !== 'restored' || analysis === null || draftToken === null) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title={t('snap_to_track.title')} />
                <div className="mx-auto w-full max-w-2xl p-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>
                                {t('snap_to_track.unavailable.heading')}
                            </CardTitle>
                            <CardDescription>
                                {reason === 'expired'
                                    ? t('snap_to_track.unavailable.expired')
                                    : reason === 'consumed'
                                      ? t('snap_to_track.unavailable.consumed')
                                      : t('snap_to_track.unavailable.generic')}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-3 sm:flex-row">
                            <Button asChild>
                                <Link href={ShowSnapToTrackController().url}>
                                    {t(
                                        'snap_to_track.unavailable.analyze_again',
                                    )}
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </AppLayout>
        );
    }

    const macroFields: MacroField[] = ['calories', 'protein', 'carbs', 'fat'];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={t('snap_to_track.review.heading')} />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-semibold">
                        {t('snap_to_track.review.heading')}
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {t('snap_to_track.review.description')}
                    </p>
                    <p className="mt-2 text-xs text-muted-foreground">
                        {t('snap_to_track.review.ai_confidence', {
                            confidence: analysis.confidence,
                        })}
                    </p>
                </div>

                <Form
                    {...StoreSnapToTrackMealController.form(draftToken)}
                    disableWhileProcessing
                    className="flex flex-col gap-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <Card>
                                <CardHeader>
                                    <CardTitle>
                                        {t(
                                            'snap_to_track.review.items_heading',
                                        )}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="flex flex-col gap-6">
                                    {items.map((item, index) => (
                                        <div
                                            key={index}
                                            className="flex flex-col gap-3 border-b border-border pb-6 last:border-b-0 last:pb-0"
                                        >
                                            <input
                                                type="hidden"
                                                name={`items[${index}][provenance]`}
                                                value={item.provenance}
                                            />
                                            <div className="flex items-end gap-2">
                                                <div className="flex-1">
                                                    <Label
                                                        htmlFor={`item-name-${index}`}
                                                    >
                                                        {t(
                                                            'snap_to_track.review.item_name',
                                                        )}
                                                    </Label>
                                                    <Input
                                                        id={`item-name-${index}`}
                                                        name={`items[${index}][name]`}
                                                        value={item.name}
                                                        maxLength={100}
                                                        required
                                                        onChange={(event) =>
                                                            updateItem(
                                                                index,
                                                                'name',
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                        className="mt-1"
                                                    />
                                                </div>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    aria-label={t(
                                                        'snap_to_track.review.remove_item',
                                                    )}
                                                    onClick={() =>
                                                        removeItem(index)
                                                    }
                                                >
                                                    <Trash2 className="size-4" />
                                                </Button>
                                            </div>
                                            {item.provenance ===
                                                'reference' && (
                                                <p className="text-xs text-muted-foreground">
                                                    {t(
                                                        'snap_to_track.review.reference_badge',
                                                    )}
                                                </p>
                                            )}
                                            <div>
                                                <Label
                                                    htmlFor={`item-portion-${index}`}
                                                >
                                                    {t(
                                                        'snap_to_track.review.portion',
                                                    )}
                                                </Label>
                                                <Input
                                                    id={`item-portion-${index}`}
                                                    name={`items[${index}][portion]`}
                                                    value={item.portion}
                                                    maxLength={100}
                                                    onChange={(event) =>
                                                        updateItem(
                                                            index,
                                                            'portion',
                                                            event.target.value,
                                                        )
                                                    }
                                                    className="mt-1"
                                                />
                                            </div>
                                            <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                                {macroFields.map((field) => (
                                                    <div key={field}>
                                                        <Label
                                                            htmlFor={`item-${field}-${index}`}
                                                        >
                                                            {t(
                                                                `snap_to_track.review.${field}`,
                                                            )}
                                                        </Label>
                                                        <Input
                                                            id={`item-${field}-${index}`}
                                                            name={`items[${index}][${field}]`}
                                                            type="number"
                                                            inputMode="decimal"
                                                            min={0}
                                                            step={0.1}
                                                            value={item[field]}
                                                            onChange={(event) =>
                                                                updateItem(
                                                                    index,
                                                                    field,
                                                                    event.target
                                                                        .value,
                                                                )
                                                            }
                                                            className="mt-1"
                                                        />
                                                        <InputError
                                                            message={
                                                                errors[
                                                                    `items.${index}.${field}`
                                                                ]
                                                            }
                                                        />
                                                    </div>
                                                ))}
                                            </div>
                                            <InputError
                                                message={
                                                    errors[
                                                        `items.${index}.name`
                                                    ]
                                                }
                                            />
                                        </div>
                                    ))}

                                    <InputError message={errors.items} />

                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() =>
                                            setItems((current) => [
                                                ...current,
                                                emptyItem(),
                                            ])
                                        }
                                    >
                                        <Plus className="size-4" />
                                        {t('snap_to_track.review.add_item')}
                                    </Button>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle>
                                        {t('snap_to_track.review.totals')}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <dl className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                                        {macroFields.map((field) => (
                                            <div key={field}>
                                                <dt className="text-xs text-muted-foreground uppercase">
                                                    {t(
                                                        `snap_to_track.review.${field}`,
                                                    )}
                                                </dt>
                                                <dd className="text-xl font-semibold">
                                                    {Math.round(
                                                        totals[field] * 10,
                                                    ) / 10}
                                                </dd>
                                            </div>
                                        ))}
                                    </dl>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardContent className="flex flex-col gap-4 pt-6">
                                    <div>
                                        <Label htmlFor="measured-at">
                                            {t(
                                                'snap_to_track.review.meal_time',
                                            )}
                                        </Label>
                                        <Input
                                            id="measured-at"
                                            name="measured_at"
                                            type="datetime-local"
                                            defaultValue={formatLocalDatetime(
                                                new Date(),
                                            )}
                                            required
                                            className="mt-1"
                                        />
                                        <InputError
                                            message={errors.measured_at}
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="meal-notes">
                                            {t('snap_to_track.review.notes')}
                                        </Label>
                                        <Textarea
                                            id="meal-notes"
                                            name="notes"
                                            maxLength={500}
                                            placeholder={t(
                                                'snap_to_track.review.notes_placeholder',
                                            )}
                                            className="mt-1"
                                        />
                                        <InputError message={errors.notes} />
                                    </div>
                                </CardContent>
                            </Card>

                            <p className="text-xs text-muted-foreground">
                                {t('snap_to_track.review.disclaimer')}
                            </p>

                            <Button
                                type="submit"
                                disabled={processing || items.length === 0}
                            >
                                {processing
                                    ? t('snap_to_track.review.saving')
                                    : t('snap_to_track.review.save')}
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
