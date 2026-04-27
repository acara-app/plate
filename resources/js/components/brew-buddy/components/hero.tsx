export function Hero({ props }: { props: { title: string; subtitle: string } }) {
    return (
        <div className="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 dark:border-emerald-900 dark:bg-emerald-950/40">
            <h2 className="text-xl font-bold text-emerald-950 md:text-2xl dark:text-emerald-50">
                {props.title}
            </h2>
            <p className="mt-2 text-sm text-emerald-900/80 dark:text-emerald-100/80">{props.subtitle}</p>
        </div>
    );
}
