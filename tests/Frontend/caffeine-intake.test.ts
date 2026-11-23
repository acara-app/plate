import { describe, expect, test } from 'bun:test';
import {
    caffeineReferencesMg,
    calculateCaffeineIntake,
    compareCaffeineReference,
} from '../../resources/js/lib/caffeine-intake';

const defaultInputs = {
    mgPerServing: '80',
    servings: '1.5',
    otherOccasionMg: '',
    otherTodayMg: '',
};

describe('caffeine intake', () => {
    test('separates the occasion from the day and counts each source once', () => {
        expect(
            calculateCaffeineIntake({ ...defaultInputs, otherTodayMg: '60' }),
        ).toEqual({ status: 'ready', occasionMg: 120, todayMg: 180 });

        expect(
            calculateCaffeineIntake({
                ...defaultInputs,
                otherOccasionMg: '85',
                otherTodayMg: '60',
            }),
        ).toEqual({ status: 'ready', occasionMg: 205, todayMg: 265 });
    });

    test('does not treat a blank required amount or serving count as zero', () => {
        for (const field of ['mgPerServing', 'servings']) {
            expect(
                calculateCaffeineIntake({ ...defaultInputs, [field]: ' ' }),
            ).toEqual({ status: 'empty' });
        }

        expect(
            calculateCaffeineIntake({ ...defaultInputs, mgPerServing: '0' }),
        ).toEqual({ status: 'ready', occasionMg: 0, todayMg: 0 });
    });

    test('accepts decimal commas without letting floating point change threshold equality', () => {
        expect(
            calculateCaffeineIntake({
                ...defaultInputs,
                mgPerServing: '199,86',
                servings: '1',
                otherOccasionMg: ',14',
                otherTodayMg: '200',
            }),
        ).toEqual({ status: 'ready', occasionMg: 200, todayMg: 400 });
        expect(
            compareCaffeineReference(200, caffeineReferencesMg.occasion),
        ).toBe('at');
        expect(compareCaffeineReference(400, caffeineReferencesMg.daily)).toBe(
            'at',
        );
    });

    test('rejects invalid entries and arithmetic overflow rather than showing an old result', () => {
        for (const value of ['-1', 'Infinity', '1e309', '1.0001', '1.2.3']) {
            expect(
                calculateCaffeineIntake({
                    ...defaultInputs,
                    otherTodayMg: value,
                }),
            ).toEqual({ status: 'invalid', fields: ['otherTodayMg'] });
        }
        expect(
            calculateCaffeineIntake({
                ...defaultInputs,
                mgPerServing: '90000000000000',
                servings: '5',
            }),
        ).toEqual({ status: 'overflow' });
    });

    test('compares each total with its own general reference', () => {
        expect(
            compareCaffeineReference(199.9999, caffeineReferencesMg.occasion),
        ).toBe('below');
        expect(
            compareCaffeineReference(200.0001, caffeineReferencesMg.occasion),
        ).toBe('above');
        expect(compareCaffeineReference(265, caffeineReferencesMg.daily)).toBe(
            'below',
        );
        expect(compareCaffeineReference(401, caffeineReferencesMg.daily)).toBe(
            'above',
        );
    });
});
