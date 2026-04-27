export function DrinkCard({
    props,
}: {
    props: {
        name: string;
        volume_oz: number;
        caffeine_mg: number;
        time_hint: string;
        reason: string;
    };
}) {
    return (
        <div className="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <h3 className="text-base font-semibold text-slate-900 dark:text-slate-50">{props.name}</h3>
                    <p className="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{props.time_hint}</p>
                </div>
                <div className="shrink-0 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-900 dark:bg-emerald-900/40 dark:text-emerald-100">
                    {Math.round(props.caffeine_mg)} mg
                </div>
            </div>
            <p className="mt-3 text-sm leading-relaxed text-slate-700 dark:text-slate-300">{props.reason}</p>
            <p className="mt-2 text-xs text-slate-500 dark:text-slate-400">
                {props.volume_oz} oz serving
            </p>
        </div>
    );
}
