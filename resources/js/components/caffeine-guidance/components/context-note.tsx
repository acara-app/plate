import { Info } from 'lucide-react';

export function ContextNote({
    props,
}: {
    props: { title: string; body: string };
}) {
    return (
        <section className="rounded-xl border border-sky-200 bg-sky-50 p-4 dark:border-sky-900 dark:bg-sky-950/40">
            <div className="flex gap-3">
                <Info className="mt-0.5 size-4 shrink-0 text-sky-700 dark:text-sky-300" />
                <div>
                    <h3 className="text-sm font-semibold text-sky-950 dark:text-sky-50">
                        {props.title}
                    </h3>
                    <p className="mt-1 text-sm leading-relaxed text-sky-900/80 dark:text-sky-100/80">
                        {props.body}
                    </p>
                </div>
            </div>
        </section>
    );
}
