import { useTranslation } from 'react-i18next';

type Tone = 'green' | 'amber' | 'red' | 'blue' | 'slate';

const TONE_BORDER: Record<Tone, string> = {
    green:
        'border-emerald-500 bg-emerald-50 text-emerald-950 dark:border-emerald-500 dark:bg-emerald-950/40 dark:text-emerald-50',
    amber:
        'border-amber-500 bg-amber-50 text-amber-950 dark:border-amber-500 dark:bg-amber-950/40 dark:text-amber-50',
    red: 'border-red-500 bg-red-50 text-red-950 dark:border-red-500 dark:bg-red-950/40 dark:text-red-50',
    blue: 'border-sky-500 bg-sky-50 text-sky-950 dark:border-sky-500 dark:bg-sky-950/40 dark:text-sky-50',
    slate:
        'border-gray-500 bg-gray-50 text-gray-950 dark:border-slate-600 dark:bg-slate-800 dark:text-gray-50',
};

const TONE_NUMBER: Record<Tone, string> = {
    green: 'text-emerald-700 dark:text-emerald-300',
    amber: 'text-amber-700 dark:text-amber-300',
    red: 'text-red-700 dark:text-red-300',
    blue: 'text-sky-700 dark:text-sky-300',
    slate: 'text-gray-700 dark:text-slate-300',
};

export function LimitGauge({
    props,
}: {
    props: {
        label: string;
        value_label: string;
        limit_mg: number | null;
        max_mg: number;
        tone: Tone;
        caption: string;
    };
}) {
    const { t } = useTranslation('caffeine');

    const percentage =
        props.limit_mg === null || props.max_mg <= 0
            ? 0
            : Math.round((props.limit_mg / props.max_mg) * 100);

    const limitDisplay =
        props.limit_mg === null ? '0' : String(props.limit_mg);

    return (
        <section className="rounded-xl border border-gray-200 bg-white p-5 shadow-none dark:border-slate-700 dark:bg-slate-800">
            <h3 className="text-sm font-semibold text-gray-900 dark:text-gray-50">
                {props.label}
            </h3>
            <p className="mt-1 text-xs text-gray-500 dark:text-slate-400">
                {props.caption}
            </p>

            <div className="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                {/* Your daily limit */}
                <div
                    className={`rounded-xl border-2 p-5 text-center transition duration-150 ease-[cubic-bezier(0.4,0,0.2,1)] ${TONE_BORDER[props.tone] ?? TONE_BORDER.slate}`}
                >
                    <div
                        className={`text-3xl font-bold tabular-nums md:text-4xl ${TONE_NUMBER[props.tone] ?? TONE_NUMBER.slate}`}
                    >
                        {limitDisplay}
                    </div>
                    <div className="mt-1 text-xs font-semibold uppercase tracking-wide opacity-75">
                        mg / day
                    </div>
                    <div className="mt-2 text-sm font-semibold">
                        {t('gauge_your_limit')}
                    </div>
                </div>

                {/* General adult maximum */}
                <div className="rounded-xl border border-gray-200 bg-gray-50 p-5 text-center dark:border-slate-700 dark:bg-slate-800">
                    <div className="text-3xl font-bold tabular-nums text-gray-700 md:text-4xl dark:text-slate-300">
                        {props.max_mg}
                    </div>
                    <div className="mt-1 text-xs font-semibold uppercase tracking-wide text-gray-500 opacity-75 dark:text-slate-400">
                        mg / day
                    </div>
                    <div className="mt-2 text-sm font-semibold text-gray-700 dark:text-slate-300">
                        {t('gauge_general_max')}
                    </div>
                </div>
            </div>

            <p className="mt-4 text-center text-sm leading-relaxed text-gray-600 dark:text-slate-400">
                {props.limit_mg === null || percentage === 0
                    ? t('gauge_avoid')
                    : t('gauge_comparison', { percentage })}
            </p>
        </section>
    );
}
