import { ShieldCheck } from 'lucide-react';

export function SafetyNote({
    props,
}: {
    props: { title: string; body: string; items: string[] };
}) {
    return (
        <section className="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/80">
            <div className="flex gap-3">
                <ShieldCheck className="mt-0.5 size-4 shrink-0 text-slate-600 dark:text-slate-300" />
                <div>
                    <h3 className="text-sm font-semibold text-slate-900 dark:text-slate-50">
                        {props.title}
                    </h3>
                    <p className="mt-1 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                        {props.body}
                    </p>
                    <ul className="mt-3 flex flex-wrap gap-2">
                        {props.items.map((item) => (
                            <li
                                key={item}
                                className="rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs text-slate-600 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300"
                            >
                                {item}
                            </li>
                        ))}
                    </ul>
                </div>
            </div>
        </section>
    );
}
