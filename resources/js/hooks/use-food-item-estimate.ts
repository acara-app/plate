import EstimateSnapToTrackItemController from '@/actions/App/Http/Controllers/SnapToTrack/EstimateSnapToTrackItemController';
import { useHttp } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { toast } from 'sonner';

export type FoodItemEstimate = {
    calories: number;
    protein: number;
    carbs: number;
    fat: number;
    fiber?: number | null;
    grams: number;
    portion: string;
};

type FoodItemEstimateRequest = {
    name: string;
    grams: number | null;
};

type FoodItemEstimateCallbacks = {
    onSuccess: (estimate: FoodItemEstimate) => void;
    onFailed: () => void;
};

const normalizedName = (value: string): string => value.trim().toLowerCase();

export const estimatedFields = (
    estimate: FoodItemEstimate,
    currentFiber: string,
) => ({
    portion: estimate.portion,
    grams: estimate.grams,
    calories: String(estimate.calories),
    protein: String(estimate.protein),
    carbs: String(estimate.carbs),
    fat: String(estimate.fat),
    fiber: estimate.fiber != null ? String(estimate.fiber) : currentFiber,
    provenance: 'user',
});

export const needsNutritionUpdate = (
    name: string,
    originalName?: string,
    estimatedFor?: string,
): boolean =>
    originalName !== undefined &&
    name.trim() !== '' &&
    normalizedName(name) !== normalizedName(originalName) &&
    normalizedName(name) !== normalizedName(estimatedFor ?? '');

export function useFoodItemEstimate() {
    const { t } = useTranslation('common');
    const http = useHttp<FoodItemEstimateRequest, FoodItemEstimate>(
        EstimateSnapToTrackItemController(),
        { name: '', grams: null },
    );
    const [estimatingIndex, setEstimatingIndex] = useState<number | null>(null);

    const estimate = (
        index: number,
        name: string,
        grams: number | null,
        callbacks: FoodItemEstimateCallbacks,
    ) => {
        setEstimatingIndex(index);
        http.transform(() => ({ name, grams }));
        http.submit({
            onSuccess: (response) => callbacks.onSuccess(response),
            onHttpException: (response) => {
                if (response.status === 402) {
                    toast.error(t('snap_to_track.review.rename.limit'));
                } else {
                    callbacks.onFailed();
                }

                return false;
            },
            onNetworkError: () => callbacks.onFailed(),
            onFinish: () => setEstimatingIndex(null),
        }).catch(() => undefined);
    };

    return { estimatingIndex, estimate };
}
