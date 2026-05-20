import { plan as planRoute } from '@/actions/App/Http/Controllers/CaffeineCalculatorController';
import AppLogoIcon from '@/components/app-logo-icon';
import { CaffeineGuidanceRenderer } from '@/components/caffeine-guidance/render';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import useSharedProps from '@/hooks/use-shared-props';
import { cn } from '@/lib/utils';
import {
    aiNutritionist,
    mealPlanner,
    privacy,
    register,
    terms,
    usdaServingsCalculator,
} from '@/routes';
import { Head, useHttp, usePage } from '@inertiajs/react';
import type { Spec } from '@json-render/core';
import { ChevronRight, Coffee, Home, LoaderCircle, Plus } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { useTranslation } from 'react-i18next';

const PAPER = 'bg-[#F2EBDD]';
const PAPER_2 = 'bg-[#EBE2D0]';
const INK = 'text-[#1A1814]';
const INK_2 = 'text-[#3D3833]';
const INK_3 = 'text-[#6E665C]';
const RULE = 'border-[#D9CFBC]';
const ACCENT = 'text-[#C4623A]';
const ACCENT_BG = 'bg-[#C4623A]';
const FONT_DISPLAY = 'font-bold';
const FONT_MONO = 'font-mono';

const EYEBROW = cn(FONT_MONO, INK_3, 'text-[11px] tracking-[0.18em] uppercase');

