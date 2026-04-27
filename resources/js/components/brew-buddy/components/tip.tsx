export function Tip({ props }: { props: { title: string; body: string } }) {
    return (
        <div className="rounded-xl border border-sky-200 bg-sky-50 p-4 dark:border-sky-900 dark:bg-sky-950/40">
            <h4 className="text-sm font-semibold text-sky-900 dark:text-sky-100">{props.title}</h4>
            <p className="mt-1 text-sm text-sky-900/80 dark:text-sky-100/80">{props.body}</p>
        </div>
    );
}
