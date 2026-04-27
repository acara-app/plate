type Tone = 'good' | 'warn' | 'danger' | 'info';

const TONE_CLASSES: Record<Tone, string> = {
    good: 'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100',
    warn: 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100',
    danger: 'border-red-200 bg-red-50 text-red-900 dark:border-red-900 dark:bg-red-950/40 dark:text-red-100',
    info: 'border-slate-200 bg-slate-50 text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100',
};

export function Stat({ props }: { props: { label: string; value: string; tone: Tone } }) {
    return (
        <div className={`rounded-xl border p-4 ${TONE_CLASSES[props.tone] ?? TONE_CLASSES.info}`}>
            <div className="text-xs font-medium uppercase tracking-wide opacity-70">{props.label}</div>
            <div className="mt-1 text-2xl font-bold">{props.value}</div>
        </div>
    );
}
