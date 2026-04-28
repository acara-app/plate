import { plan as planRoute } from '@/actions/App/Http/Controllers/CaffeineCalculatorController';
import { CaffeineGuidanceRenderer } from '@/components/caffeine-guidance/render';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { Head, useHttp, usePage } from '@inertiajs/react';
import type { Spec } from '@json-render/core';
import {
    Activity,
    Coffee,
    LoaderCircle,
    MessageSquareText,
    Ruler,
    Sparkles,
} from 'lucide-react';
import type { FormEvent } from 'react';

interface AssessmentResponse {
    summary: string;
    limit: {
        heightCm: number;
        sensitivity: string;
        limitMg: number | null;
        status: string;
    };
    spec: Spec;
}

interface AssessmentFormData {
    height_cm: string;
    sensitivity: 'low' | 'normal' | 'high';
    context: string;
}

interface CaffeineCalculatorPageProps {
    seo: {
        appName: string;
        appUrl: string;
        canonicalUrl: string;
    };
    [key: string]: unknown;
}

const SENSITIVITY_OPTIONS: Array<{
    value: AssessmentFormData['sensitivity'];
    label: string;
    detail: string;
}> = [
    { value: 'low', label: 'Low', detail: 'Tolerant' },
    { value: 'normal', label: 'Normal', detail: 'Typical' },
    { value: 'high', label: 'High', detail: 'Sensitive' },
];

