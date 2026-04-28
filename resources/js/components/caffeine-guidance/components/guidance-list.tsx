import { CheckCircle2 } from 'lucide-react';

export function GuidanceList({
    props,
}: {
    props: { title: string; items: string[] };
}) {
    return (
        <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
            <h3 className="text-sm font-semibold text-slate-900 dark:text-slate-50">
                {props.title}
            </h3>
            <ul className="mt-4 space-y-3">
                {props.items.map((item) => (
                    <li
                        key={item}
                        className="flex gap-3 text-sm leading-relaxed text-slate-700 dark:text-slate-300"
                    >
                        <CheckCircle2 className="mt-0.5 size-4 shrink-0 text-emerald-600 dark:text-emerald-400" />
                        <span>{item}</span>
                    </li>
                ))}
            </ul>
        </section>
    );
}
