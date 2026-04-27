import { plan as planRoute } from '@/actions/App/Http/Controllers/CaffeineCalculatorController';
import { BrewPlanRenderer } from '@/components/brew-buddy/render';
import type { Spec } from '@json-render/core';
import { Head, useHttp } from '@inertiajs/react';
import type { FormEvent } from 'react';

interface PlanResponse {
    summary: string;
    spec: Spec;
}

interface PlanFormData {
    prompt: string;
    weight_kg: string;
    bedtime: string;
    sensitivity: '' | 'low' | 'normal' | 'high';
}

const EXAMPLES: string[] = [
    "I have a 9am sprint demo and a 3pm wall. Bedtime 22:30, ~75kg, normal sensitivity.",
    "I'm cutting back. Want one solid morning coffee and then green tea.",
    "I'm sensitive to caffeine and have a late dinner meeting. Bedtime 23:00.",
];

export default function CaffeineCalculator() {
    const form = useHttp<PlanFormData, PlanResponse>(planRoute(), {
        prompt: '',
        weight_kg: '',
        bedtime: '',
        sensitivity: '',
    });

    function onSubmit(event: FormEvent): void {
        event.preventDefault();
        if (form.data.prompt.trim() === '' || form.processing) {
            return;
        }
        form.transform((data) => ({
            ...data,
            weight_kg: data.weight_kg === '' ? null : Number(data.weight_kg),
            bedtime: data.bedtime === '' ? null : data.bedtime,
            sensitivity: data.sensitivity === '' ? null : data.sensitivity,
        }));
        void form.submit();
    }

    return (
        <>
            <Head title="Brew Buddy — AI Caffeine Coach">
                <meta
                    name="description"
                    content="Describe your day. Brew Buddy assembles a personalised caffeine plan with safe doses, drink picks, and a sleep-aware cutoff."
                />
            </Head>
            <style>{`
                @keyframes brew-result-in {
                    from { opacity: 0; transform: translateY(8px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                [data-brew-result] {
                    animation: brew-result-in 220ms ease-out both;
                }
                @media (prefers-reduced-motion: reduce) {
                    [data-brew-result] { animation: none; }
                }
            `}</style>

            <div className="mx-auto max-w-2xl px-4 py-12">
                <h1 className="text-[32px] font-bold leading-tight tracking-tight text-slate-900 md:text-5xl dark:text-slate-50">
                    Brew Buddy
                </h1>
                <p className="mt-3 text-lg text-slate-600 dark:text-slate-400">
                    Tell me about your day. I'll build a caffeine plan that fits your schedule, your sensitivity,
                    and your bedtime.
                </p>

                <form
                    onSubmit={onSubmit}
                    className="mt-8 space-y-5 rounded-2xl border border-slate-200 bg-white p-6 md:p-8 dark:border-slate-700 dark:bg-slate-800"
                >
                    <div>
                        <label
                            htmlFor="brew-prompt"
                            className="block text-sm font-medium text-slate-700 dark:text-slate-200"
                        >
                            Your day
                        </label>
                        <textarea
                            id="brew-prompt"
                            value={form.data.prompt}
                            onChange={(event) => form.setData('prompt', event.target.value)}
                            placeholder="e.g. 9am demo, 3pm slump, bedtime 22:30, normal sensitivity, ~75kg"
                            rows={4}
                            className="mt-1 block w-full rounded-md border border-slate-200 bg-white px-3.5 py-2.5 text-base text-slate-900 placeholder-slate-400 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/15 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-50 dark:placeholder-slate-500"
                            required
                            maxLength={2000}
                        />
                        {form.errors.prompt && (
                            <p className="mt-1 text-sm text-red-600 dark:text-red-400">{form.errors.prompt}</p>
                        )}
                        <div className="mt-2 flex flex-wrap gap-2">
                            {EXAMPLES.map((example) => (
                                <button
                                    type="button"
                                    key={example}
                                    onClick={() => form.setData('prompt', example)}
                                    className="rounded-full border border-slate-200 px-3 py-1 text-xs text-slate-600 hover:border-emerald-400 hover:text-emerald-700 dark:border-slate-700 dark:text-slate-300 dark:hover:border-emerald-600 dark:hover:text-emerald-300"
                                >
                                    Try: {example.slice(0, 32)}…
                                </button>
                            ))}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label
                                htmlFor="brew-weight"
                                className="block text-sm font-medium text-slate-700 dark:text-slate-200"
                            >
                                Weight (kg)
                            </label>
                            <input
                                id="brew-weight"
                                type="number"
                                inputMode="decimal"
                                min={30}
                                max={200}
                                step={0.1}
                                value={form.data.weight_kg}
                                onChange={(event) => form.setData('weight_kg', event.target.value)}
                                placeholder="optional"
                                className="mt-1 block w-full rounded-md border border-slate-200 bg-white px-3.5 py-2.5 text-base text-slate-900 placeholder-slate-400 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/15 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-50"
                            />
                            {form.errors.weight_kg && (
                                <p className="mt-1 text-sm text-red-600 dark:text-red-400">{form.errors.weight_kg}</p>
                            )}
                        </div>
                        <div>
                            <label
                                htmlFor="brew-bedtime"
                                className="block text-sm font-medium text-slate-700 dark:text-slate-200"
                            >
                                Bedtime
                            </label>
                            <input
                                id="brew-bedtime"
                                type="time"
                                value={form.data.bedtime}
                                onChange={(event) => form.setData('bedtime', event.target.value)}
                                className="mt-1 block w-full rounded-md border border-slate-200 bg-white px-3.5 py-2.5 text-base text-slate-900 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/15 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-50"
                            />
                            {form.errors.bedtime && (
                                <p className="mt-1 text-sm text-red-600 dark:text-red-400">{form.errors.bedtime}</p>
                            )}
                        </div>
                        <div>
                            <label
                                htmlFor="brew-sensitivity"
                                className="block text-sm font-medium text-slate-700 dark:text-slate-200"
                            >
                                Sensitivity
                            </label>
                            <select
                                id="brew-sensitivity"
                                value={form.data.sensitivity}
                                onChange={(event) =>
                                    form.setData('sensitivity', event.target.value as PlanFormData['sensitivity'])
                                }
                                className="mt-1 block w-full rounded-md border border-slate-200 bg-white px-3.5 py-2.5 text-base text-slate-900 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/15 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-50"
                            >
                                <option value="">Auto</option>
                                <option value="low">Low (tolerant)</option>
                                <option value="normal">Normal</option>
                                <option value="high">High (sensitive)</option>
                            </select>
                            {form.errors.sensitivity && (
                                <p className="mt-1 text-sm text-red-600 dark:text-red-400">{form.errors.sensitivity}</p>
                            )}
                        </div>
                    </div>

                    <button
                        type="submit"
                        disabled={form.processing || form.data.prompt.trim() === ''}
                        className="w-full rounded-md bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/40 disabled:cursor-not-allowed disabled:bg-slate-300 dark:disabled:bg-slate-700"
                    >
                        {form.processing ? 'Brewing your plan…' : 'Plan my day'}
                    </button>
                </form>

                {form.response && (
                    <section data-brew-result className="mt-8" aria-label={form.response.summary}>
                        <BrewPlanRenderer spec={form.response.spec} />
                    </section>
                )}
            </div>
        </>
    );
}
