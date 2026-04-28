type Tone = 'green' | 'amber' | 'red' | 'slate';

const BAR_CLASSES: Record<Tone, string> = {
    green: 'bg-emerald-500',
    amber: 'bg-amber-500',
    red: 'bg-red-500',
    slate: 'bg-slate-500',
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
    const percentage =
        props.limit_mg === null || props.max_mg <= 0
            ? 0
            : Math.min(100, Math.max(0, (props.limit_mg / props.max_mg) * 100));

    return (
        <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <h3 className="text-sm font-semibold text-slate-900 dark:text-slate-50">
                        {props.label}
                    </h3>
                    <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        {props.caption}
                    </p>
                </div>
                <div className="rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-900 dark:bg-slate-800 dark:text-slate-100">
                    {props.value_label}
                </div>
            </div>
            <div
                className="mt-5 h-3 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"
                role="meter"
                aria-label={props.label}
                aria-valuemin={0}
                aria-valuemax={props.max_mg}
                aria-valuenow={props.limit_mg ?? 0}
            >
                <div
                    className={`h-full rounded-full ${BAR_CLASSES[props.tone] ?? BAR_CLASSES.slate}`}
                    style={{ width: `${percentage}%` }}
                />
            </div>
            <div className="mt-2 flex justify-between text-xs text-slate-500 dark:text-slate-400">
                <span>0 mg</span>
                <span>{props.max_mg} mg</span>
            </div>
        </section>
    );
}
