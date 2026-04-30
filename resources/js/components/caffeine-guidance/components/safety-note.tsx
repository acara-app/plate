import { ShieldCheck } from 'lucide-react';

export function SafetyNote({
    props,
}: {
    props: { title: string; body: string; items: string[] };
}) {
    return (
        <section className="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-slate-700 dark:bg-slate-800/80">
            <div className="flex gap-3">
                <ShieldCheck className="mt-0.5 size-4 shrink-0 text-gray-600 dark:text-slate-400" />
                <div>
                    <h3 className="text-sm font-semibold text-gray-900 dark:text-gray-50">
                        {props.title}
                    </h3>
                    <p className="mt-1 text-sm leading-relaxed text-gray-600 dark:text-slate-400">
                        {props.body}
                    </p>
                    <ul className="mt-3 flex flex-wrap gap-2">
                        {props.items.map((item) => (
                            <li
                                key={item}
                                className="rounded-full border border-gray-200 bg-white px-2.5 py-1 text-xs text-gray-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300"
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
