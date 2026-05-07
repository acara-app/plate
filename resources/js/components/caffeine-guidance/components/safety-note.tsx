import { ShieldCheck } from 'lucide-react';

export function SafetyNote({
    props,
}: {
    props: { title: string; body: string; items: string[] };
}) {
    return (
        <section className="rounded-none border border-[#D9CFBC] bg-[#EBE2D0] p-5">
            <div className="flex gap-3">
                <ShieldCheck
                    className="mt-0.5 size-4 shrink-0 text-[#6B3F1D]"
                    aria-hidden={true}
                />
                <div>
                    <h3 className="font-bold text-lg leading-tight text-[#1A1814]">
                        {props.title}
                    </h3>
                    <p className="mt-1 text-sm leading-relaxed text-[#3D3833]">
                        {props.body}
                    </p>
                    <ul className="mt-3 flex flex-wrap gap-2">
                        {props.items.map((item) => (
                            <li
                                key={item}
                                className="rounded-none border border-[#D9CFBC] bg-[#F2EBDD] px-2.5 py-1 font-mono text-[10px] tracking-[0.1em] text-[#3D3833] uppercase"
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
