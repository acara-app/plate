export const caffeineReferencesMg = { occasion: 200, daily: 400 } as const;

export const caffeineInputFields = [
    'mgPerServing',
    'servings',
    'otherOccasionMg',
    'otherTodayMg',
] as const;

type CaffeineField = (typeof caffeineInputFields)[number];

export type CaffeineIntakeInputs = Record<CaffeineField, string>;

type CaffeineIntakeResult =
    | { status: 'empty' }
    | { status: 'invalid'; fields: CaffeineField[] }
    | { status: 'overflow' }
    | { status: 'ready'; occasionMg: number; todayMg: number };

const inputScale = 100;
const totalScale = inputScale * inputScale;

// Convert decimals to integer hundredths. Blank is distinct from zero.
function readHundredths(value: string): number | null | undefined {
    const decimal = value.trim().replace(',', '.');

    if (decimal === '') {
        return undefined;
    }

    if (!/^(?:\d+(?:\.\d{0,2})?|\.\d{1,2})$/.test(decimal)) {
        return null;
    }

    const [whole, fraction = ''] = decimal.split('.');
    const hundredths =
        Number(whole) * inputScale + Number(fraction.padEnd(2, '0'));

    return Number.isSafeInteger(hundredths) ? hundredths : null;
}

export function calculateCaffeineIntake(
    inputs: CaffeineIntakeInputs,
): CaffeineIntakeResult {
    const amounts = {
        mgPerServing: readHundredths(inputs.mgPerServing),
        servings: readHundredths(inputs.servings),
        otherOccasionMg: readHundredths(inputs.otherOccasionMg),
        otherTodayMg: readHundredths(inputs.otherTodayMg),
    };
    const { mgPerServing, servings, otherOccasionMg, otherTodayMg } = amounts;
    const fields = caffeineInputFields.filter(
        (field) => amounts[field] === null,
    );

    if (fields.length > 0) {
        return { status: 'invalid', fields };
    }

    if (mgPerServing == null || servings == null) {
        return { status: 'empty' };
    }

    const occasion =
        mgPerServing * servings + (otherOccasionMg ?? 0) * inputScale;
    const today = occasion + (otherTodayMg ?? 0) * inputScale;

    if (!Number.isSafeInteger(occasion) || !Number.isSafeInteger(today)) {
        return { status: 'overflow' };
    }

    return {
        status: 'ready',
        occasionMg: occasion / totalScale,
        todayMg: today / totalScale,
    };
}

export function compareCaffeineReference(
    amountMg: number,
    referenceMg: number,
) {
    if (amountMg === referenceMg) {
        return 'at';
    }

    return amountMg < referenceMg ? 'below' : 'above';
}
