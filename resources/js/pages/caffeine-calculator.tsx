import { plan as planRoute } from '@/actions/App/Http/Controllers/CaffeineCalculatorController';
import { CaffeineGuidanceRenderer } from '@/components/caffeine-guidance/render';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { Head, useHttp, usePage } from '@inertiajs/react';
import type { Spec } from '@json-render/core';
import {
    Activity,
    BookOpen,
    Calendar,
    ChevronRight,
    ExternalLink,
    LoaderCircle,
    MessageSquareText,
    Ruler,
    Sparkles,
    Weight,
} from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { useTranslation } from 'react-i18next';

interface AssessmentResponse {
    summary: string;
    limit: {
        heightCm: number;
        weightKg: number;
        age: number;
        sex: string;
        sensitivity: string;
        limitMg: number | null;
        status: string;
    };
    spec: Spec;
}

type ConditionKey =
    | 'pregnancy'
    | 'breastfeeding'
    | 'trying_to_conceive'
    | 'heart_condition'
    | 'anxiety'
    | 'gerd'
    | 'insomnia'
    | 'medication';

interface AssessmentFormData {
    height_cm: string;
    height_ft: string;
    height_in: string;
    weight_kg: string;
    weight_lb: string;
    age: string;
    sex: 'male' | 'female' | 'decline';
    sensitivity: 'low' | 'normal' | 'high';
    context: string;
    unit_system: 'metric' | 'imperial';
    locale: string;
}

const CONDITION_OPTIONS: Array<{ value: ConditionKey; labelKey: string }> = [
    { value: 'pregnancy', labelKey: 'condition_pregnancy' },
    { value: 'breastfeeding', labelKey: 'condition_breastfeeding' },
    { value: 'trying_to_conceive', labelKey: 'condition_trying_to_conceive' },
    { value: 'heart_condition', labelKey: 'condition_heart_condition' },
    { value: 'medication', labelKey: 'condition_medication' },
    { value: 'anxiety', labelKey: 'condition_anxiety' },
    { value: 'insomnia', labelKey: 'condition_insomnia' },
    { value: 'gerd', labelKey: 'condition_gerd' },
];

interface CaffeineCalculatorPageProps {
    seo: {
        appName: string;
        appUrl: string;
        canonicalUrl: string;
        toolsUrl: string;
        imageUrl: string;
        hreflangLinks: Array<{
            locale: string;
            url: string;
        }>;
        xDefaultUrl: string;
    };
    locale: string;
    [key: string]: unknown;
}

const SENSITIVITY_OPTIONS: Array<{
    value: AssessmentFormData['sensitivity'];
    labelKey: string;
    detailKey: string;
}> = [
    {
        value: 'low',
        labelKey: 'sensitivity_low',
        detailKey: 'sensitivity_low_detail',
    },
    {
        value: 'normal',
        labelKey: 'sensitivity_normal',
        detailKey: 'sensitivity_normal_detail',
    },
    {
        value: 'high',
        labelKey: 'sensitivity_high',
        detailKey: 'sensitivity_high_detail',
    },
];

const SEX_OPTIONS: Array<{
    value: AssessmentFormData['sex'];
    labelKey: string;
}> = [
    { value: 'male', labelKey: 'sex_male' },
    { value: 'female', labelKey: 'sex_female' },
    { value: 'decline', labelKey: 'sex_decline' },
];

const SOURCE_LINKS = [
    {
        labelKey: 'seo_source_fda',
        url: 'https://www.fda.gov/consumers/consumer-updates/spilling-beans-how-much-caffeine-too-much',
    },
    {
        labelKey: 'seo_source_efsa',
        url: 'https://www.efsa.europa.eu/en/topics/topic/caffeine',
    },
    {
        labelKey: 'seo_source_acog',
        url: 'https://www.acog.org/womens-health/experts-and-stories/ask-acog/how-much-coffee-can-i-drink-while-pregnant',
    },
] as const;

const FAQ_KEYS = [
    {
        questionKey: 'seo_faq_safe_amount_question',
        answerKey: 'seo_faq_safe_amount_answer',
    },
    {
        questionKey: 'seo_faq_estimate_question',
        answerKey: 'seo_faq_estimate_answer',
    },
    {
        questionKey: 'seo_faq_weight_question',
        answerKey: 'seo_faq_weight_answer',
    },
    {
        questionKey: 'seo_faq_medical_advice_question',
        answerKey: 'seo_faq_medical_advice_answer',
    },
    {
        questionKey: 'seo_faq_sensitivity_question',
        answerKey: 'seo_faq_sensitivity_answer',
    },
] as const;

