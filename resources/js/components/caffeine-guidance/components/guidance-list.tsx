export function GuidanceList({
    props,
}: {
    props: { title: string; items: string[] };
}) {
    return (
        <section className="rounded-none border border-[#D9CFBC] bg-[#F2EBDD] p-5">
            <h3 className="font-mono text-[11px] tracking-[0.18em] text-[#6E665C] uppercase">
                {props.title}
            </h3>
            <ul className="mt-4 divide-y divide-[#D9CFBC]">
                {props.items.map((item, index) => (
                    <li
                        key={item}
                        className="flex gap-4 py-3 text-sm leading-relaxed text-[#3D3833] first:pt-0 last:pb-0"
                    >
                        <span
                            className="mt-0.5 shrink-0 font-mono text-[11px] tracking-[0.14em] text-[#C4623A]"
                            aria-hidden={true}
                        >
                            {String(index + 1).padStart(2, '0')}
                        </span>
                        <span>{item}</span>
                    </li>
                ))}
            </ul>
        </section>
    );
}
