import AppLogoIcon from '@/components/app-logo-icon';
import CaffeineIntakeCalculator from '@/components/caffeine-intake-calculator';
import useSharedProps from '@/hooks/use-shared-props';
import { cn } from '@/lib/utils';
import {
    aiNutritionist,
    mealPlanner,
    privacy,
    register,
    snapToTrack,
    terms,
    usdaServingsCalculator,
} from '@/routes';
import { Head, usePage } from '@inertiajs/react';
import { ChevronRight, Coffee, Home, Plus } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

const PAPER = 'bg-[#F2EBDD]';
const INK = 'text-[#1A1814]';
const INK_2 = 'text-[#3D3833]';
const INK_3 = 'text-[#6E665C]';
const RULE = 'border-[#D9CFBC]';
const ACCENT = 'text-[#C4623A]';
const FONT_DISPLAY = 'font-bold';
const FONT_MONO = 'font-mono';

const EYEBROW = cn(FONT_MONO, INK_3, 'text-[11px] tracking-[0.18em] uppercase');

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
        questionKey: 'seo_faq_calculation_question',
        answerKey: 'seo_faq_calculation_answer',
    },
    {
        questionKey: 'seo_faq_single_dose_question',
        answerKey: 'seo_faq_single_dose_answer',
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

export default function CaffeineCalculator() {
    const { t } = useTranslation('caffeine');
    const { seo, locale } = usePage<CaffeineCalculatorPageProps>().props;
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

                    <main>
                        <CaffeineIntakeCalculator locale={locale} />

                        <CaffeineReferenceSection sources={sources} />

                        <CaffeineFaqSection faqItems={faqItems} />
                    </main>
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
                citation: createSourceLinks(t).map((source) => ({
                    '@type': 'CreativeWork',
                    name: source.label,
                    url: source.url,
                })),
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

function CaffeineReferenceSection({
    sources,
}: {
    sources: ReadonlyArray<{ label: string; url: string }>;
}) {
    const { t } = useTranslation('caffeine');

    const references = [
        {
            kicker: 'A',
            title: t('seo_single_dose_title'),
            body: t('seo_single_dose_body'),
        },
        {
            kicker: 'B',
            title: t('seo_daily_intake_title'),
            body: t('seo_daily_intake_body'),
        },
        {
            kicker: 'C',
            title: t('seo_pregnancy_lactation_title'),
            body: t('seo_pregnancy_lactation_body'),
        },
    ];

    return (
        <section className="mx-auto mt-16 max-w-7xl lg:px-8">
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
                {references.map((reference) => (
                    <article
                        key={reference.kicker}
                        className="border-b border-[#D9CFBC] px-2 pt-8 pb-10 sm:border-b-0 sm:px-7"
                    >
                        <div
                            className={cn(
                                FONT_DISPLAY,
                                ACCENT,
                                'text-5xl leading-none italic',
                            )}
                        >
                            {reference.kicker}
                        </div>
                        <h3
                            className={cn(
                                FONT_DISPLAY,
                                INK,
                                'mt-4 text-xl leading-tight tracking-[-0.01em]',
                            )}
                        >
                            {reference.title}
                        </h3>
                        <p
                            className={cn(
                                INK_2,
                                'mt-3 text-sm leading-relaxed',
                            )}
                        >
                            {reference.body}
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
        { label: t('footer_tool_snap_to_track'), href: snapToTrack.url() },
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