function cmToFtIn(cm: number): { ft: number; inch: number } {
    const totalInches = Math.round(cm / 2.54);
    const ft = Math.floor(totalInches / 12);
    const inch = totalInches % 12;
    return { ft, inch };
}

function ftInToCm(ft: number, inch: number): number {
    return Math.round(ft * 30.48 + inch * 2.54);
}

function kgToLb(kg: number): number {
    return Math.round(kg * 2.20462);
}

function lbToKg(lb: number): number {
    return Math.round((lb / 2.20462) * 10) / 10;
}

export default function CaffeineCalculator() {
    const { t } = useTranslation('caffeine');
    const { seo, locale } = usePage<CaffeineCalculatorPageProps>().props;
    const form = useHttp<AssessmentFormData, AssessmentResponse>(planRoute(), {
        height_cm: '',
        height_ft: '',
        height_in: '',
        weight_kg: '',
        weight_lb: '',
        age: '',
        sex: 'decline',
        sensitivity: 'normal',
        context: '',
        unit_system: 'metric',
        locale: locale ?? 'en',
    });

    const [unitSystem, setUnitSystem] = useState<'metric' | 'imperial'>(
        'metric',
    );
    const [selectedConditions, setSelectedConditions] = useState<
        ConditionKey[]
    >([]);

    function toggleCondition(condition: ConditionKey): void {
        setSelectedConditions((current) =>
            current.includes(condition)
                ? current.filter((c) => c !== condition)
                : [...current, condition],
        );
    }

    function toggleUnitSystem(): void {
        const newSystem = unitSystem === 'metric' ? 'imperial' : 'metric';
        setUnitSystem(newSystem);
        form.setData('unit_system', newSystem);

        if (newSystem === 'imperial') {
            if (form.data.height_cm) {
                const { ft, inch } = cmToFtIn(Number(form.data.height_cm));
                form.setData('height_ft', String(ft));
                form.setData('height_in', String(inch));
            }
            if (form.data.weight_kg) {
                form.setData(
                    'weight_lb',
                    String(kgToLb(Number(form.data.weight_kg))),
                );
            }
        } else {
            if (form.data.height_ft || form.data.height_in) {
                const cm = ftInToCm(
                    Number(form.data.height_ft) || 0,
                    Number(form.data.height_in) || 0,
                );
                form.setData('height_cm', String(cm));
            }
            if (form.data.weight_lb) {
                form.setData(
                    'weight_kg',
                    String(lbToKg(Number(form.data.weight_lb))),
                );
            }
        }
    }

    function onSubmit(event: FormEvent): void {
        event.preventDefault();
        if (form.processing) {
            return;
        }

        let heightCm = Number(form.data.height_cm);
        let weightKg = Number(form.data.weight_kg);

        if (unitSystem === 'imperial') {
            heightCm = ftInToCm(
                Number(form.data.height_ft) || 0,
                Number(form.data.height_in) || 0,
            );
            weightKg = lbToKg(Number(form.data.weight_lb) || 0);
        }

        if (!heightCm || !weightKg || !form.data.age) {
            return;
        }

        form.transform((data) => ({
            height_cm: heightCm,
            weight_kg: weightKg,
            age: Number(data.age),
            sex: data.sex,
            sensitivity: data.sensitivity,
            context: data.context.trim() === '' ? null : data.context.trim(),
            conditions: selectedConditions,
            locale: data.locale,
        }));

        void form.submit();
    }

    const canSubmit =
        (unitSystem === 'metric'
            ? form.data.height_cm.trim() !== '' &&
              form.data.weight_kg.trim() !== ''
            : form.data.height_ft.trim() !== '' &&
              form.data.weight_lb.trim() !== '') && form.data.age.trim() !== '';

    const conditionsError = (form.errors as Record<string, string | undefined>)[
        'conditions.0'
    ];
    const pageTitle = t('page_title');
    const metaDescription = t('meta_description');
    const faqItems = createFaqItems(t);
    const openGraphLocale = locale === 'mn' ? 'mn_MN' : 'en_US';
    const alternateOpenGraphLocale = locale === 'mn' ? 'en_US' : 'mn_MN';

    return (
        <>
            <Head title={pageTitle}>
                <meta
                    head-key="description"
                    name="description"
                    content={metaDescription}
                />
                <meta
                    head-key="keywords"
                    name="keywords"
                    content={t('meta_keywords')}
                />
                <meta
                    head-key="robots"
                    name="robots"
                    content="index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1"
                />
                {seo.hreflangLinks.map((link) => (
                    <link
                        key={link.locale}
                        head-key={`alternate-${link.locale}`}
                        rel="alternate"
                        hrefLang={link.locale}
                        href={link.url}
                    />
                ))}
                <link
                    head-key="alternate-x-default"
                    rel="alternate"
                    hrefLang="x-default"
                    href={seo.xDefaultUrl}
                />
                <meta
                    head-key="og-title"
                    property="og:title"
                    content={pageTitle}
                />
                <meta
                    head-key="og-description"
                    property="og:description"
                    content={metaDescription}
                />
                <meta
                    head-key="og-url"
                    property="og:url"
                    content={seo.canonicalUrl}
                />
                <meta head-key="og-type" property="og:type" content="website" />
                <meta
                    head-key="og-site-name"
                    property="og:site_name"
                    content={seo.appName}
                />
                <meta
                    head-key="og-locale"
                    property="og:locale"
                    content={openGraphLocale}
                />
                <meta
                    head-key="og-locale-alternate"
                    property="og:locale:alternate"
                    content={alternateOpenGraphLocale}
                />
                <meta
                    head-key="og-image"
                    property="og:image"
                    content={seo.imageUrl}
                />
                <meta
                    head-key="twitter-card"
                    name="twitter:card"
                    content="summary_large_image"
                />
                <meta
                    head-key="twitter-title"
                    name="twitter:title"
                    content={pageTitle}
                />
                <meta
                    head-key="twitter-description"
                    name="twitter:description"
                    content={metaDescription}
                />
                <meta
                    head-key="twitter-image"
                    name="twitter:image"
                    content={seo.imageUrl}
                />
                <script
                    head-key="caffeine-calculator-structured-data"
                    type="application/ld+json"
                    dangerouslySetInnerHTML={{
                        __html: toJsonLd(
                            createStructuredDataSchema(
                                seo,
                                locale ?? 'en',
                                pageTitle,
                                metaDescription,
                                faqItems,
                                t,
                            ),
                        ),
                    }}
                />
            </Head>
            <style>{`
                @keyframes caffeine-result-in {
                    from { opacity: 0; transform: translateY(8px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                [data-caffeine-result] {
                    animation: caffeine-result-in 220ms ease-out both;
                }
                @media (prefers-reduced-motion: reduce) {
                    [data-caffeine-result] { animation: none; }
                }
            `}</style>

            <div className="min-h-screen bg-gray-50 px-4 py-6 text-gray-900 md:py-10 dark:bg-slate-900 dark:text-gray-50">
                <Breadcrumbs seo={seo} />

                <main className="mx-auto grid max-w-7xl gap-6 lg:grid-cols-[0.92fr_1.08fr] lg:px-8">
                    <section className="rounded-xl border border-gray-200 bg-white p-6 shadow-none dark:border-slate-700 dark:bg-slate-800">
                        <div className="flex items-start justify-between gap-4">
                            <div className="flex items-center gap-3">
                                <div>
                                    <p className="text-sm font-semibold tracking-wide text-emerald-700 uppercase dark:text-emerald-300">
                                        {t('tagline')}
                                    </p>
                                    <h1 className="text-3xl leading-tight font-bold tracking-tight md:text-4xl">
                                        {t('heading')}
                                    </h1>
                                    <p className="mt-2 max-w-xl text-base leading-relaxed text-gray-600 dark:text-slate-400">
                                        {t('subheading')}
                                    </p>
                                </div>
                            </div>
                            <button
                                type="button"
                                onClick={toggleUnitSystem}
                                className="shrink-0 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-900 transition duration-150 ease-[cubic-bezier(0.4,0,0.2,1)] hover:border-emerald-500 hover:bg-emerald-50 dark:border-slate-700 dark:bg-slate-800 dark:text-gray-50 dark:hover:border-emerald-500 dark:hover:bg-emerald-900/30"
                            >
                                {unitSystem === 'metric'
                                    ? t('unit_toggle_metric')
                                    : t('unit_toggle_imperial')}
                            </button>
                        </div>

                        <form onSubmit={onSubmit} className="mt-6 space-y-6">
                            <div>
                                <label
                                    htmlFor="height_cm"
                                    className="text-sm font-semibold text-gray-900 dark:text-gray-50"
                                >
                                    {t('height')}
                                </label>
                                {unitSystem === 'metric' ? (
                                    <div className="relative mt-2">
                                        <Ruler
                                            className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-gray-400"
                                            aria-hidden="true"
                                        />
                                        <Input
                                            id="height_cm"
                                            type="number"
                                            inputMode="numeric"
                                            min={90}
                                            max={230}
                                            value={form.data.height_cm}
                                            onChange={(event) =>
                                                form.setData(
                                                    'height_cm',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder={t(
                                                'height_cm_placeholder',
                                            )}
                                            className="h-11 rounded-lg border-gray-200 bg-white pr-14 pl-10 text-base focus-visible:border-emerald-500 focus-visible:ring-emerald-500/15 dark:border-slate-700 dark:bg-slate-900"
                                            aria-invalid={
                                                form.errors.height_cm
                                                    ? 'true'
                                                    : undefined
                                            }
                                        />
                                        <span className="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-sm font-semibold text-gray-500 dark:text-slate-400">
                                            cm
                                        </span>
                                    </div>
                                ) : (
                                    <div className="mt-2 flex gap-2">
                                        <div className="relative flex-1">
                                            <Ruler
                                                className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-gray-400"
                                                aria-hidden="true"
                                            />
                                            <Input
                                                id="height_ft"
                                                type="number"
                                                inputMode="numeric"
                                                min={2}
                                                max={7}
                                                value={form.data.height_ft}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'height_ft',
                                                        event.target.value,
                                                    )
                                                }
                                                placeholder={t(
                                                    'height_ft_placeholder',
                                                )}
                                                className="h-11 rounded-lg border-gray-200 bg-white pr-14 pl-10 text-base focus-visible:border-emerald-500 focus-visible:ring-emerald-500/15 dark:border-slate-700 dark:bg-slate-900"
                                            />
                                            <span className="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-sm font-semibold text-gray-500 dark:text-slate-400">
                                                ft
                                            </span>
                                        </div>
                                        <div className="relative flex-1">
                                            <Input
                                                id="height_in"
                                                type="number"
                                                inputMode="numeric"
                                                min={0}
                                                max={11}
                                                value={form.data.height_in}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'height_in',
                                                        event.target.value,
                                                    )
                                                }
                                                placeholder={t(
                                                    'height_in_placeholder',
                                                )}
                                                className="h-11 rounded-lg border-gray-200 bg-white pr-14 text-base focus-visible:border-emerald-500 focus-visible:ring-emerald-500/15 dark:border-slate-700 dark:bg-slate-900"
                                            />
                                            <span className="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-sm font-semibold text-gray-500 dark:text-slate-400">
                                                in
                                            </span>
                                        </div>
                                    </div>
                                )}
                                {form.errors.height_cm && (
                                    <p className="mt-2 text-sm text-red-600 dark:text-red-400">
                                        {form.errors.height_cm}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label
                                    htmlFor="weight"
                                    className="text-sm font-semibold text-gray-900 dark:text-gray-50"
                                >
                                    {t('weight')}
                                </label>
                                {unitSystem === 'metric' ? (
                                    <div className="relative mt-2">
                                        <Weight
                                            className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-gray-400"
                                            aria-hidden="true"
                                        />
                                        <Input
                                            id="weight_kg"
                                            type="number"
                                            inputMode="numeric"
                                            min={30}
                                            max={300}
                                            value={form.data.weight_kg}
                                            onChange={(event) =>
                                                form.setData(
                                                    'weight_kg',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder={t(
                                                'weight_kg_placeholder',
                                            )}
                                            className="h-11 rounded-lg border-gray-200 bg-white pr-14 pl-10 text-base focus-visible:border-emerald-500 focus-visible:ring-emerald-500/15 dark:border-slate-700 dark:bg-slate-900"
                                            aria-invalid={
                                                form.errors.weight_kg
                                                    ? 'true'
                                                    : undefined
                                            }
                                        />
                                        <span className="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-sm font-semibold text-gray-500 dark:text-slate-400">
                                            kg
                                        </span>
                                    </div>
                                ) : (
                                    <div className="relative mt-2">
                                        <Weight
                                            className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-gray-400"
                                            aria-hidden="true"
                                        />
                                        <Input
                                            id="weight_lb"
                                            type="number"
                                            inputMode="numeric"
                                            min={66}
                                            max={660}
                                            value={form.data.weight_lb}
                                            onChange={(event) =>
                                                form.setData(
                                                    'weight_lb',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder={t(
                                                'weight_lb_placeholder',
                                            )}
                                            className="h-11 rounded-lg border-gray-200 bg-white pr-14 pl-10 text-base focus-visible:border-emerald-500 focus-visible:ring-emerald-500/15 dark:border-slate-700 dark:bg-slate-900"
                                            aria-invalid={
                                                form.errors.weight_kg
                                                    ? 'true'
                                                    : undefined
                                            }
                                        />
                                        <span className="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-sm font-semibold text-gray-500 dark:text-slate-400">
                                            lb
                                        </span>
                                    </div>
                                )}
                                {form.errors.weight_kg && (
                                    <p className="mt-2 text-sm text-red-600 dark:text-red-400">
                                        {form.errors.weight_kg}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label
                                    htmlFor="age"
                                    className="text-sm font-semibold text-gray-900 dark:text-gray-50"
                                >
                                    {t('age')}
                                </label>
                                <div className="relative mt-2">
                                    <Calendar
                                        className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-gray-400"
                                        aria-hidden="true"
                                    />
                                    <Input
                                        id="age"
                                        type="number"
                                        inputMode="numeric"
                                        min={13}
                                        max={120}
                                        value={form.data.age}
                                        onChange={(event) =>
                                            form.setData(
                                                'age',
                                                event.target.value,
                                            )
                                        }
                                        placeholder={t('age_placeholder')}
                                        className="h-11 rounded-lg border-gray-200 bg-white pr-14 pl-10 text-base focus-visible:border-emerald-500 focus-visible:ring-emerald-500/15 dark:border-slate-700 dark:bg-slate-900"
                                        aria-invalid={
                                            form.errors.age ? 'true' : undefined
                                        }
                                    />
                                    <span className="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-sm font-semibold text-gray-500 dark:text-slate-400">
                                        {t('age_unit')}
                                    </span>
                                </div>
                                {form.errors.age && (
                                    <p className="mt-2 text-sm text-red-600 dark:text-red-400">
                                        {form.errors.age}
                                    </p>
                                )}
                            </div>

                            <div>
                                <div className="flex items-center justify-between gap-3">
                                    <label className="text-sm font-semibold text-gray-900 dark:text-gray-50">
                                        {t('sex')}
                                    </label>
                                    {form.errors.sex && (
                                        <p className="text-sm text-red-600 dark:text-red-400">
                                            {form.errors.sex}
                                        </p>
                                    )}
                                </div>
                                <p className="mt-1 text-xs text-gray-500 dark:text-slate-400">
                                    {t('sex_description')}
                                </p>
                                <div
                                    className="mt-2 grid grid-cols-3 gap-2"
                                    role="radiogroup"
                                    aria-label={t('sex')}
                                >
                                    {SEX_OPTIONS.map((option) => {
                                        const selected =
                                            form.data.sex === option.value;

                                        return (
                                            <button
                                                key={option.value}
                                                type="button"
                                                role="radio"
                                                aria-checked={selected}
                                                onClick={() =>
                                                    form.setData(
                                                        'sex',
                                                        option.value,
                                                    )
                                                }
                                                className={cn(
                                                    'rounded-xl border px-3 py-3 text-left transition duration-150 ease-[cubic-bezier(0.4,0,0.2,1)] focus:ring-2 focus:ring-emerald-500/40 focus:outline-none',
                                                    selected
                                                        ? 'border-emerald-500 bg-emerald-50 text-gray-900 dark:border-emerald-500 dark:bg-emerald-900/30 dark:text-gray-50'
                                                        : 'border-gray-200 bg-gray-50 text-gray-700 hover:border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-slate-600',
                                                )}
                                            >
                                                <span className="block text-sm font-semibold">
                                                    {t(option.labelKey)}
                                                </span>
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>

                            <div>
                                <div className="flex items-center justify-between gap-3">
                                    <label className="text-sm font-semibold text-gray-900 dark:text-gray-50">
                                        {t('sensitivity')}
                                    </label>
                                    {form.errors.sensitivity && (
                                        <p className="text-sm text-red-600 dark:text-red-400">
                                            {form.errors.sensitivity}
                                        </p>
                                    )}
                                </div>
                                <div
                                    className="mt-2 grid grid-cols-3 gap-2"
                                    role="radiogroup"
                                    aria-label={t('sensitivity')}
                                >
                                    {SENSITIVITY_OPTIONS.map((option) => {
                                        const selected =
                                            form.data.sensitivity ===
                                            option.value;

                                        return (
                                            <button
                                                key={option.value}
                                                type="button"
                                                role="radio"
                                                aria-checked={selected}
                                                onClick={() =>
                                                    form.setData(
                                                        'sensitivity',
                                                        option.value,
                                                    )
                                                }
                                                className={cn(
                                                    'rounded-xl border px-3 py-3 text-left transition duration-150 ease-[cubic-bezier(0.4,0,0.2,1)] focus:ring-2 focus:ring-emerald-500/40 focus:outline-none',
                                                    selected
                                                        ? 'border-emerald-500 bg-emerald-50 text-gray-900 dark:border-emerald-500 dark:bg-emerald-900/30 dark:text-gray-50'
                                                        : 'border-gray-200 bg-gray-50 text-gray-700 hover:border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-slate-600',
                                                )}
                                            >
                                                <span className="block text-sm font-semibold">
                                                    {t(option.labelKey)}
                                                </span>
                                                <span className="mt-0.5 block text-xs opacity-70">
                                                    {t(option.detailKey)}
                                                </span>
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>

                            <div>
                                <label className="text-sm font-semibold text-gray-900 dark:text-gray-50">
                                    {t('conditions')}
                                </label>
                                <p className="mt-1 text-xs text-gray-500 dark:text-slate-400">
                                    {t('conditions_description')}
                                </p>
                                <div
                                    className="mt-2 grid grid-cols-2 gap-2"
                                    role="group"
                                    aria-label={t('conditions')}
                                >
                                    {CONDITION_OPTIONS.map((option) => {
                                        const checked =
                                            selectedConditions.includes(
                                                option.value,
                                            );

                                        return (
                                            <label
                                                key={option.value}
                                                className={cn(
                                                    'flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-2.5 text-sm transition duration-150 ease-[cubic-bezier(0.4,0,0.2,1)] focus-within:ring-2 focus-within:ring-emerald-500/40 focus-within:outline-none',
                                                    checked
                                                        ? 'border-emerald-500 bg-emerald-50 text-gray-900 dark:border-emerald-500 dark:bg-emerald-900/30 dark:text-gray-50'
                                                        : 'border-gray-200 bg-gray-50 text-gray-700 hover:border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-slate-600',
                                                )}
                                            >
                                                <input
                                                    type="checkbox"
                                                    className="size-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 dark:border-slate-600"
                                                    checked={checked}
                                                    onChange={() =>
                                                        toggleCondition(
                                                            option.value,
                                                        )
                                                    }
                                                />
                                                <span className="font-medium">
                                                    {t(option.labelKey)}
                                                </span>
                                            </label>
                                        );
                                    })}
                                </div>
                                {conditionsError && (
                                    <p className="mt-2 text-sm text-red-600 dark:text-red-400">
                                        {conditionsError}
                                    </p>
                                )}
                            </div>

                            <div>
                                <div className="flex items-center gap-2">
                                    <MessageSquareText
                                        className="size-4 text-emerald-700 dark:text-emerald-300"
                                        aria-hidden="true"
                                    />
                                    <label
                                        htmlFor="context"
                                        className="text-sm font-semibold text-gray-900 dark:text-gray-50"
                                    >
                                        {t('context_label')}
                                    </label>
                                </div>
                                <p className="mt-1 text-xs text-gray-500 dark:text-slate-400">
                                    {t('context_description')}
                                </p>
                                <Textarea
                                    id="context"
                                    value={form.data.context}
                                    onChange={(event) =>
                                        form.setData(
                                            'context',
                                            event.target.value,
                                        )
                                    }
                                    placeholder={t('context_placeholder')}
                                    rows={4}
                                    maxLength={1000}
                                    className="mt-2 rounded-lg border-gray-200 bg-white text-base focus-visible:border-emerald-500 focus-visible:ring-emerald-500/15 dark:border-slate-700 dark:bg-slate-900"
                                    aria-invalid={
                                        form.errors.context ? 'true' : undefined
                                    }
                                />
                                {form.errors.context && (
                                    <p className="mt-2 text-sm text-red-600 dark:text-red-400">
                                        {form.errors.context}
                                    </p>
                                )}
                            </div>

                            <button
                                type="submit"
                                disabled={!canSubmit || form.processing}
                                className="inline-flex h-12 w-full items-center justify-center gap-2 rounded-lg bg-emerald-500 px-6 text-base font-semibold text-white shadow-none transition-all duration-150 ease-[cubic-bezier(0.4,0,0.2,1)] hover:-translate-y-px hover:bg-emerald-600 focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:outline-none active:translate-y-0 active:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50 dark:focus:ring-offset-slate-900"
                            >
                                {form.processing ? (
                                    <LoaderCircle
                                        className="size-4 animate-spin"
                                        aria-hidden="true"
                                    />
                                ) : (
                                    <Activity
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                )}
                                {form.processing
                                    ? t('submit_loading')
                                    : t('submit_button')}
                            </button>
                        </form>
                    </section>

                    <section
                        data-caffeine-result
                        aria-live="polite"
                        aria-label={
                            form.response?.summary ?? 'Caffeine limit result'
                        }
                    >
                        {form.processing && <LoadingResult />}
                        {!form.processing && form.response && (
                            <CaffeineGuidanceRenderer
                                spec={form.response.spec}
                            />
                        )}
                        {!form.processing && !form.response && <EmptyResult />}
                    </section>
                </main>

                <CaffeineSeoSection faqItems={faqItems} />
            </div>
        </>
    );
}

interface FaqItem {
    question: string;
    answer: string;
}

type Translate = (key: string) => string;

function createStructuredDataSchema(
    seo: CaffeineCalculatorPageProps['seo'],
    locale: string,
    pageTitle: string,
    metaDescription: string,
    faqItems: FaqItem[],
    t: Translate,
) {
    const pageId = `${seo.canonicalUrl}#webpage`;
    const calculatorId = `${seo.canonicalUrl}#calculator`;
    const faqId = `${seo.canonicalUrl}#faq`;
    const breadcrumbId = `${seo.canonicalUrl}#breadcrumb`;
    const language = locale === 'mn' ? 'mn-MN' : 'en-US';

    return {
        '@context': 'https://schema.org',
        '@graph': [
            {
                '@type': 'WebPage',
                '@id': pageId,
                url: seo.canonicalUrl,
                name: pageTitle,
                description: metaDescription,
                inLanguage: language,
                isPartOf: {
                    '@type': 'WebSite',
                    '@id': `${seo.appUrl}#website`,
                    name: seo.appName,
                    url: seo.appUrl,
                },
                breadcrumb: {
                    '@id': breadcrumbId,
                },
                mainEntity: {
                    '@id': calculatorId,
                },
                citation: createSourceLinks(t).map((source) => ({
                    '@type': 'CreativeWork',
                    name: source.label,
                    url: source.url,
                })),
            },
            {
                '@type': 'WebApplication',
                '@id': calculatorId,
                name: pageTitle,
                description: metaDescription,
                url: seo.canonicalUrl,
                applicationCategory: 'HealthApplication',
                operatingSystem: 'Any',
                isAccessibleForFree: true,
                inLanguage: language,
                offers: {
                    '@type': 'Offer',
                    price: '0',
                    priceCurrency: 'USD',
                },
                publisher: {
                    '@type': 'Organization',
                    name: seo.appName,
                    url: seo.appUrl,
                },
                featureList: [
                    t('seo_feature_weight'),
                    t('seo_feature_sensitivity'),
                    t('seo_feature_context'),
                ],
            },
            {
                '@type': 'FAQPage',
                '@id': faqId,
                inLanguage: language,
                mainEntityOfPage: {
                    '@id': pageId,
                },
                mainEntity: faqItems.map((item) => ({
                    '@type': 'Question',
                    name: item.question,
                    acceptedAnswer: {
                        '@type': 'Answer',
                        text: item.answer,
                    },
                })),
            },
            {
                '@type': 'BreadcrumbList',
                '@id': breadcrumbId,
                itemListElement: [
                    {
                        '@type': 'ListItem',
                        position: 1,
                        name: t('breadcrumb_home'),
                        item: seo.appUrl,
                    },
                    {
                        '@type': 'ListItem',
                        position: 2,
                        name: t('breadcrumb_tools'),
                        item: seo.toolsUrl,
                    },
                    {
                        '@type': 'ListItem',
                        position: 3,
                        name: t('breadcrumb_current'),
                        item: seo.canonicalUrl,
                    },
                ],
            },
        ],
    };
}

function createFaqItems(t: Translate): FaqItem[] {
    return FAQ_KEYS.map((item) => ({
        question: t(item.questionKey),
        answer: t(item.answerKey),
    }));
}

function createSourceLinks(t: Translate) {
    return SOURCE_LINKS.map((source) => ({
        label: t(source.labelKey),
        url: source.url,
    }));
}

function toJsonLd(data: Record<string, unknown>): string {
    return JSON.stringify(data).replace(/</g, '\\u003c');
}

function Breadcrumbs({ seo }: { seo: CaffeineCalculatorPageProps['seo'] }) {
    const { t } = useTranslation('caffeine');

    return (
        <nav
            aria-label={t('breadcrumb_label')}
            className="mx-auto mb-4 flex max-w-7xl items-center gap-2 text-sm text-gray-500 lg:px-8 dark:text-slate-400"
        >
            <a
                href={seo.appUrl}
                className="font-medium text-gray-600 transition hover:text-emerald-700 dark:text-slate-300 dark:hover:text-emerald-300"
            >
                {t('breadcrumb_home')}
            </a>
            <ChevronRight className="size-4" aria-hidden="true" />
            <a
                href={seo.toolsUrl}
                className="font-medium text-gray-600 transition hover:text-emerald-700 dark:text-slate-300 dark:hover:text-emerald-300"
            >
                {t('breadcrumb_tools')}
            </a>
            <ChevronRight className="size-4" aria-hidden="true" />
            <span className="font-semibold text-gray-900 dark:text-gray-50">
                {t('breadcrumb_current')}
            </span>
        </nav>
    );
}

function CaffeineSeoSection({ faqItems }: { faqItems: FaqItem[] }) {
    const { t } = useTranslation('caffeine');
    const sources = createSourceLinks(t);

    return (
        <section className="mx-auto mt-8 max-w-7xl lg:px-8">
            <div className="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
                <article className="rounded-xl border border-gray-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                    <p className="text-sm font-semibold tracking-wide text-emerald-700 uppercase dark:text-emerald-300">
                        {t('seo_guide_label')}
                    </p>
                    <h2 className="mt-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-50">
                        {t('seo_guide_heading')}
                    </h2>
                    <p className="mt-3 max-w-3xl text-base leading-relaxed text-gray-600 dark:text-slate-400">
                        {t('seo_guide_summary')}
                    </p>
                    <div className="mt-6 grid gap-3 md:grid-cols-3">
                        <SeoStep
                            title={t('seo_step_weight_title')}
                            body={t('seo_step_weight_body')}
                        />
                        <SeoStep
                            title={t('seo_step_context_title')}
                            body={t('seo_step_context_body')}
                        />
                        <SeoStep
                            title={t('seo_step_sensitivity_title')}
                            body={t('seo_step_sensitivity_body')}
                        />
                    </div>
                </article>

                <aside className="rounded-xl border border-gray-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                    <div className="flex items-center gap-2">
                        <BookOpen
                            className="size-5 text-emerald-700 dark:text-emerald-300"
                            aria-hidden="true"
                        />
                        <h2 className="text-xl font-bold text-gray-900 dark:text-gray-50">
                            {t('seo_sources_heading')}
                        </h2>
                    </div>
                    <p className="mt-3 text-sm leading-relaxed text-gray-600 dark:text-slate-400">
                        {t('seo_sources_description')}
                    </p>
                    <ul className="mt-5 space-y-3">
                        {sources.map((source) => (
                            <li key={source.url}>
                                <a
                                    href={source.url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex items-center gap-2 text-sm font-semibold text-emerald-700 transition hover:text-emerald-900 dark:text-emerald-300 dark:hover:text-emerald-200"
                                >
                                    {source.label}
                                    <ExternalLink
                                        className="size-3.5"
                                        aria-hidden="true"
                                    />
                                </a>
                            </li>
                        ))}
                    </ul>
                </aside>
            </div>

            <section className="mt-6 rounded-xl border border-gray-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                <div className="max-w-3xl">
                    <h2 className="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-50">
                        {t('faq_heading')}
                    </h2>
                    <p className="mt-2 text-base leading-relaxed text-gray-600 dark:text-slate-400">
                        {t('faq_intro')}
                    </p>
                </div>
                <div className="mt-6 grid gap-5 md:grid-cols-2">
                    {faqItems.map((item) => (
                        <article
                            key={item.question}
                            className="border-t border-gray-200 pt-4 dark:border-slate-700"
                        >
                            <h3 className="text-base font-semibold text-gray-900 dark:text-gray-50">
                                {item.question}
                            </h3>
                            <p className="mt-2 text-sm leading-relaxed text-gray-600 dark:text-slate-400">
                                {item.answer}
                            </p>
                        </article>
                    ))}
                </div>
            </section>
        </section>
    );
}

function SeoStep({ title, body }: { title: string; body: string }) {
    return (
        <div className="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-slate-700 dark:bg-slate-900">
            <h3 className="text-sm font-semibold text-gray-900 dark:text-gray-50">
                {title}
            </h3>
            <p className="mt-2 text-sm leading-relaxed text-gray-600 dark:text-slate-400">
                {body}
            </p>
        </div>
    );
}

function LoadingResult() {
    return (
        <div className="flex flex-col gap-4">
            <div className="rounded-xl border border-gray-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                <div className="h-5 w-28 animate-pulse rounded-full bg-gray-200 dark:bg-slate-700" />
                <div className="mt-6 h-8 w-3/4 animate-pulse rounded-lg bg-gray-200 dark:bg-slate-700" />
                <div className="mt-3 h-4 w-full animate-pulse rounded bg-gray-100 dark:bg-slate-700" />
                <div className="mt-2 h-4 w-2/3 animate-pulse rounded bg-gray-100 dark:bg-slate-700" />
            </div>
            <div className="h-28 rounded-xl border border-gray-200 bg-white dark:border-slate-700 dark:bg-slate-800" />
            <div className="h-44 rounded-xl border border-gray-200 bg-white dark:border-slate-700 dark:bg-slate-800" />
        </div>
    );
}

function EmptyResult() {
    const { t } = useTranslation('caffeine');

    return (
        <div className="flex min-h-full items-center justify-center rounded-xl border border-dashed border-gray-300 bg-white p-8 text-center dark:border-slate-700 dark:bg-slate-800">
            <div className="max-w-sm">
                <span className="mx-auto flex size-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                    <Sparkles className="size-5" aria-hidden="true" />
                </span>
                <h2 className="mt-4 text-xl font-bold text-gray-900 dark:text-gray-50">
                    {t('empty_result_title')}
                </h2>
                <p className="mt-2 text-sm leading-relaxed text-gray-500 dark:text-slate-400">
                    {t('empty_result_description')}
                </p>
            </div>
        </div>
    );
}