export default function CaffeineCalculator() {
    const { seo } = usePage<CaffeineCalculatorPageProps>().props;
    const form = useHttp<AssessmentFormData, AssessmentResponse>(planRoute(), {
        height_cm: '',
        sensitivity: 'normal',
        context: '',
    });

    function onSubmit(event: FormEvent): void {
        event.preventDefault();
        if (form.data.height_cm.trim() === '' || form.processing) {
            return;
        }

        form.transform((data) => ({
            height_cm: Number(data.height_cm),
            sensitivity: data.sensitivity,
            context: data.context.trim() === '' ? null : data.context.trim(),
        }));

        void form.submit();
    }

    return (
        <>
            <Head title="Caffeine Calculator: How Much Is Too Much?">
                <meta
                    head-key="description"
                    name="description"
                    content="Enter height and caffeine sensitivity to get a personalized daily caffeine limit with AI-written guidance."
                />
                <meta
                    head-key="keywords"
                    name="keywords"
                    content="caffeine calculator, how much caffeine is too much, caffeine limit by height, caffeine sensitivity"
                />
                <script
                    head-key="caffeine-calculator-web-application"
                    type="application/ld+json"
                    dangerouslySetInnerHTML={{
                        __html: toJsonLd(createWebApplicationSchema(seo)),
                    }}
                />
                <script
                    head-key="caffeine-calculator-faq"
                    type="application/ld+json"
                    dangerouslySetInnerHTML={{
                        __html: toJsonLd(createFaqSchema()),
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

            <div className="min-h-screen bg-slate-50 px-4 py-6 text-slate-950 md:py-10 dark:bg-slate-950 dark:text-slate-50">
                <main className="mx-auto grid max-w-6xl gap-6 lg:grid-cols-[0.92fr_1.08fr]">
                    <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:p-6 dark:border-slate-800 dark:bg-slate-900">
                        <div className="flex items-center gap-3">
                            <span className="flex size-11 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                                <Coffee className="size-5" aria-hidden="true" />
                            </span>
                            <div>
                                <p className="text-sm font-semibold text-emerald-700 uppercase dark:text-emerald-300">
                                    Caffeine limit
                                </p>
                                <h1 className="text-3xl leading-tight font-bold tracking-tight md:text-4xl">
                                    How Much Is Too Much?
                                </h1>
                            </div>
                        </div>

                        <form onSubmit={onSubmit} className="mt-7 space-y-5">
                            <div>
                                <label
                                    htmlFor="height_cm"
                                    className="text-sm font-semibold text-slate-800 dark:text-slate-100"
                                >
                                    Height
                                </label>
                                <div className="relative mt-2">
                                    <Ruler
                                        className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400"
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
                                        placeholder="170"
                                        className="h-11 bg-white pr-14 pl-10 text-base dark:bg-slate-950"
                                        aria-invalid={
                                            form.errors.height_cm
                                                ? 'true'
                                                : undefined
                                        }
                                    />
                                    <span className="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-sm font-semibold text-slate-500 dark:text-slate-400">
                                        cm
                                    </span>
                                </div>
                                {form.errors.height_cm && (
                                    <p className="mt-2 text-sm text-red-600 dark:text-red-400">
                                        {form.errors.height_cm}
                                    </p>
                                )}
                            </div>

                            <div>
                                <div className="flex items-center justify-between gap-3">
                                    <label className="text-sm font-semibold text-slate-800 dark:text-slate-100">
                                        Sensitivity
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
                                    aria-label="Caffeine sensitivity"
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
                                                    'rounded-xl border px-3 py-3 text-left transition focus:ring-2 focus:ring-emerald-500/40 focus:outline-none',
                                                    selected
                                                        ? 'border-emerald-500 bg-emerald-50 text-emerald-950 dark:border-emerald-500 dark:bg-emerald-950/50 dark:text-emerald-50'
                                                        : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300 dark:hover:border-slate-600',
                                                )}
                                            >
                                                <span className="block text-sm font-semibold">
                                                    {option.label}
                                                </span>
                                                <span className="mt-0.5 block text-xs opacity-70">
                                                    {option.detail}
                                                </span>
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>

                            <div>
                                <div className="flex items-center gap-2">
                                    <MessageSquareText
                                        className="size-4 text-emerald-700 dark:text-emerald-300"
                                        aria-hidden="true"
                                    />
                                    <label
                                        htmlFor="context"
                                        className="text-sm font-semibold text-slate-800 dark:text-slate-100"
                                    >
                                        Drink or personal context
                                    </label>
                                </div>
                                <Textarea
                                    id="context"
                                    value={form.data.context}
                                    onChange={(event) =>
                                        form.setData(
                                            'context',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Example: morning latte, two Americanos, pregnant, anxiety, heart medication, or caffeine makes me jittery"
                                    rows={4}
                                    maxLength={1000}
                                    className="mt-2 bg-white text-base dark:bg-slate-950"
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

                            <Button
                                type="submit"
                                size="lg"
                                disabled={
                                    form.processing ||
                                    form.data.height_cm.trim() === ''
                                }
                                className="h-12 w-full"
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
                                    ? 'Checking limit'
                                    : 'Show my limit'}
                            </Button>
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
            </div>
        </>
    );
}

function createWebApplicationSchema(seo: CaffeineCalculatorPageProps['seo']) {
    return {
        '@context': 'https://schema.org',
        '@type': 'WebApplication',
        name: 'Caffeine Calculator: How Much Is Too Much?',
        description:
            'Free caffeine calculator: enter height and sensitivity to get a personalized daily caffeine limit.',
        url: seo.canonicalUrl,
        applicationCategory: 'HealthApplication',
        operatingSystem: 'Any',
        offers: {
            '@type': 'Offer',
            price: '0',
            priceCurrency: 'USD',
        },
        author: {
            '@type': 'Organization',
            name: seo.appName,
            url: seo.appUrl,
        },
    };
}

function createFaqSchema() {
    return {
        '@context': 'https://schema.org',
        '@type': 'FAQPage',
        mainEntity: [
            {
                '@type': 'Question',
                name: 'How much caffeine is safe per day?',
                acceptedAnswer: {
                    '@type': 'Answer',
                    text: 'For most healthy adults, up to 400 mg per day is a common reference point. This calculator adjusts that educational limit based on height as a body-size proxy and self-reported caffeine sensitivity.',
                },
            },
            {
                '@type': 'Question',
                name: 'How does the caffeine calculator work?',
                acceptedAnswer: {
                    '@type': 'Answer',
                    text: 'The calculator starts with a common adult reference limit, adjusts it conservatively by height and sensitivity, then lowers it for optional context such as pregnancy or breastfeeding.',
                },
            },
            {
                '@type': 'Question',
                name: 'Why does height matter for caffeine?',
                acceptedAnswer: {
                    '@type': 'Answer',
                    text: 'Height is used as a simple body-size proxy. It is not a medical measurement, but it helps the tool avoid giving the same caffeine limit to every person.',
                },
            },
            {
                '@type': 'Question',
                name: 'Is this caffeine calculator a substitute for medical advice?',
                acceptedAnswer: {
                    '@type': 'Answer',
                    text: 'No. This tool provides educational guidance only. People who are pregnant, breastfeeding, trying to conceive, taking medications, or managing a health condition should follow clinician guidance.',
                },
            },
            {
                '@type': 'Question',
                name: 'Does caffeine affect everyone the same way?',
                acceptedAnswer: {
                    '@type': 'Answer',
                    text: 'No. Height, sensitivity, sleep, medications, health conditions, pregnancy, and breastfeeding can all change how caffeine feels and how much is too much.',
                },
            },
        ],
    };
}

function toJsonLd(data: Record<string, unknown>): string {
    return JSON.stringify(data).replace(/</g, '\\u003c');
}

function LoadingResult() {
    return (
        <div className="flex flex-col gap-4">
            <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div className="h-5 w-28 animate-pulse rounded-full bg-slate-200 dark:bg-slate-800" />
                <div className="mt-6 h-8 w-3/4 animate-pulse rounded-lg bg-slate-200 dark:bg-slate-800" />
                <div className="mt-3 h-4 w-full animate-pulse rounded bg-slate-100 dark:bg-slate-800" />
                <div className="mt-2 h-4 w-2/3 animate-pulse rounded bg-slate-100 dark:bg-slate-800" />
            </div>
            <div className="h-28 rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900" />
            <div className="h-44 rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900" />
        </div>
    );
}

function EmptyResult() {
    return (
        <div className="flex min-h-full items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div className="max-w-sm">
                <span className="mx-auto flex size-12 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                    <Sparkles className="size-5" aria-hidden="true" />
                </span>
                <h2 className="mt-4 text-xl font-bold text-slate-900 dark:text-slate-50">
                    Your limit appears here
                </h2>
                <p className="mt-2 text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                    The answer starts with a daily milligram limit adjusted by
                    height and sensitivity.
                </p>
            </div>
        </div>
    );
}
