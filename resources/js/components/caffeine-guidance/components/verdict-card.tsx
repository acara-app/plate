import { AlertTriangle, CheckCircle2 } from 'lucide-react';

type Tone = 'green' | 'amber' | 'red' | 'slate';

const TONE_CLASSES: Record<Tone, string> = {
    green: 'border-emerald-200 bg-emerald-50 text-emerald-950 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-50',
    amber: 'border-amber-200 bg-amber-50 text-amber-950 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-50',
    red: 'border-red-200 bg-red-50 text-red-950 dark:border-red-900 dark:bg-red-950/40 dark:text-red-50',
    slate: 'border-slate-200 bg-slate-50 text-slate-950 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-50',
};

export function VerdictCard({
    props,
}: {
    props: {
        title: string;
        body: string;
        badge: string;
        tone: Tone;
        limit_mg: number | null;
    };
}) {
    const Icon = props.limit_mg === null ? AlertTriangle : CheckCircle2;

    return (
        <section
            className={`rounded-2xl border p-5 shadow-sm ${TONE_CLASSES[props.tone] ?? TONE_CLASSES.slate}`}
        >
            <div className="flex items-start justify-between gap-4">
                <div className="flex items-center gap-2">
                    <span className="flex size-9 items-center justify-center rounded-full bg-white/70 dark:bg-white/10">
                        <Icon className="size-5" aria-hidden="true" />
                    </span>
                    <span className="rounded-full border border-current/20 px-2.5 py-1 text-xs font-semibold uppercase">
                        {props.badge}
                    </span>
                </div>
                <div className="text-right">
                    <div className="text-3xl font-bold tabular-nums md:text-4xl">
                        {props.limit_mg === null ? '0' : props.limit_mg}
                    </div>
                    <div className="text-xs font-medium opacity-75">
                        {props.limit_mg === null ? 'routine mg' : 'mg / day'}
                    </div>
                </div>
            </div>
            <h2 className="mt-5 text-2xl leading-tight font-bold md:text-3xl">
                {props.title}
            </h2>
            <p className="mt-2 text-sm leading-relaxed opacity-85">
                {props.body}
            </p>
        </section>
    );
}
