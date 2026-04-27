export function Warning({ props }: { props: { title: string; body: string } }) {
    return (
        <div className="rounded-xl border border-amber-300 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950/40">
            <h4 className="text-sm font-semibold text-amber-900 dark:text-amber-100">⚠ {props.title}</h4>
            <p className="mt-1 text-sm text-amber-900/80 dark:text-amber-100/80">{props.body}</p>
        </div>
    );
}
