interface TimelineSlot {
    time_label: string;
    label: string;
    caffeine_mg: number;
}

export function Timeline({ props }: { props: { slots: TimelineSlot[] } }) {
    const maxMg = Math.max(...props.slots.map((slot) => slot.caffeine_mg), 1);

    return (
        <div className="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
            <h3 className="mb-4 text-sm font-semibold text-slate-900 dark:text-slate-50">Your day</h3>
            <ol className="space-y-3">
                {props.slots.map((slot, index) => (
                    <li key={index} className="flex items-center gap-3">
                        <div className="w-14 shrink-0 text-xs font-medium tabular-nums text-slate-500 dark:text-slate-400">
                            {slot.time_label}
                        </div>
                        <div className="flex-1">
                            <div className="flex items-center justify-between gap-2">
                                <span className="text-sm text-slate-900 dark:text-slate-100">{slot.label}</span>
                                <span className="text-xs font-semibold text-emerald-700 dark:text-emerald-300">
                                    {Math.round(slot.caffeine_mg)} mg
                                </span>
                            </div>
                            <div className="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700">
                                <div
                                    className="h-full rounded-full bg-emerald-500"
                                    style={{ width: `${Math.max(6, (slot.caffeine_mg / maxMg) * 100)}%` }}
                                />
                            </div>
                        </div>
                    </li>
                ))}
            </ol>
        </div>
    );
}
