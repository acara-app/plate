import {
    caffeineInputFields,
    caffeineReferencesMg,
    calculateCaffeineIntake,
    compareCaffeineReference,
    type CaffeineIntakeInputs,
} from '@/lib/caffeine-intake';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

const labels = {
    mgPerServing: 'intake_per_serving',
    servings: 'intake_servings',
    otherOccasionMg: 'intake_other_occasion',
    otherTodayMg: 'intake_other_today',
} as const;

const hints = {
    mgPerServing: 'intake_per_serving_hint',
    servings: 'intake_servings_hint',
    otherOccasionMg: 'intake_other_occasion_hint',
    otherTodayMg: 'intake_other_today_hint',
} as const;

export default function CaffeineIntakeCalculator({
    locale,
}: {
    locale: string;
}) {
    const { t } = useTranslation('caffeine');
    const [inputs, setInputs] = useState<CaffeineIntakeInputs>({
        mgPerServing: '',
        servings: '1',
        otherOccasionMg: '',
        otherTodayMg: '',
    });
    const result = calculateCaffeineIntake(inputs);
    const format = new Intl.NumberFormat(locale, { maximumFractionDigits: 4 });

    function compare(
        amount: number,
        reference: number,
        timeframe: 'occasion' | 'daily',
    ) {
        const relation = compareCaffeineReference(amount, reference);

        return t('intake_' + timeframe + '_' + relation, {
            reference: format.format(reference),
        });
    }

    return (
        <section
            aria-labelledby="caffeine-intake"
            className="mx-auto mt-8 max-w-7xl lg:px-8"
        >
            <div className="grid gap-8 border border-[#D9CFBC] bg-[#EBE2D0] p-6 md:grid-cols-2 md:p-8">
                <div>
                    <h2 id="caffeine-intake" className="text-2xl font-bold">
                        {t('intake_title')}
                    </h2>
                    <p className="mt-2 text-sm leading-relaxed text-[#3D3833]">
                        {t('intake_intro')}
                    </p>
                    <div className="mt-5 grid gap-4 sm:grid-cols-2">
                        {caffeineInputFields.map((field) => {
                            const id = 'caffeine-intake-' + field;
                            const hintId = id + '-hint';
                            const errorId = id + '-error';
                            const invalid =
                                result.status === 'invalid' &&
                                result.fields.includes(field);

                            return (
                                <div key={field}>
                                    <label
                                        htmlFor={id}
                                        className="block text-sm font-semibold"
                                    >
                                        {t(labels[field])}
                                    </label>
                                    <input
                                        id={id}
                                        inputMode="decimal"
                                        autoComplete="off"
                                        maxLength={24}
                                        aria-describedby={
                                            invalid
                                                ? hintId + ' ' + errorId
                                                : hintId
                                        }
                                        aria-invalid={invalid || undefined}
                                        value={inputs[field]}
                                        onChange={(event) =>
                                            setInputs({
                                                ...inputs,
                                                [field]: event.target.value,
                                            })
                                        }
                                        className="mt-1 w-full border border-[#6E665C] bg-[#F2EBDD] px-3 py-2 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#1A1814] aria-invalid:border-[#8E2E19]"
                                    />
                                    <p
                                        id={hintId}
                                        className="mt-1 text-sm text-[#3D3833]"
                                    >
                                        {t(hints[field])}
                                    </p>
                                    {invalid && (
                                        <p
                                            id={errorId}
                                            className="mt-1 text-sm text-[#8E2E19]"
                                        >
                                            {t('intake_invalid_input')}
                                        </p>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                    <p className="mt-5 text-sm text-[#3D3833]">
                        {t('intake_local_only')}
                    </p>
                </div>

                <div className="border-t border-[#D9CFBC] pt-6 md:border-t-0 md:border-l md:pt-0 md:pl-8">
                    <h3 className="text-xl font-bold">
                        {t('intake_results_title')}
                    </h3>
                    <p className="mt-2 text-sm leading-relaxed text-[#3D3833]">
                        {t('intake_comparison_scope')}
                    </p>
                    <div aria-live="polite" aria-atomic="true" className="mt-5">
                        {result.status === 'ready' ? (
                            <dl className="grid gap-4">
                                <div>
                                    <dt className="text-sm font-semibold">
                                        {t('intake_occasion_total')}
                                    </dt>
                                    <dd className="text-3xl font-bold tabular-nums">
                                        {t('intake_milligrams', {
                                            amount: format.format(
                                                result.occasionMg,
                                            ),
                                        })}
                                    </dd>
                                    <dd className="mt-1 text-sm">
                                        {compare(
                                            result.occasionMg,
                                            caffeineReferencesMg.occasion,
                                            'occasion',
                                        )}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-semibold">
                                        {t('intake_day_total')}
                                    </dt>
                                    <dd className="text-3xl font-bold tabular-nums">
                                        {t('intake_milligrams', {
                                            amount: format.format(
                                                result.todayMg,
                                            ),
                                        })}
                                    </dd>
                                    <dd className="mt-1 text-sm">
                                        {compare(
                                            result.todayMg,
                                            caffeineReferencesMg.daily,
                                            'daily',
                                        )}
                                    </dd>
                                </div>
                            </dl>
                        ) : (
                            <p className="text-sm">
                                {t(
                                    result.status === 'overflow'
                                        ? 'intake_overflow'
                                        : result.status === 'invalid'
                                          ? 'intake_check_inputs'
                                          : 'intake_empty',
                                )}
                            </p>
                        )}
                    </div>
                </div>
            </div>
        </section>
    );
}
