import { AlertTriangle, CheckCircle2 } from 'lucide-react';

type Tone = 'green' | 'amber' | 'red' | 'blue' | 'slate';

const TONE_ACCENT: Record<Tone, string> = {
    green: 'text-[#6F8654]',
    amber: 'text-[#B8843E]',
    red: 'text-[#B5482E]',
    blue: 'text-[#6B3F1D]',
    slate: 'text-[#3D3833]',
};

export function VerdictCard({
    props,
}: {
    props: {
        title: string;
        body: string;
        badge: string;
        tone: Tone;
        limit_mg: number | null;
    };
}) {
    const Icon = props.limit_mg === null ? AlertTriangle : CheckCircle2;
    const accent = TONE_ACCENT[props.tone] ?? TONE_ACCENT.slate;

    return (
        <section className="rounded-none border border-[#D9CFBC] bg-[#F2EBDD] p-6">
            <div className="flex items-start justify-between gap-4 border-b border-[#D9CFBC] pb-5">
                <div className="flex items-center gap-3">
                    <Icon className={`size-5 ${accent}`} aria-hidden={true} />
                    <span
                        className={`font-mono text-[11px] tracking-[0.18em] uppercase ${accent}`}
                    >
                        {props.badge}
                    </span>
                </div>
                <div className="text-right">
                    <div
                        className={`font-bold text-5xl leading-none tracking-[-0.04em] tabular-nums sm:text-6xl ${accent}`}
                    >
                        {props.limit_mg === null ? '0' : props.limit_mg}
                    </div>
                    <div className="mt-1 font-mono text-[10px] tracking-[0.18em] text-[#6E665C] uppercase">
                        mg / day
                    </div>
                </div>
            </div>
            <h2 className="mt-5 font-bold text-2xl leading-tight tracking-[-0.02em] text-[#1A1814] sm:text-3xl">
                {props.title}
            </h2>
            <p className="mt-3 text-sm leading-relaxed text-[#3D3833]">
                {props.body}
            </p>
        </section>
    );
}