interface AssessmentResponse {
    summary: string;
    limit: {
        weightKg: number;
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
    weight_kg: string;
    weight_lb: string;
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

function kgToLb(kg: number): number {
    return Math.round(kg * 2.20462);
}

function lbToKg(lb: number): number {
    return Math.round((lb / 2.20462) * 10) / 10;
}

const inputClass = cn(
    'h-11 rounded-none border bg-[#F2EBDD] text-base text-[#1A1814] placeholder:text-[#6E665C]',
    RULE,
    'focus-visible:border-[#1A1814] focus-visible:ring-[#1A1814]/15',
);

export default function CaffeineCalculator() {
    const { t } = useTranslation('caffeine');
    const { seo, locale } = usePage<CaffeineCalculatorPageProps>().props;
    const form = useHttp<AssessmentFormData, AssessmentResponse>(planRoute(), {
        weight_kg: '',
        weight_lb: '',
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

    function toggleUnitSystem(system: 'metric' | 'imperial'): void {
        if (system === unitSystem) {
            return;
        }
        setUnitSystem(system);
        form.setData('unit_system', system);

        if (system === 'imperial') {
            if (form.data.weight_kg) {
                form.setData(
                    'weight_lb',
                    String(kgToLb(Number(form.data.weight_kg))),
                );
            }
        } else if (form.data.weight_lb) {
            form.setData(
                'weight_kg',
                String(lbToKg(Number(form.data.weight_lb))),
            );
        }
    }

    function onSubmit(event: FormEvent): void {
        event.preventDefault();
        if (form.processing) {
            return;
        }

        let weightKg = Number(form.data.weight_kg);

        if (unitSystem === 'imperial') {
            weightKg = lbToKg(Number(form.data.weight_lb) || 0);
        }

        if (!weightKg) {
            return;
        }

        form.transform((data) => ({
            weight_kg: weightKg,
            sex: data.sex,
            sensitivity: data.sensitivity,
            context: data.context.trim() === '' ? null : data.context.trim(),
            conditions: selectedConditions,
            locale: data.locale,
        }));

        void form.submit();
    }

    const canSubmit =
        unitSystem === 'metric'
            ? form.data.weight_kg.trim() !== ''
            : form.data.weight_lb.trim() !== '';

    const conditionsError = (form.errors as Record<string, string | undefined>)[
        'conditions.0'
    ];
    const pageTitle = t('page_title');
    const metaDescription = t('meta_description');
    const faqItems = createFaqItems(t);
    const sources = createSourceLinks(t);
    const localeToOg: Record<string, string> = {
        en: 'en_US',
        mn: 'mn_MN',
        fr: 'fr_FR',
    };
    const openGraphLocale = localeToOg[locale] ?? 'en_US';
    const alternateOpenGraphLocale = locale === 'en' ? 'mn_MN' : 'en_US';

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

            <div className={cn('min-h-screen', PAPER, INK)}>
                <BrewlineHeader />

                <div className="px-4 py-8 md:py-12">
                    <Breadcrumbs seo={seo} />

                    <header className="mx-auto mt-6 max-w-7xl lg:px-8">
                        <h1
                            className={cn(
                                FONT_DISPLAY,
                                INK,
                                'text-[clamp(40px,5vw,68px)] leading-[1.02] tracking-[-0.02em] text-balance',
                            )}
                        >
                            {t('heading')}
                        </h1>
                        <p
                            className={cn(
                                INK_2,
                                'mt-5 max-w-2xl text-base leading-relaxed sm:text-lg',
                            )}
                        >
                            {t('subheading')}
                        </p>
                    </header>

                    <main className="mx-auto mt-8 grid max-w-7xl gap-6 lg:grid-cols-[0.92fr_1.08fr] lg:px-8">
                        <section
                            className={cn(
                                PAPER_2,
                                'border',
                                RULE,
                                'rounded-none p-6 sm:p-8',
                            )}
                        >
                            <div className="flex items-center justify-between gap-4 pb-5">
                                <p className={EYEBROW}>{t('form_title')}</p>
                                <div
                                    className={cn(
                                        'inline-flex border',
                                        RULE,
                                        'rounded-none',
                                    )}
                                    role="radiogroup"
                                    aria-label="Unit system"
                                >
                                    <button
                                        type="button"
                                        role="radio"
                                        aria-checked={unitSystem === 'metric'}
                                        onClick={() =>
                                            toggleUnitSystem('metric')
                                        }
                                        className={cn(
                                            FONT_MONO,
                                            'px-4 py-2 text-[11px] tracking-[0.14em] uppercase transition',
                                            unitSystem === 'metric'
                                                ? 'bg-[#1A1814] text-[#F2EBDD]'
                                                : 'text-[#3D3833] hover:bg-[#F2EBDD]',
                                        )}
                                    >
                                        kg
                                    </button>
                                    <button
                                        type="button"
                                        role="radio"
                                        aria-checked={unitSystem === 'imperial'}
                                        onClick={() =>
                                            toggleUnitSystem('imperial')
                                        }
                                        className={cn(
                                            FONT_MONO,
                                            'px-4 py-2 text-[11px] tracking-[0.14em] uppercase transition',
                                            unitSystem === 'imperial'
                                                ? 'bg-[#1A1814] text-[#F2EBDD]'
                                                : 'text-[#3D3833] hover:bg-[#F2EBDD]',
                                        )}
                                    >
                                        lb
                                    </button>
                                </div>
                            </div>

                            <form onSubmit={onSubmit} className="space-y-7">
                                {/* Region 1: body */}
                                <div className="space-y-5 border-t border-[#D9CFBC] pt-6">
                                    <FieldShell
                                        label={t('weight')}
                                        error={form.errors.weight_kg}
                                    >
                                        {unitSystem === 'metric' ? (
                                            <UnitInput
                                                id="weight_kg"
                                                value={form.data.weight_kg}
                                                onChange={(v) =>
                                                    form.setData('weight_kg', v)
                                                }
                                                placeholder={t(
                                                    'weight_kg_placeholder',
                                                )}
                                                suffix="kg"
                                                min={30}
                                                max={300}
                                                aria-invalid={
                                                    form.errors.weight_kg
                                                        ? 'true'
                                                        : undefined
                                                }
                                            />
                                        ) : (
                                            <UnitInput
                                                id="weight_lb"
                                                value={form.data.weight_lb}
                                                onChange={(v) =>
                                                    form.setData('weight_lb', v)
                                                }
                                                placeholder={t(
                                                    'weight_lb_placeholder',
                                                )}
                                                suffix="lb"
                                                min={66}
                                                max={660}
                                                aria-invalid={
                                                    form.errors.weight_kg
                                                        ? 'true'
                                                        : undefined
                                                }
                                            />
                                        )}
                                    </FieldShell>

                                    <FieldShell
                                        label={t('sex')}
                                        hint={t('sex_description')}
                                        error={form.errors.sex}
                                    >
                                        <ChipGroup
                                            ariaLabel={t('sex')}
                                            options={SEX_OPTIONS.map((o) => ({
                                                value: o.value,
                                                label: t(o.labelKey),
                                            }))}
                                            value={form.data.sex}
                                            onChange={(v) =>
                                                form.setData(
                                                    'sex',
                                                    v as AssessmentFormData['sex'],
                                                )
                                            }
                                        />
                                    </FieldShell>
                                </div>

                                {/* Region 2: sensitivity */}
                                <div className="space-y-3 border-t border-[#D9CFBC] pt-6">
                                    <FieldShell
                                        label={t('sensitivity')}
                                        error={form.errors.sensitivity}
                                    >
                                        <ChipGroup
                                            ariaLabel={t('sensitivity')}
                                            options={SENSITIVITY_OPTIONS.map(
                                                (o) => ({
                                                    value: o.value,
                                                    label: t(o.labelKey),
                                                    detail: t(o.detailKey),
                                                }),
                                            )}
                                            value={form.data.sensitivity}
                                            onChange={(v) =>
                                                form.setData(
                                                    'sensitivity',
                                                    v as AssessmentFormData['sensitivity'],
                                                )
                                            }
                                        />
                                    </FieldShell>
                                </div>

                                {/* Region 3: conditions */}
                                <div className="space-y-3 border-t border-[#D9CFBC] pt-6">
                                    <FieldShell
                                        label={t('conditions')}
                                        hint={t('conditions_description')}
                                        error={conditionsError}
                                    >
                                        <ChipGroup
                                            ariaLabel={t('conditions')}
                                            multi
                                            options={CONDITION_OPTIONS.map(
                                                (o) => ({
                                                    value: o.value,
                                                    label: t(o.labelKey),
                                                }),
                                            )}
                                            value={selectedConditions}
                                            onChange={(v) =>
                                                toggleCondition(
                                                    v as ConditionKey,
                                                )
                                            }
                                        />
                                    </FieldShell>
                                </div>

                                {/* Region 4: context */}
                                <div className="space-y-3 border-t border-[#D9CFBC] pt-6">
                                    <FieldShell
                                        label={t('context_label')}
                                        hint={t('context_description')}
                                        error={form.errors.context}
                                    >
                                        <Textarea
                                            id="context"
                                            value={form.data.context}
                                            onChange={(event) =>
                                                form.setData(
                                                    'context',
                                                    event.target.value,
                                                )
                                            }
                                            placeholder={t(
                                                'context_placeholder',
                                            )}
                                            rows={4}
                                            maxLength={1000}
                                            className={cn(
                                                'rounded-none border bg-[#F2EBDD] text-base text-[#1A1814] placeholder:text-[#6E665C]',
                                                RULE,
                                                'focus-visible:border-[#1A1814] focus-visible:ring-[#1A1814]/15',
                                            )}
                                            aria-invalid={
                                                form.errors.context
                                                    ? 'true'
                                                    : undefined
                                            }
                                        />
                                    </FieldShell>
                                </div>

                                <button
                                    type="submit"
                                    disabled={!canSubmit || form.processing}
                                    className={cn(
                                        'inline-flex h-12 w-full items-center justify-center gap-2 rounded-none bg-[#1A1814] px-6 text-base font-semibold text-[#F2EBDD] transition',
                                        'hover:bg-[#3D3833] focus:ring-2 focus:ring-[#1A1814] focus:ring-offset-2 focus:ring-offset-[#EBE2D0] focus:outline-none',
                                        'disabled:cursor-not-allowed disabled:opacity-50',
                                    )}
                                >
                                    {form.processing ? (
                                        <LoaderCircle
                                            className="size-4 animate-spin"
                                            aria-hidden="true"
                                        />
                                    ) : null}
                                    {form.processing
                                        ? t('submit_loading')
                                        : t('submit_button')}
                                </button>
                            </form>
                        </section>

                        <section
                            aria-live="polite"
                            aria-label={
                                form.response?.summary ??
                                'Caffeine limit result'
                            }
                        >
                            {form.processing && <LoadingResult />}
                            {!form.processing && form.response && (
                                <div className="motion-safe:duration-200 motion-safe:animate-in motion-safe:fade-in-0 motion-safe:slide-in-from-bottom-2">
                                    <CaffeineGuidanceRenderer
                                        spec={form.response.spec}
                                    />
                                </div>
                            )}
                            {!form.processing && !form.response && (
                                <EmptyResult />
                            )}
                        </section>
                    </main>

                    <CaffeineMethodSection sources={sources} />

                    <CaffeineFaqSection faqItems={faqItems} />

                    <CaffeineCtaSection />
                </div>

                <CaffeineFooter />
            </div>
        </>
    );
}

interface FaqItem {
    question: string;
    answer: string;
}

type Translate = (key: string, options?: Record<string, unknown>) => string;

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
    const language =
        locale === 'mn' ? 'mn-MN' : locale === 'fr' ? 'fr-FR' : 'en-US';

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

function BrewlineHeader() {
    const { t } = useTranslation('caffeine');
    const { currentUser } = useSharedProps();

    return (
        <header
            className={cn(
                'sticky top-0 z-50 border-b',
                RULE,
                'bg-[#F2EBDD]/85 backdrop-blur-md',
            )}
        >
            <div className="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <a
                    href="/"
                    className="flex items-center gap-3 transition hover:opacity-80"
                    aria-label={t('footer_powered_by_brand')}
                >
                    <AppLogoIcon className="size-7" aria-hidden="true" />
                    <span
                        className={cn(
                            FONT_DISPLAY,
                            INK,
                            'text-2xl leading-none tracking-[-0.01em]',
                        )}
                    >
                        {t('footer_powered_by_brand')}
                    </span>
                    <Coffee
                        className={cn('size-4', ACCENT)}
                        aria-hidden="true"
                    />
                </a>

                <div className="flex items-center gap-4">
                    {currentUser ? (
                        <a
                            href="/dashboard"
                            className={cn(
                                'inline-flex items-center gap-2 rounded-none border bg-[#1A1814] px-5 py-2 text-[#F2EBDD] transition hover:bg-[#3D3833]',
                                'border-[#1A1814]',
                                FONT_MONO,
                                'text-[11px] tracking-[0.16em] uppercase',
                            )}
                        >
                            Dashboard →
                        </a>
                    ) : (
                        <>
                            <a
                                href="/login"
                                className={cn(
                                    FONT_MONO,
                                    INK_2,
                                    'hidden text-[11px] tracking-[0.16em] uppercase transition hover:text-[#1A1814] sm:inline',
                                )}
                            >
                                Log in
                            </a>
                            <a
                                href="/register"
                                className={cn(
                                    'inline-flex items-center gap-2 rounded-none border px-5 py-2 transition hover:bg-[#1A1814] hover:text-[#F2EBDD]',
                                    'border-[#1A1814] text-[#1A1814]',
                                    FONT_MONO,
                                    'text-[11px] tracking-[0.16em] uppercase',
                                )}
                            >
                                Get started →
                            </a>
                        </>
                    )}
                </div>
            </div>
        </header>
    );
}

function Breadcrumbs({ seo }: { seo: CaffeineCalculatorPageProps['seo'] }) {
    const { t } = useTranslation('caffeine');

    return (
        <nav
            aria-label={t('breadcrumb_label')}
            className={cn(
                'mx-auto flex max-w-7xl items-center gap-2',
                FONT_MONO,
                INK_3,
                'text-[11px] tracking-[0.14em] uppercase lg:px-8',
            )}
        >
            <a
                href={seo.appUrl}
                aria-label={t('breadcrumb_home')}
                className={cn('transition hover:text-[#1A1814]', INK_3)}
            >
                <Home className="size-3.5" aria-hidden="true" />
            </a>
            <ChevronRight className="size-3.5" aria-hidden="true" />
            <a
                href={seo.toolsUrl}
                className={cn('transition hover:text-[#1A1814]', INK_3)}
            >
                {t('breadcrumb_tools')}
            </a>
            <ChevronRight className="size-3.5" aria-hidden="true" />
            <span className={cn(INK)}>{t('breadcrumb_current')}</span>
        </nav>
    );
}

function FieldShell({
    label,
    hint,
    error,
    children,
}: {
    label: string;
    hint?: string;
    error?: string;
    children: React.ReactNode;
}) {
    return (
        <div>
            <div className="flex items-baseline justify-between gap-3">
                <span className={EYEBROW}>{label}</span>
                {error ? (
                    <span
                        className={cn(
                            FONT_MONO,
                            'text-[10px] tracking-[0.12em] text-[#B5482E] uppercase',
                        )}
                    >
                        {error}
                    </span>
                ) : null}
            </div>
            {hint ? <p className={cn('mt-1 text-xs', INK_3)}>{hint}</p> : null}
            <div className="mt-3">{children}</div>
        </div>
    );
}

function UnitInput({
    id,
    value,
    onChange,
    placeholder,
    suffix,
    min,
    max,
    'aria-invalid': ariaInvalid,
}: {
    id: string;
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    suffix?: string;
    min?: number;
    max?: number;
    'aria-invalid'?: 'true' | 'false';
}) {
    return (
        <div className="relative w-full">
            <Input
                id={id}
                type="number"
                inputMode="numeric"
                min={min}
                max={max}
                value={value}
                onChange={(event) => onChange(event.target.value)}
                placeholder={placeholder}
                className={cn(inputClass, suffix ? 'pr-12' : '')}
                aria-invalid={ariaInvalid}
            />
            {suffix ? (
                <span
                    className={cn(
                        'pointer-events-none absolute top-1/2 right-3 -translate-y-1/2',
                        FONT_MONO,
                        INK_3,
                        'text-[10px] tracking-[0.14em] uppercase',
                    )}
                >
                    {suffix}
                </span>
            ) : null}
        </div>
    );
}

interface ChipOption {
    value: string;
    label: string;
    detail?: string;
}

function ChipGroup({
    options,
    value,
    onChange,
    multi = false,
    ariaLabel,
}: {
    options: ChipOption[];
    value: string | string[];
    onChange: (value: string) => void;
    multi?: boolean;
    ariaLabel?: string;
}) {
    const isSelected = (v: string) =>
        Array.isArray(value) ? value.includes(v) : value === v;

    return (
        <div
            className="flex flex-wrap gap-2"
            role={multi ? 'group' : 'radiogroup'}
            aria-label={ariaLabel}
        >
            {options.map((option) => {
                const selected = isSelected(option.value);
                return (
                    <button
                        key={option.value}
                        type="button"
                        role={multi ? 'checkbox' : 'radio'}
                        aria-checked={selected}
                        onClick={() => onChange(option.value)}
                        className={cn(
                            'rounded-none border px-3.5 py-2 text-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1A1814]/30',
                            selected
                                ? 'border-[#1A1814] bg-[#1A1814] text-[#F2EBDD]'
                                : 'border-[#D9CFBC] bg-[#F2EBDD] text-[#3D3833] hover:border-[#1A1814]/40',
                        )}
                    >
                        <span className="block font-medium">
                            {option.label}
                        </span>
                        {option.detail ? (
                            <span
                                className={cn(
                                    'mt-0.5 block text-[11px]',
                                    selected ? 'text-[#F2EBDD]/70' : INK_3,
                                )}
                            >
                                {option.detail}
                            </span>
                        ) : null}
                    </button>
                );
            })}
        </div>
    );
}

function LoadingResult() {
    return (
        <div className="flex flex-col gap-4">
            <div className={cn('border', RULE, 'rounded-none p-6', PAPER)}>
                <div className="h-3 w-28 animate-pulse rounded-none bg-[#D9CFBC]" />
                <div className="mt-6 h-12 w-3/4 animate-pulse rounded-none bg-[#D9CFBC]" />
                <div className="mt-3 h-3 w-full animate-pulse rounded-none bg-[#EBE2D0]" />
                <div className="mt-2 h-3 w-2/3 animate-pulse rounded-none bg-[#EBE2D0]" />
            </div>
            <div
                className={cn(
                    'h-28 animate-pulse rounded-none border',
                    RULE,
                    PAPER,
                )}
            />
            <div
                className={cn(
                    'h-44 animate-pulse rounded-none border',
                    RULE,
                    PAPER,
                )}
            />
        </div>
    );
}

function EmptyResult() {
    const { t } = useTranslation('caffeine');

    return (
        <div
            className={cn(
                'flex min-h-full items-center justify-center rounded-none border-2 border-dashed p-10 text-center',
                RULE,
                PAPER,
            )}
        >
            <div className="max-w-sm">
                <span
                    className={cn(
                        'mx-auto block size-2 rounded-full',
                        ACCENT_BG,
                    )}
                    aria-hidden="true"
                />
                <h2
                    className={cn(
                        FONT_DISPLAY,
                        INK,
                        'mt-5 text-2xl leading-tight tracking-[-0.02em]',
                    )}
                >
                    {t('empty_result_title')}
                </h2>
                <p className={cn(INK_2, 'mt-3 text-sm leading-relaxed')}>
                    {t('empty_result_description')}
                </p>
            </div>
        </div>
    );
}

function CaffeineMethodSection({
    sources,
}: {
    sources: ReadonlyArray<{ label: string; url: string }>;
}) {
    const { t } = useTranslation('caffeine');

    const steps = [
        {
            kicker: 'A',
            title: t('seo_step_weight_title'),
            body: t('seo_step_weight_body'),
        },
        {
            kicker: 'B',
            title: t('seo_step_context_title'),
            body: t('seo_step_context_body'),
        },
        {
            kicker: 'C',
            title: t('seo_step_sensitivity_title'),
            body: t('seo_step_sensitivity_body'),
        },
    ];

    return (
        <section className="mx-auto mt-24 max-w-7xl lg:px-8">
            <p className={EYEBROW}>{t('seo_guide_label')}</p>
            <h2
                className={cn(
                    FONT_DISPLAY,
                    INK,
                    'mt-4 max-w-3xl text-[clamp(28px,3.4vw,44px)] leading-[1.05] tracking-[-0.02em]',
                )}
            >
                {t('seo_guide_heading')}
            </h2>
            <p
                className={cn(
                    INK_2,
                    'mt-5 max-w-3xl text-base leading-relaxed',
                )}
            >
                {t('seo_guide_summary')}
            </p>

            <div
                className={cn(
                    'mt-10 grid border-t',
                    RULE,
                    'sm:grid-cols-3 sm:divide-x sm:divide-[#D9CFBC]',
                )}
            >
                {steps.map((step) => (
                    <article
                        key={step.kicker}
                        className="border-b border-[#D9CFBC] px-2 pt-8 pb-10 sm:border-b-0 sm:px-7"
                    >
                        <div
                            className={cn(
                                FONT_DISPLAY,
                                ACCENT,
                                'text-5xl leading-none italic',
                            )}
                        >
                            {step.kicker}
                        </div>
                        <h3
                            className={cn(
                                FONT_DISPLAY,
                                INK,
                                'mt-4 text-xl leading-tight tracking-[-0.01em]',
                            )}
                        >
                            {step.title}
                        </h3>
                        <p
                            className={cn(
                                INK_2,
                                'mt-3 text-sm leading-relaxed',
                            )}
                        >
                            {step.body}
                        </p>
                    </article>
                ))}
            </div>

            <aside
                className={cn(
                    'mt-12 grid gap-8 border-t pt-10 sm:grid-cols-[2fr_3fr]',
                    RULE,
                )}
            >
                <div>
                    <p className={EYEBROW}>{t('seo_sources_heading')}</p>
                    <p
                        className={cn(
                            INK_2,
                            'mt-3 max-w-md text-sm leading-relaxed',
                        )}
                    >
                        {t('seo_sources_description')}
                    </p>
                </div>
                <ul className={cn('divide-y', RULE)}>
                    {sources.map((source, index) => (
                        <li key={source.url}>
                            <a
                                href={source.url}
                                target="_blank"
                                rel="noreferrer"
                                className="group flex items-baseline gap-4 py-3 transition hover:bg-[#EBE2D0]"
                            >
                                <span
                                    className={cn(
                                        FONT_MONO,
                                        ACCENT,
                                        'text-[11px] tracking-[0.14em]',
                                    )}
                                    aria-hidden="true"
                                >
                                    {String(index + 1).padStart(2, '0')}
                                </span>
                                <span
                                    className={cn(
                                        FONT_DISPLAY,
                                        INK,
                                        'text-lg leading-tight transition group-hover:text-[#C4623A]',
                                    )}
                                >
                                    {source.label}
                                </span>
                            </a>
                        </li>
                    ))}
                </ul>
            </aside>
        </section>
    );
}

function CaffeineFaqSection({ faqItems }: { faqItems: FaqItem[] }) {
    const { t } = useTranslation('caffeine');
    const [openIndex, setOpenIndex] = useState<number | null>(0);

    return (
        <section className="mx-auto mt-24 max-w-7xl lg:px-8">
            <div className="grid gap-12 sm:grid-cols-[1fr_2fr]">
                <div>
                    <p className={EYEBROW}>{t('faq_heading')}</p>
                    <h2
                        className={cn(
                            FONT_DISPLAY,
                            INK,
                            'mt-4 text-[clamp(28px,3.4vw,44px)] leading-[1.05] tracking-[-0.02em]',
                        )}
                    >
                        {t('faq_heading')}
                    </h2>
                    <p className={cn(INK_2, 'mt-3 text-sm leading-relaxed')}>
                        {t('faq_intro')}
                    </p>
                </div>
                <div>
                    {faqItems.map((item, index) => {
                        const open = openIndex === index;
                        return (
                            <div
                                key={item.question}
                                className={cn(
                                    'border-t',
                                    index === 0 ? 'border-[#1A1814]' : RULE,
                                    index === faqItems.length - 1
                                        ? 'border-b border-[#D9CFBC]'
                                        : '',
                                )}
                            >
                                <button
                                    type="button"
                                    onClick={() =>
                                        setOpenIndex(open ? null : index)
                                    }
                                    aria-expanded={open}
                                    className="flex w-full items-baseline justify-between gap-4 py-5 text-left"
                                >
                                    <div className="flex items-baseline gap-4">
                                        <span
                                            className={cn(
                                                FONT_MONO,
                                                INK_3,
                                                'text-[11px] tracking-[0.14em]',
                                            )}
                                            aria-hidden="true"
                                        >
                                            {String(index + 1).padStart(2, '0')}
                                        </span>
                                        <span
                                            className={cn(
                                                FONT_DISPLAY,
                                                INK,
                                                'text-lg leading-tight tracking-[-0.01em] sm:text-xl',
                                            )}
                                        >
                                            {item.question}
                                        </span>
                                    </div>
                                    <Plus
                                        className={cn(
                                            'mt-1 size-5 shrink-0 transition-transform duration-200',
                                            ACCENT,
                                            open ? 'rotate-45' : '',
                                        )}
                                        aria-hidden="true"
                                    />
                                </button>
                                <div
                                    className={cn(
                                        'grid transition-[grid-template-rows] duration-300 ease-out',
                                        open
                                            ? 'grid-rows-[1fr]'
                                            : 'grid-rows-[0fr]',
                                    )}
                                >
                                    <div className="overflow-hidden">
                                        <p
                                            className={cn(
                                                INK_2,
                                                'mt-0 mb-6 max-w-prose pl-10 text-sm leading-relaxed',
                                            )}
                                        >
                                            {item.answer}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>
        </section>
    );
}

function CaffeineCtaSection() {
    return (
        <section className="mx-auto mt-24 max-w-7xl lg:px-8">
            <div
                className={cn(
                    'border',
                    RULE,
                    PAPER_2,
                    'flex flex-col items-center gap-8 rounded-none p-8 text-center sm:flex-row sm:items-center sm:gap-10 sm:p-12 sm:text-left',
                )}
            >
                <div className="shrink-0">
                    <img
                        src="https://pub-plate-assets.acara.app/images/altani_with_hand_on_chin_considering_expression_thought-1024.webp"
                        alt="Altani, your personal AI health coach"
                        className={cn(
                            'h-28 w-28 rounded-none border object-cover sm:h-32 sm:w-32',
                            RULE,
                        )}
                    />
                </div>
                <div className="flex-1">
                    <p className={EYEBROW}>Meet Altani</p>
                    <h3
                        className={cn(
                            FONT_DISPLAY,
                            INK,
                            'mt-3 text-2xl leading-tight tracking-[-0.02em] sm:text-3xl',
                        )}
                    >
                        Your personal AI health coach.
                    </h3>
                    <p
                        className={cn(
                            INK_2,
                            'mt-3 max-w-xl text-sm leading-relaxed sm:text-base',
                        )}
                    >
                        Altani helps you plan meals, predict glucose responses,
                        and stay on track with your health goals. She&apos;s
                        available 24/7 and learns what works best for your body.
                    </p>
                    <div className="mt-6">
                        <a
                            href="/meet-altani"
                            className={cn(
                                'inline-flex items-center gap-2 rounded-none border px-6 py-3 transition hover:bg-[#1A1814] hover:text-[#F2EBDD]',
                                'border-[#1A1814] text-[#1A1814]',
                                FONT_MONO,
                                'text-[11px] tracking-[0.16em] uppercase',
                            )}
                        >
                            Ask Altani For A Better Energy Plan →
                        </a>
                    </div>
                </div>
            </div>
        </section>
    );
}

function CaffeineFooter() {
    const { t } = useTranslation('caffeine');

    const tools = [
        { label: t('footer_tool_meal_planner'), href: mealPlanner.url() },
        { label: t('footer_tool_ask_ai'), href: register.url() },
        { label: t('footer_tool_ai_nutritionist'), href: aiNutritionist.url() },
        {
            label: t('footer_tool_usda_calculator'),
            href: usdaServingsCalculator.url(),
        },
    ];

    return (
        <footer className={cn('border-t', RULE, PAPER, 'mt-24 py-20')}>
            <div className="mx-auto flex max-w-7xl flex-col items-center px-4 text-center lg:px-8">
                <div className="flex w-full items-center gap-6">
                    <div className={cn('h-px flex-1', 'bg-[#D9CFBC]')} />
                    <div className="flex items-center gap-3">
                        <AppLogoIcon className="size-10" aria-hidden="true" />
                        <div className="flex flex-col items-start leading-none">
                            <span className={cn(EYEBROW)}>
                                {t('footer_powered_by_label')}
                            </span>
                            <span
                                className={cn(
                                    FONT_DISPLAY,
                                    INK,
                                    'mt-1 text-xl tracking-[-0.01em]',
                                )}
                            >
                                {t('footer_powered_by_brand')}
                            </span>
                        </div>
                    </div>
                    <div className={cn('h-px flex-1', 'bg-[#D9CFBC]')} />
                </div>

                <div className="mt-12 space-y-2">
                    <p className={cn(INK_2, 'text-base leading-relaxed')}>
                        {t('footer_tagline_line1')}
                    </p>
                    <p className={cn(INK_2, 'text-base leading-relaxed')}>
                        {t('footer_tagline_line2')}
                    </p>
                </div>

                <div
                    className={cn(
                        'mt-6 flex items-center gap-3',
                        FONT_MONO,
                        INK_3,
                        'text-[11px] tracking-[0.14em] uppercase',
                    )}
                >
                    <a
                        href={terms.url()}
                        className="transition hover:text-[#1A1814]"
                    >
                        {t('footer_terms')}
                    </a>
                    <span aria-hidden="true">·</span>
                    <a
                        href={privacy.url()}
                        className="transition hover:text-[#1A1814]"
                    >
                        {t('footer_privacy')}
                    </a>
                </div>

                <div className="mt-20">
                    <p className={EYEBROW}>{t('footer_more_tools_heading')}</p>
                    <nav className="mt-6 flex flex-col items-center gap-3">
                        {tools.map((tool) => (
                            <a
                                key={tool.href}
                                href={tool.href}
                                className={cn(
                                    FONT_DISPLAY,
                                    INK_2,
                                    'text-lg transition hover:text-[#1A1814]',
                                )}
                            >
                                {tool.label}
                            </a>
                        ))}
                    </nav>
                </div>
            </div>
        </footer>
    );
}
